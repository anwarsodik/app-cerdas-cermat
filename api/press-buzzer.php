<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$data = request_json();
$matchId = (int) ($data['match_id'] ?? 0);
$teamId = (int) ($data['team_id'] ?? 0);
$accessCode = strtoupper(trim((string) ($data['access_code'] ?? '')));

if ($matchId < 1 || $teamId < 1) {
    json_response(['ok' => false, 'message' => 'Data bel tidak valid.'], 422);
}

$pdo = db();
$pdo->beginTransaction();

try {
    $teamStatement = $pdo->prepare(
        'SELECT t.name, t.access_code
         FROM match_teams mt JOIN teams t ON t.id = mt.team_id
         WHERE mt.match_id = ? AND mt.team_id = ?'
    );
    $teamStatement->execute([$matchId, $teamId]);
    $team = $teamStatement->fetch();

    if (!$team) {
        throw new DomainException('Regu tidak terdaftar pada pertandingan ini.');
    }
    if (!user() && !hash_equals($team['access_code'], $accessCode)) {
        throw new DomainException('Kode akses regu tidak cocok.');
    }

    $matchStatement = $pdo->prepare('SELECT status, buzzer_team_id FROM matches WHERE id = ? FOR UPDATE');
    $matchStatement->execute([$matchId]);
    $match = $matchStatement->fetch();

    if (!$match) {
        throw new DomainException('Pertandingan tidak ditemukan.');
    }

    $accepted = $match['status'] === 'question_open' && $match['buzzer_team_id'] === null;
    $reason = $accepted
        ? 'Bel diterima'
        : ($match['status'] === 'buzzed' ? 'Bel sudah dikunci' : 'Bel belum dibuka');

    $eventStatement = $pdo->prepare(
        'INSERT INTO buzz_events (match_id, team_id, accepted, reason) VALUES (?, ?, ?, ?)'
    );
    $eventStatement->execute([$matchId, $teamId, $accepted ? 1 : 0, $reason]);

    if ($accepted) {
        $update = $pdo->prepare(
            'UPDATE matches SET status = \'buzzed\', buzzer_team_id = ?, buzzed_at = NOW(6) WHERE id = ?'
        );
        $update->execute([$teamId, $matchId]);
    }

    $pdo->commit();
    json_response([
        'ok' => $accepted,
        'accepted' => $accepted,
        'message' => $accepted ? $team['name'] . ' mendapat hak jawab.' : $reason . '.',
    ], $accepted ? 200 : 409);
} catch (DomainException $exception) {
    $pdo->rollBack();
    json_response(['ok' => false, 'message' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}
