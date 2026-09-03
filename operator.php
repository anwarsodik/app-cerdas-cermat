<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_auth();

$event = active_event();
if (!$event) {
    flash('error', 'Buat dan pilih kegiatan sebelum membuka konsol.');
    redirect('/admin/events.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'start_match') {
    verify_csrf();
    $roundId = (int) ($_POST['round_id'] ?? 0);
    $pdo = db();

    $roundStatement = $pdo->prepare('SELECT id FROM rounds WHERE id = ? AND event_id = ?');
    $roundStatement->execute([$roundId, $event['id']]);
    $teamStatement = $pdo->prepare('SELECT id FROM teams WHERE event_id = ? AND is_active = 1 ORDER BY name');
    $teamStatement->execute([$event['id']]);
    $teamIds = array_map('intval', $teamStatement->fetchAll(PDO::FETCH_COLUMN));

    if (!$roundStatement->fetchColumn()) {
        flash('error', 'Babak tidak ditemukan.');
    } elseif (count($teamIds) < 2) {
        flash('error', 'Aktifkan minimal dua regu sebelum memulai pertandingan.');
    } elseif (current_match_for_event((int) $event['id'])) {
        flash('error', 'Masih ada pertandingan yang sedang berjalan.');
    } else {
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare('INSERT INTO matches (round_id, status) VALUES (?, \'ready\')');
            $statement->execute([$roundId]);
            $matchId = (int) $pdo->lastInsertId();

            $insertTeam = $pdo->prepare(
                'INSERT INTO match_teams (match_id, team_id, display_order) VALUES (?, ?, ?)'
            );
            foreach ($teamIds as $index => $teamId) {
                $insertTeam->execute([$matchId, $teamId, $index + 1]);
            }
            $pdo->commit();
            flash('success', 'Pertandingan siap. Buka soal ketika moderator mulai membacakan soal.');
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }
    redirect('/operator.php');
}

$match = current_match_for_event((int) $event['id']);
$payload = $match ? match_payload((int) $match['id']) : null;

$roundStatement = db()->prepare('SELECT * FROM rounds WHERE event_id = ? ORDER BY display_order, id');
$roundStatement->execute([$event['id']]);
$rounds = $roundStatement->fetchAll();

$teamCountStatement = db()->prepare('SELECT COUNT(*) FROM teams WHERE event_id = ? AND is_active = 1');
$teamCountStatement->execute([$event['id']]);
$teamCount = (int) $teamCountStatement->fetchColumn();

$finishedStatement = db()->prepare(
    'SELECT m.id, m.finished_at, r.name AS round_name
     FROM matches m JOIN rounds r ON r.id = m.round_id
     WHERE r.event_id = ? AND m.status = \'finished\'
     ORDER BY m.id DESC LIMIT 1'
);
$finishedStatement->execute([$event['id']]);
$lastFinishedMatch = $finishedStatement->fetch() ?: null;

$pageTitle = 'Konsol Pertandingan';
$bodyClass = 'operator-page';
require __DIR__ . '/includes/header.php';
?>
<?php if (!$payload): ?>
    <header class="page-heading split-heading">
        <div>
            <span class="eyebrow">Konsol pertandingan</span>
            <h1>Siapkan babak berikutnya</h1>
            <p><?= e($event['name']) ?> memiliki <?= $teamCount ?> regu aktif.</p>
        </div>
        <?php if ($lastFinishedMatch): ?>
            <a class="button button-secondary" href="/results.php?match_id=<?= (int) $lastFinishedMatch['id'] ?>">Lihat hasil terakhir</a>
        <?php endif; ?>
    </header>

    <?php if (!$rounds || $teamCount < 2): ?>
        <section class="empty-state empty-state-large">
            <span class="empty-symbol" aria-hidden="true">CCI</span>
            <h2>Data pertandingan belum lengkap</h2>
            <p>Tambahkan minimal dua regu aktif dan satu babak sebelum membuka pertandingan.</p>
            <div class="form-actions">
                <a class="button button-primary" href="/admin/teams.php">Atur regu</a>
                <a class="button button-secondary" href="/admin/rounds.php">Atur babak</a>
            </div>
        </section>
    <?php else: ?>
        <section class="start-match-panel" aria-labelledby="start-title">
            <div>
                <span class="eyebrow">Langkah berikutnya</span>
                <h2 id="start-title">Pilih babak yang akan dimainkan</h2>
                <p>Semua regu aktif akan dimasukkan dengan skor awal nol.</p>
            </div>
            <form method="post" class="start-match-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="start_match">
                <label>
                    <span>Babak</span>
                    <select name="round_id" required>
                        <option value="">Pilih babak</option>
                        <?php foreach ($rounds as $round): ?>
                            <option value="<?= (int) $round['id'] ?>"><?= e($round['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="button button-primary" type="submit">Siapkan pertandingan</button>
            </form>
        </section>
    <?php endif; ?>
<?php else: ?>
    <?php $matchData = $payload['match']; ?>
    <div class="operator-console" data-match-id="<?= (int) $matchData['id'] ?>" data-csrf="<?= e(csrf_token()) ?>">
        <header class="console-header">
            <div>
                <span class="eyebrow"><?= e($matchData['event_name']) ?></span>
                <h1><?= e($matchData['round_name']) ?></h1>
            </div>
            <div class="console-header-actions">
                <a class="button button-secondary" href="/scoreboard.php?match_id=<?= (int) $matchData['id'] ?>" target="_blank" rel="noopener">Buka layar publik</a>
                <a class="button button-secondary" href="/buzzer.php?match_id=<?= (int) $matchData['id'] ?>" target="_blank" rel="noopener">Buka halaman regu</a>
            </div>
        </header>

        <div class="match-status-strip">
            <span class="status-dot" aria-hidden="true"></span>
            <strong id="match-status-label"><?= e(status_label($matchData['status'])) ?></strong>
            <span>Soal <span id="question-number"><?= (int) $matchData['current_question'] ?></span></span>
        </div>

        <section class="decision-stage" id="decision-stage" data-status="<?= e($matchData['status']) ?>">
            <div class="decision-copy">
                <span class="eyebrow" id="decision-kicker">Status pertandingan</span>
                <h2 id="decision-title">
                    <?= $matchData['buzzer_team_name'] ? e($matchData['buzzer_team_name']) . ' mendapat hak jawab' : 'Buka soal saat moderator siap' ?>
                </h2>
                <p id="decision-help">Kontrol yang tersedia menyesuaikan status pertandingan.</p>
            </div>
            <div class="decision-actions" id="decision-actions">
                <button class="button button-primary button-large" type="button" data-match-action="open-question">Buka soal</button>
                <button class="button button-correct button-large" type="button" data-match-action="correct">Jawaban benar</button>
                <button class="button button-wrong button-large" type="button" data-match-action="wrong">Jawaban salah</button>
                <button class="button button-secondary" type="button" data-match-action="cancel-buzz">Batalkan bel</button>
                <button class="button button-secondary" type="button" data-match-action="undo-score">Batalkan nilai terakhir</button>
            </div>
            <div class="action-feedback" id="action-feedback" role="status" aria-live="polite"></div>
        </section>

        <div class="console-columns">
            <section class="score-panel" aria-labelledby="score-title">
                <div class="section-heading">
                    <div><span class="eyebrow">Skor saat ini</span><h2 id="score-title">Regu peserta</h2></div>
                    <span class="muted">Klik bel untuk simulasi</span>
                </div>
                <div class="team-score-list" id="team-score-list">
                    <?php foreach ($payload['teams'] as $team): ?>
                        <article class="team-score-row" data-team-row="<?= (int) $team['id'] ?>">
                            <div><h3><?= e($team['name']) ?></h3><span class="team-state">Menunggu soal</span></div>
                            <strong class="score-value"><?= (int) $team['score'] ?></strong>
                            <button class="buzzer-sim-button" type="button" data-buzz-team="<?= (int) $team['id'] ?>">Tekan bel <?= e($team['name']) ?></button>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <aside class="history-panel" aria-labelledby="history-title">
                <div class="section-heading"><div><span class="eyebrow">Jejak keputusan</span><h2 id="history-title">Riwayat nilai</h2></div></div>
                <ol class="history-list" id="history-list">
                    <?php if (!$payload['history']): ?>
                        <li class="history-empty">Belum ada perubahan nilai.</li>
                    <?php else: ?>
                        <?php foreach ($payload['history'] as $item): ?>
                            <li class="<?= $item['reversed_at'] ? 'is-reversed' : '' ?>">
                                <strong><?= e($item['team_name']) ?></strong>
                                <span><?= e($item['reason']) ?>, skor <?= (int) $item['score_after'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ol>
                <button class="button button-danger-quiet button-block" type="button" data-match-action="finish">Akhiri pertandingan</button>
            </aside>
        </div>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
