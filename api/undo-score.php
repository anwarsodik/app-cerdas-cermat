<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_auth(true);
verify_csrf();

$data = request_json();
$matchId = (int) ($data['match_id'] ?? 0);
$pdo = db();
$pdo->beginTransaction();

try {
    $matchStatement = $pdo->prepare('SELECT status FROM matches WHERE id = ? FOR UPDATE');
    $matchStatement->execute([$matchId]);
    $status = $matchStatement->fetchColumn();
    if (!$status || $status === 'finished') {
        throw new DomainException('Pertandingan tidak dapat dikoreksi.');
    }

    $eventStatement = $pdo->prepare(
        'SELECT * FROM score_events
         WHERE match_id = ? AND reversed_at IS NULL
         ORDER BY id DESC LIMIT 1 FOR UPDATE'
    );
    $eventStatement->execute([$matchId]);
    $event = $eventStatement->fetch();
    if (!$event) {
        throw new DomainException('Belum ada nilai yang dapat dibatalkan.');
    }

    $scoreStatement = $pdo->prepare(
        'SELECT score FROM match_teams WHERE match_id = ? AND team_id = ? FOR UPDATE'
    );
    $scoreStatement->execute([$matchId, $event['team_id']]);
    $currentScore = $scoreStatement->fetchColumn();
    if ($currentScore === false) {
        throw new DomainException('Skor regu tidak ditemukan.');
    }

    $updateScore = $pdo->prepare(
        'UPDATE match_teams SET score = ? WHERE match_id = ? AND team_id = ?'
    );
    $updateScore->execute([(int) $event['score_before'], $matchId, $event['team_id']]);

    $reverse = $pdo->prepare(
        'UPDATE score_events SET reversed_at = NOW(), reversed_by = ? WHERE id = ?'
    );
    $reverse->execute([user()['id'], $event['id']]);

    $updateMatch = $pdo->prepare('UPDATE matches SET status = \'buzzed\' WHERE id = ?');
    $updateMatch->execute([$matchId]);

    $pdo->commit();
    json_response(['ok' => true, 'message' => 'Nilai terakhir dibatalkan. Regu dapat dinilai kembali.']);
} catch (DomainException $exception) {
    $pdo->rollBack();
    json_response(['ok' => false, 'message' => $exception->getMessage()], 409);
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}

