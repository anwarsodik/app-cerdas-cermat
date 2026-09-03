<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_auth();

$matchId = filter_input(INPUT_GET, 'match_id', FILTER_VALIDATE_INT);
$payload = $matchId ? match_payload((int) $matchId) : null;

if (!$payload) {
    flash('error', 'Hasil pertandingan tidak ditemukan.');
    redirect('/operator.php');
}

$rankedTeams = $payload['teams'];
usort($rankedTeams, static function (array $a, array $b): int {
    $scoreComparison = (int) $b['score'] <=> (int) $a['score'];
    return $scoreComparison !== 0 ? $scoreComparison : strcmp($a['name'], $b['name']);
});

$rank = 0;
$previousScore = null;
$position = 0;

$pageTitle = 'Hasil Pertandingan';
$bodyClass = 'results-page';
require __DIR__ . '/includes/header.php';
?>
<header class="result-hero">
    <div>
        <span class="eyebrow">Hasil pertandingan</span>
        <h1><?= e($payload['match']['round_name']) ?></h1>
        <p><?= e($payload['match']['event_name']) ?><?= $payload['match']['location'] ? ', ' . e($payload['match']['location']) : '' ?></p>
    </div>
    <div class="result-actions">
        <button class="button button-secondary" type="button" onclick="window.print()">Cetak hasil</button>
        <a class="button button-primary" href="/export-results.php?match_id=<?= (int) $matchId ?>">Unduh CSV</a>
    </div>
</header>

<section class="result-board" aria-labelledby="ranking-title">
    <div class="section-heading">
        <div><span class="eyebrow">Peringkat akhir</span><h2 id="ranking-title">Skor regu</h2></div>
        <span><?= count($rankedTeams) ?> regu</span>
    </div>
    <ol class="ranking-list">
        <?php foreach ($rankedTeams as $team): ?>
            <?php
            $position++;
            if ($previousScore === null || (int) $team['score'] !== $previousScore) {
                $rank = $position;
                $previousScore = (int) $team['score'];
            }
            ?>
            <li>
                <span class="rank-number"><?= $rank ?></span>
                <strong><?= e($team['name']) ?></strong>
                <span class="rank-score"><?= (int) $team['score'] ?></span>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<section class="result-history" aria-labelledby="result-history-title">
    <div class="section-heading"><div><span class="eyebrow">Audit nilai</span><h2 id="result-history-title">Keputusan terakhir</h2></div></div>
    <?php if (!$payload['history']): ?>
        <div class="empty-state"><p>Tidak ada perubahan nilai pada pertandingan ini.</p></div>
    <?php else: ?>
        <div class="record-list">
            <?php foreach ($payload['history'] as $item): ?>
                <article class="record-row <?= $item['reversed_at'] ? 'is-muted' : '' ?>">
                    <div><h3><?= e($item['team_name']) ?></h3><p><?= e($item['reason']) ?><?= $item['reversed_at'] ? ', dibatalkan' : '' ?></p></div>
                    <strong><?= (int) $item['score_after'] ?></strong>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

