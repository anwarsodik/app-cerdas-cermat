<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_auth();

$event = active_event();
if (!$event) {
    flash('error', 'Pilih atau buat kegiatan terlebih dahulu.');
    redirect('/admin/events.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save') {
        $teamId = (int) ($_POST['team_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $members = trim((string) ($_POST['members'] ?? ''));
        $code = strtoupper(trim((string) ($_POST['access_code'] ?? '')));

        if ($name === '' || $code === '') {
            flash('error', 'Nama regu dan kode akses wajib diisi.');
        } elseif (!preg_match('/^[A-Z0-9]{3,12}$/', $code)) {
            flash('error', 'Kode akses harus terdiri dari 3 sampai 12 huruf atau angka.');
        } else {
            try {
                if ($teamId > 0) {
                    $statement = db()->prepare(
                        'UPDATE teams SET name = ?, members = ?, access_code = ? WHERE id = ? AND event_id = ?'
                    );
                    $statement->execute([$name, $members ?: null, $code, $teamId, $event['id']]);
                    flash('success', 'Data regu diperbarui.');
                } else {
                    $statement = db()->prepare(
                        'INSERT INTO teams (event_id, name, members, access_code) VALUES (?, ?, ?, ?)'
                    );
                    $statement->execute([$event['id'], $name, $members ?: null, $code]);
                    flash('success', 'Regu ditambahkan.');
                }
            } catch (PDOException $exception) {
                if ((string) $exception->getCode() === '23000') {
                    flash('error', 'Kode akses sudah dipakai oleh regu lain.');
                } else {
                    throw $exception;
                }
            }
        }
        redirect('/admin/teams.php');
    }

    $teamId = (int) ($_POST['team_id'] ?? 0);
    if ($action === 'toggle') {
        $statement = db()->prepare(
            'UPDATE teams SET is_active = IF(is_active = 1, 0, 1) WHERE id = ? AND event_id = ?'
        );
        $statement->execute([$teamId, $event['id']]);
        flash('success', 'Status regu diperbarui.');
    } elseif ($action === 'delete') {
        try {
            $statement = db()->prepare('DELETE FROM teams WHERE id = ? AND event_id = ?');
            $statement->execute([$teamId, $event['id']]);
            flash('success', 'Regu dihapus.');
        } catch (PDOException) {
            flash('error', 'Regu yang sudah mengikuti pertandingan tidak dapat dihapus. Nonaktifkan regu sebagai gantinya.');
        }
    }
    redirect('/admin/teams.php');
}

$editTeam = null;
if (isset($_GET['edit'])) {
    $statement = db()->prepare('SELECT * FROM teams WHERE id = ? AND event_id = ?');
    $statement->execute([(int) $_GET['edit'], $event['id']]);
    $editTeam = $statement->fetch() ?: null;
}

$statement = db()->prepare('SELECT * FROM teams WHERE event_id = ? ORDER BY is_active DESC, name');
$statement->execute([$event['id']]);
$teams = $statement->fetchAll();

$pageTitle = 'Regu';
require __DIR__ . '/../includes/header.php';
?>
<header class="page-heading">
    <span class="eyebrow">Kegiatan aktif</span>
    <h1>Regu peserta</h1>
    <p><?= e($event['name']) ?>. Kode akses digunakan saat regu membuka halaman bel.</p>
</header>

<div class="content-grid content-grid-form">
    <section class="panel" aria-labelledby="team-form-title">
        <h2 id="team-form-title"><?= $editTeam ? 'Ubah regu' : 'Tambah regu' ?></h2>
        <form class="stack-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="team_id" value="<?= (int) ($editTeam['id'] ?? 0) ?>">
            <label>
                <span>Nama regu</span>
                <input type="text" name="name" value="<?= e($editTeam['name'] ?? '') ?>" required>
            </label>
            <label>
                <span>Nama anggota <small>opsional</small></span>
                <textarea name="members" rows="3"><?= e($editTeam['members'] ?? '') ?></textarea>
            </label>
            <label>
                <span>Kode akses bel</span>
                <input type="text" name="access_code" value="<?= e($editTeam['access_code'] ?? '') ?>" minlength="3" maxlength="12" pattern="[A-Za-z0-9]+" required>
                <small>Gunakan 3 sampai 12 huruf atau angka.</small>
            </label>
            <div class="form-actions">
                <button class="button button-primary" type="submit"><?= $editTeam ? 'Simpan perubahan' : 'Tambahkan regu' ?></button>
                <?php if ($editTeam): ?>
                    <a class="button button-secondary" href="/admin/teams.php">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="panel panel-wide" aria-labelledby="team-list-title">
        <div class="section-heading">
            <div>
                <h2 id="team-list-title">Daftar regu</h2>
                <p>Regu aktif otomatis dimasukkan saat pertandingan dibuat.</p>
            </div>
        </div>
        <?php if (!$teams): ?>
            <div class="empty-state">
                <h3>Belum ada regu</h3>
                <p>Tambahkan minimal dua regu untuk menjalankan simulasi.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Regu</th><th>Kode bel</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                    <tbody>
                    <?php foreach ($teams as $team): ?>
                        <tr class="<?= $team['is_active'] ? '' : 'is-muted' ?>">
                            <td><strong><?= e($team['name']) ?></strong><?php if ($team['members']): ?><small><?= nl2br(e($team['members'])) ?></small><?php endif; ?></td>
                            <td><code><?= e($team['access_code']) ?></code></td>
                            <td><?= $team['is_active'] ? 'Aktif' : 'Nonaktif' ?></td>
                            <td class="table-actions">
                                <a class="text-link" href="?edit=<?= (int) $team['id'] ?>">Ubah</a>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="team_id" value="<?= (int) $team['id'] ?>">
                                    <button class="text-button" type="submit"><?= $team['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                                </form>
                                <form method="post" data-confirm="Hapus regu ini?">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="team_id" value="<?= (int) $team['id'] ?>">
                                    <button class="text-button text-danger" type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

