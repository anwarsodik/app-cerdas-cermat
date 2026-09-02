<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'CCI An Nuur';
$bodyClass = $bodyClass ?? '';
$flashMessage = pull_flash();
$activeEvent = user() ? active_event() : null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= e($pageTitle) ?> | CCI An Nuur</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="<?= e($bodyClass) ?>">
<?php if (user()): ?>
    <header class="app-header">
        <a class="brand" href="/operator.php" aria-label="CCI An Nuur, konsol operator">
            <span class="brand-mark" aria-hidden="true">CCI</span>
            <span>CCI An Nuur</span>
        </a>
        <nav class="main-nav" aria-label="Navigasi utama">
            <a href="/operator.php">Pertandingan</a>
            <a href="/admin/events.php">Kegiatan</a>
            <a href="/admin/teams.php">Regu</a>
            <a href="/admin/rounds.php">Babak</a>
        </nav>
        <div class="header-context">
            <?php if ($activeEvent): ?>
                <span class="event-context"><?= e($activeEvent['name']) ?></span>
            <?php endif; ?>
            <a class="text-link" href="/logout.php">Keluar</a>
        </div>
    </header>
<?php endif; ?>
<main class="page-shell">
    <?php if ($flashMessage): ?>
        <div class="notice notice-<?= e($flashMessage['type']) ?>" role="status">
            <?= e($flashMessage['message']) ?>
        </div>
    <?php endif; ?>

