<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));
        $date = trim((string) ($_POST['event_date'] ?? ''));

        if ($name === '') {
            flash('error', 'Nama kegiatan wajib diisi.');
        } else {
            $statement = db()->prepare('INSERT INTO events (name, location, event_date) VALUES (?, ?, ?)');
            $statement->execute([$name, $location ?: null, $date ?: null]);
            $eventId = (int) db()->lastInsertId();
            set_active_event($eventId);
            flash('success', 'Kegiatan dibuat dan dipilih sebagai kegiatan aktif.');
        }
        redirect('/admin/events.php');
    }

    $eventId = (int) ($_POST['event_id'] ?? 0);
    if ($eventId < 1) {
        flash('error', 'Kegiatan tidak valid.');
        redirect('/admin/events.php');
    }

    if ($action === 'select') {
        $statement = db()->prepare('SELECT id FROM events WHERE id = ?');
        $statement->execute([$eventId]);
        if ($statement->fetchColumn()) {
            set_active_event($eventId);
            flash('success', 'Kegiatan aktif diperbarui.');
        }
    } elseif ($action === 'delete') {
        $statement = db()->prepare('DELETE FROM events WHERE id = ?');
        try {
            $statement->execute([$eventId]);
            if (active_event_id() === $eventId) {
                unset($_SESSION['event_id']);
            }
            flash('success', 'Kegiatan dihapus.');
        } catch (PDOException) {
            flash('error', 'Kegiatan yang sudah memiliki pertandingan tidak dapat dihapus.');
        }
    }

    redirect('/admin/events.php');
}

$events = db()->query(
    'SELECT e.*,
            (SELECT COUNT(*) FROM teams t WHERE t.event_id = e.id) AS team_count,
            (SELECT COUNT(*) FROM rounds r WHERE r.event_id = e.id) AS round_count
     FROM events e ORDER BY e.id DESC'
)->fetchAll();

$pageTitle = 'Kegiatan';
require __DIR__ . '/../includes/header.php';
?>
<header class="page-heading split-heading">
    <div>
        <span class="eyebrow">Persiapan lomba</span>
        <h1>Kegiatan</h1>
        <p>Pilih satu kegiatan aktif sebelum mengatur regu dan babak.</p>
    </div>
</header>

<div class="content-grid content-grid-form">
    <section class="panel" aria-labelledby="event-form-title">
        <h2 id="event-form-title">Buat kegiatan</h2>
        <form class="stack-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create">
            <label>
                <span>Nama kegiatan</span>
                <input type="text" name="name" required>
            </label>
            <label>
                <span>Lokasi</span>
                <input type="text" name="location">
            </label>
            <label>
                <span>Tanggal</span>
                <input type="date" name="event_date">
            </label>
            <button class="button button-primary" type="submit">Simpan kegiatan</button>
        </form>
    </section>

    <section class="panel panel-wide" aria-labelledby="event-list-title">
        <div class="section-heading">
            <div>
                <h2 id="event-list-title">Daftar kegiatan</h2>
                <p>Pilih kegiatan yang sedang disiapkan atau dijalankan.</p>
            </div>
        </div>
        <?php if (!$events): ?>
            <div class="empty-state">
                <h3>Belum ada kegiatan</h3>
                <p>Isi formulir di samping untuk membuat kegiatan pertama.</p>
            </div>
        <?php else: ?>
            <div class="record-list">
                <?php foreach ($events as $event): ?>
                    <article class="record-row <?= active_event_id() === (int) $event['id'] ? 'is-active' : '' ?>">
                        <div>
                            <div class="record-title-line">
                                <h3><?= e($event['name']) ?></h3>
                                <?php if (active_event_id() === (int) $event['id']): ?>
                                    <span class="status-label">Aktif</span>
                                <?php endif; ?>
                            </div>
                            <p><?= e($event['location'] ?: 'Lokasi belum diisi') ?></p>
                            <small><?= (int) $event['team_count'] ?> regu, <?= (int) $event['round_count'] ?> babak</small>
                        </div>
                        <div class="record-actions">
                            <?php if (active_event_id() !== (int) $event['id']): ?>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="select">
                                    <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                    <button class="button button-secondary" type="submit">Pilih kegiatan</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" data-confirm="Hapus kegiatan ini beserta regu dan babaknya?">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                <button class="button button-danger-quiet" type="submit">Hapus</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

