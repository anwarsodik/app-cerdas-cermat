<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$matchId = filter_input(INPUT_GET, 'match_id', FILTER_VALIDATE_INT);
$payload = $matchId ? match_payload((int) $matchId) : null;

if (!$payload) {
    http_response_code(404);
    $pageTitle = 'Pertandingan tidak ditemukan';
    $bodyClass = 'public-page';
    $showAppHeader = false;
    require __DIR__ . '/includes/header.php';
    echo '<section class="empty-state empty-state-large"><h1>Pertandingan tidak ditemukan</h1><p>Periksa kembali tautan layar publik.</p></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$match = $payload['match'];
$pageTitle = 'Layar Skor';
$bodyClass = 'scoreboard-page public-page';
$showAppHeader = false;
require __DIR__ . '/includes/header.php';
?>
<div class="scoreboard" data-public-match="<?= (int) $match['id'] ?>">
    <header class="scoreboard-header">
        <div>
            <span class="scoreboard-brand">CCI An Nuur</span>
            <h1><?= e($match['event_name']) ?></h1>
        </div>
        <div class="scoreboard-round">
            <span><?= e($match['round_name']) ?></span>
            <strong>Soal <span id="public-question"><?= (int) $match['current_question'] ?></span></strong>
        </div>
    </header>

    <section class="public-status" id="public-status" data-status="<?= e($match['status']) ?>">
        <span class="public-status-label" id="public-status-label"><?= e(status_label($match['status'])) ?></span>
        <h2 id="public-status-title">
            <?= $match['buzzer_team_name'] ? e($match['buzzer_team_name']) . ' mendapat hak jawab' : 'Pertandingan segera dimulai' ?>
        </h2>
        <div class="answer-timer" id="answer-timer" data-seconds="<?= (int) $match['answer_seconds'] ?>" aria-live="polite"></div>
    </section>

    <section class="public-score-grid" id="public-score-grid" aria-label="Skor regu">
        <?php foreach ($payload['teams'] as $team): ?>
            <article class="public-team-card <?= $match['status'] === 'buzzed' && (int) $match['buzzer_team_id'] === (int) $team['id'] ? 'is-answering' : '' ?>" data-public-team="<?= (int) $team['id'] ?>">
                <h3><?= e($team['name']) ?></h3>
                <strong><?= (int) $team['score'] ?></strong>
                <span><?= $match['status'] === 'buzzed' && (int) $match['buzzer_team_id'] === (int) $team['id'] ? 'Hak jawab' : 'Menunggu' ?></span>
            </article>
        <?php endforeach; ?>
    </section>

    <footer class="scoreboard-footer">
        <span><?= e($match['location'] ?: 'Layar skor pertandingan') ?></span>
        <span id="public-sync-status">Terhubung ke pertandingan</span>
    </footer>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
