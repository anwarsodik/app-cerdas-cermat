<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'cerdas_cermat';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        http_response_code(500);
        $isCli = PHP_SAPI === 'cli';
        $message = 'Koneksi database gagal. Periksa konfigurasi MySQL dan impor database/schema.sql.';

        if ($isCli) {
            throw new RuntimeException($message, 0, $exception);
        }

        exit('<!doctype html><html lang="id"><meta charset="utf-8"><title>Database tidak tersedia</title><body style="font-family:system-ui;padding:2rem"><h1>Database tidak tersedia</h1><p>' . $message . '</p></body></html>');
    }

    return $pdo;
}

