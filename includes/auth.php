<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function require_auth(bool $json = false): void
{
    if (user()) {
        return;
    }

    if ($json) {
        json_response(['ok' => false, 'message' => 'Sesi operator telah berakhir.'], 401);
    }

    flash('error', 'Silakan masuk sebagai operator.');
    redirect('/login.php');
}

