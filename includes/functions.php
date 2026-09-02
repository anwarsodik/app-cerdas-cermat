<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        json_response(['ok' => false, 'message' => 'Sesi formulir tidak valid. Muat ulang halaman.'], 419);
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pull_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function request_json(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return $_POST;
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function active_event_id(): ?int
{
    $value = $_SESSION['event_id'] ?? null;
    return is_numeric($value) ? (int) $value : null;
}

function set_active_event(int $eventId): void
{
    $_SESSION['event_id'] = $eventId;
}

function active_event(): ?array
{
    $eventId = active_event_id();
    if (!$eventId) {
        return null;
    }

    $statement = db()->prepare('SELECT * FROM events WHERE id = ?');
    $statement->execute([$eventId]);
    $event = $statement->fetch();

    return $event ?: null;
}

function current_match_for_event(int $eventId): ?array
{
    $statement = db()->prepare(
        'SELECT m.*, r.name AS round_name, r.correct_score, r.wrong_score, r.answer_seconds,
                e.name AS event_name, e.location
         FROM matches m
         JOIN rounds r ON r.id = m.round_id
         JOIN events e ON e.id = r.event_id
         WHERE r.event_id = ? AND m.status <> \'finished\'
         ORDER BY m.id DESC LIMIT 1'
    );
    $statement->execute([$eventId]);
    $match = $statement->fetch();

    return $match ?: null;
}

function match_payload(int $matchId): ?array
{
    $statement = db()->prepare(
        'SELECT m.*, r.name AS round_name, r.correct_score, r.wrong_score, r.answer_seconds,
                e.name AS event_name, e.location, e.event_date,
                bt.name AS buzzer_team_name
         FROM matches m
         JOIN rounds r ON r.id = m.round_id
         JOIN events e ON e.id = r.event_id
         LEFT JOIN teams bt ON bt.id = m.buzzer_team_id
         WHERE m.id = ?'
    );
    $statement->execute([$matchId]);
    $match = $statement->fetch();

    if (!$match) {
        return null;
    }

    $teamsStatement = db()->prepare(
        'SELECT t.id, t.name, mt.score, mt.display_order
         FROM match_teams mt
         JOIN teams t ON t.id = mt.team_id
         WHERE mt.match_id = ?
         ORDER BY mt.display_order, t.name'
    );
    $teamsStatement->execute([$matchId]);

    $historyStatement = db()->prepare(
        'SELECT se.id, t.name AS team_name, se.delta, se.score_after, se.reason,
                se.created_at, se.reversed_at
         FROM score_events se
         JOIN teams t ON t.id = se.team_id
         WHERE se.match_id = ?
         ORDER BY se.id DESC LIMIT 8'
    );
    $historyStatement->execute([$matchId]);

    return [
        'match' => $match,
        'teams' => $teamsStatement->fetchAll(),
        'history' => $historyStatement->fetchAll(),
    ];
}

function status_label(string $status): string
{
    return match ($status) {
        'ready' => 'Siap dimulai',
        'question_open' => 'Bel dibuka',
        'buzzed' => 'Bel terkunci',
        'judged' => 'Penilaian selesai',
        'finished' => 'Pertandingan selesai',
        default => 'Persiapan',
    };
}

