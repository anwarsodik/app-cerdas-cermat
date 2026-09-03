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
    $statement = $pdo->prepare('SELECT status FROM matches WHERE id = ? FOR UPDATE');
    $statement->execute([$matchId]);
    $status = $statement->fetchColumn();

    if (!$status) {
        throw new DomainException('Pertandingan tidak ditemukan.');
    }
    if (!in_array($status, ['ready', 'judged'], true)) {
        throw new DomainException('Soal hanya dapat dibuka setelah pertandingan siap atau penilaian selesai.');
    }

    $statement = $pdo->prepare(
        'UPDATE matches
         SET current_question = current_question + 1, status = \'question_open\', buzzer_team_id = NULL,
             question_opened_at = NOW(6), buzzed_at = NULL
         WHERE id = ?'
    );
    $statement->execute([$matchId]);
    $pdo->commit();
    json_response(['ok' => true, 'message' => 'Bel dibuka. Regu dapat menekan tombol.']);
} catch (DomainException $exception) {
    $pdo->rollBack();
    json_response(['ok' => false, 'message' => $exception->getMessage()], 409);
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}

