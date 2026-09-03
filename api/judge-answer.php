<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_auth(true);
verify_csrf();

$data = request_json();
$matchId = (int) ($data['match_id'] ?? 0);
$decision = (string) ($data['decision'] ?? '');

if (!in_array($decision, ['correct', 'wrong', 'cancel_buzz'], true)) {
    json_response(['ok' => false, 'message' => 'Keputusan tidak valid.'], 422);
}

$pdo = db();
$pdo->beginTransaction();

try {
    $statement = $pdo->prepare(
        'SELECT m.status, m.buzzer_team_id, r.correct_score, r.wrong_score
         FROM matches m JOIN rounds r ON r.id = m.round_id
         WHERE m.id = ? FOR UPDATE'
    );
    $statement->execute([$matchId]);
    $match = $statement->fetch();

    if (!$match || $match['status'] !== 'buzzed' || !$match['buzzer_team_id']) {
        throw new DomainException('Bel belum terkunci pada satu regu.');
    }

    if ($decision === 'cancel_buzz') {
        $update = $pdo->prepare(
            'UPDATE matches SET status = \'question_open\', buzzer_team_id = NULL, buzzed_at = NULL WHERE id = ?'
        );
        $update->execute([$matchId]);
        $pdo->commit();
        json_response(['ok' => true, 'message' => 'Bel dibatalkan dan dibuka kembali.']);
    }

    $teamId = (int) $match['buzzer_team_id'];
    $delta = $decision === 'correct' ? (int) $match['correct_score'] : (int) $match['wrong_score'];
    $reason = $decision === 'correct' ? 'Jawaban benar' : 'Jawaban salah';

    $scoreStatement = $pdo->prepare(
        'SELECT score FROM match_teams WHERE match_id = ? AND team_id = ? FOR UPDATE'
    );
    $scoreStatement->execute([$matchId, $teamId]);
    $before = $scoreStatement->fetchColumn();
    if ($before === false) {
        throw new DomainException('Skor regu tidak ditemukan.');
    }
    $before = (int) $before;
    $after = $before + $delta;

    $updateScore = $pdo->prepare(
        'UPDATE match_teams SET score = ? WHERE match_id = ? AND team_id = ?'
    );
    $updateScore->execute([$after, $matchId, $teamId]);

    $log = $pdo->prepare(
        'INSERT INTO score_events
         (match_id, team_id, operator_id, score_before, delta, score_after, reason)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $log->execute([$matchId, $teamId, user()['id'], $before, $delta, $after, $reason]);

    $updateMatch = $pdo->prepare('UPDATE matches SET status = \'judged\' WHERE id = ?');
    $updateMatch->execute([$matchId]);

    $pdo->commit();
    json_response(['ok' => true, 'message' => $reason . '. Skor telah diperbarui.']);
} catch (DomainException $exception) {
    $pdo->rollBack();
    json_response(['ok' => false, 'message' => $exception->getMessage()], 409);
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}

