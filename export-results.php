<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_auth();

$matchId = filter_input(INPUT_GET, 'match_id', FILTER_VALIDATE_INT);
$payload = $matchId ? match_payload((int) $matchId) : null;
if (!$payload) {
    http_response_code(404);
    exit('Hasil pertandingan tidak ditemukan.');
}

$teams = $payload['teams'];
usort($teams, static fn (array $a, array $b): int => (int) $b['score'] <=> (int) $a['score']);

$safeRound = preg_replace('/[^A-Za-z0-9_-]+/', '-', $payload['match']['round_name']) ?: 'hasil';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="hasil-' . strtolower($safeRound) . '.csv"');
header('Cache-Control: no-store');

$output = fopen('php://output', 'wb');
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['Kegiatan', $payload['match']['event_name']], ',', '"', '');
fputcsv($output, ['Babak', $payload['match']['round_name']], ',', '"', '');
fputcsv($output, [], ',', '"', '');
fputcsv($output, ['Peringkat', 'Regu', 'Skor'], ',', '"', '');

$rank = 0;
$position = 0;
$previousScore = null;
foreach ($teams as $team) {
    $position++;
    if ($previousScore === null || (int) $team['score'] !== $previousScore) {
        $rank = $position;
        $previousScore = (int) $team['score'];
    }
    fputcsv($output, [$rank, $team['name'], (int) $team['score']], ',', '"', '');
}
fclose($output);
