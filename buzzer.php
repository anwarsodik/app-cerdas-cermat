<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$matchId = filter_input(INPUT_GET, 'match_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
$payload = $matchId ? match_payload((int) $matchId) : null;
$selectedTeam = null;
$error = null;

if (!$payload) {
    $error = 'Pertandingan tidak ditemukan atau tautan tidak lengkap.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim((string) ($_POST['access_code'] ?? '')));
    $statement = db()->prepare(
        'SELECT t.id, t.name, t.access_code
         FROM match_teams mt JOIN teams t ON t.id = mt.team_id
         WHERE mt.match_id = ? AND t.access_code = ? LIMIT 1'
    );
    $statement->execute([$matchId, $code]);
    $selectedTeam = $statement->fetch() ?: null;
    if (!$selectedTeam) {
        $error = 'Kode akses tidak cocok dengan regu pada pertandingan ini.';
    }
}

$pageTitle = 'Bel Regu';
$bodyClass = 'buzzer-page public-page';
$showAppHeader = false;
require __DIR__ . '/includes/header.php';
?>
<section class="buzzer-shell">
    <?php if (!$selectedTeam): ?>
        <div class="buzzer-intro">
            <span class="brand-mark brand-mark-large" aria-hidden="true">CCI</span>
            <span class="eyebrow">Bel digital regu</span>
            <h1><?= $payload ? e($payload['match']['event_name']) : 'Hubungkan ke pertandingan' ?></h1>
            <p>Masukkan kode yang diberikan operator untuk membuka tombol bel regu.</p>
        </div>
        <?php if ($error): ?><div class="notice notice-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?php if ($payload): ?>
            <form method="post" class="buzzer-code-form">
                <input type="hidden" name="match_id" value="<?= (int) $matchId ?>">
                <label><span>Kode akses regu</span><input type="text" name="access_code" minlength="3" maxlength="12" autocomplete="off" required autofocus></label>
                <button class="button button-primary button-block" type="submit">Buka tombol bel</button>
            </form>
        <?php endif; ?>
    <?php else: ?>
        <div class="team-buzzer" data-team-buzzer data-match-id="<?= (int) $matchId ?>" data-team-id="<?= (int) $selectedTeam['id'] ?>" data-access-code="<?= e($selectedTeam['access_code']) ?>">
            <span class="eyebrow"><?= e($payload['match']['round_name']) ?></span>
            <h1><?= e($selectedTeam['name']) ?></h1>
            <p id="buzzer-instruction">Tunggu sampai operator membuka soal.</p>
            <button class="big-buzzer" id="big-buzzer" type="button" disabled>
                <span>TEKAN BEL</span>
                <small id="buzzer-state">Bel belum dibuka</small>
            </button>
            <div class="buzzer-feedback" id="buzzer-feedback" role="status" aria-live="assertive"></div>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
