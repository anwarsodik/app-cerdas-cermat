<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_auth(true);
verify_csrf();

$data = request_json();
$matchId = (int) ($data['match_id'] ?? 0);

$statement = db()->prepare(
    'UPDATE matches SET status = \'finished\', finished_at = NOW() WHERE id = ? AND status <> \'finished\''
);
$statement->execute([$matchId]);

if ($statement->rowCount() < 1) {
    json_response(['ok' => false, 'message' => 'Pertandingan sudah selesai atau tidak ditemukan.'], 409);
}

json_response(['ok' => true, 'message' => 'Pertandingan diakhiri. Hasil akhir tersimpan.']);

