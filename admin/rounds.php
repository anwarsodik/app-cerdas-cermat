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
        $roundId = (int) ($_POST['round_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $order = filter_input(INPUT_POST, 'display_order', FILTER_VALIDATE_INT);
        $correct = filter_input(INPUT_POST, 'correct_score', FILTER_VALIDATE_INT);
        $wrong = filter_input(INPUT_POST, 'wrong_score', FILTER_VALIDATE_INT);
        $seconds = filter_input(INPUT_POST, 'answer_seconds', FILTER_VALIDATE_INT);

        if ($name === '' || $order === false || $correct === false || $wrong === false || $seconds === false || $order < 1 || $seconds < 1) {
            flash('error', 'Lengkapi aturan babak dengan nilai yang valid.');
        } else {
            if ($roundId > 0) {
                $statement = db()->prepare(
                    'UPDATE rounds SET name = ?, display_order = ?, correct_score = ?, wrong_score = ?, answer_seconds = ? WHERE id = ? AND event_id = ?'
                );
                $statement->execute([$name, $order, $correct, $wrong, $seconds, $roundId, $event['id']]);
                flash('success', 'Babak diperbarui.');
            } else {
                $statement = db()->prepare(
                    'INSERT INTO rounds (event_id, name, display_order, correct_score, wrong_score, answer_seconds) VALUES (?, ?, ?, ?, ?, ?)'
                );
                $statement->execute([$event['id'], $name, $order, $correct, $wrong, $seconds]);
                flash('success', 'Babak ditambahkan.');
            }
        }
        redirect('/admin/rounds.php');
    }

    if ($action === 'delete') {
        try {
            $statement = db()->prepare('DELETE FROM rounds WHERE id = ? AND event_id = ?');
            $statement->execute([(int) ($_POST['round_id'] ?? 0), $event['id']]);
            flash('success', 'Babak dihapus.');
        } catch (PDOException) {
            flash('error', 'Babak yang sudah memiliki pertandingan tidak dapat dihapus.');
        }
        redirect('/admin/rounds.php');
    }
}

$editRound = null;
if (isset($_GET['edit'])) {
    $statement = db()->prepare('SELECT * FROM rounds WHERE id = ? AND event_id = ?');
    $statement->execute([(int) $_GET['edit'], $event['id']]);
    $editRound = $statement->fetch() ?: null;
}

$statement = db()->prepare('SELECT * FROM rounds WHERE event_id = ? ORDER BY display_order, id');
$statement->execute([$event['id']]);
$rounds = $statement->fetchAll();

$pageTitle = 'Babak';
require __DIR__ . '/../includes/header.php';
?>
<header class="page-heading">
    <span class="eyebrow">Kegiatan aktif</span>
    <h1>Babak dan aturan nilai</h1>
    <p><?= e($event['name']) ?>. Nilai benar dan salah digunakan otomatis oleh konsol.</p>
</header>

<div class="content-grid content-grid-form">
    <section class="panel" aria-labelledby="round-form-title">
        <h2 id="round-form-title"><?= $editRound ? 'Ubah babak' : 'Tambah babak' ?></h2>
        <form class="stack-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="round_id" value="<?= (int) ($editRound['id'] ?? 0) ?>">
            <label><span>Nama babak</span><input type="text" name="name" value="<?= e($editRound['name'] ?? '') ?>" required></label>
            <label><span>Urutan</span><input type="number" name="display_order" min="1" value="<?= e((string) ($editRound['display_order'] ?? '')) ?>" required></label>
            <div class="field-pair">
                <label><span>Nilai benar</span><input type="number" name="correct_score" value="<?= e((string) ($editRound['correct_score'] ?? '')) ?>" required></label>
                <label><span>Nilai salah</span><input type="number" name="wrong_score" value="<?= e((string) ($editRound['wrong_score'] ?? '')) ?>" required></label>
            </div>
            <label><span>Waktu menjawab, detik</span><input type="number" name="answer_seconds" min="1" value="<?= e((string) ($editRound['answer_seconds'] ?? '')) ?>" required></label>
            <div class="form-actions">
                <button class="button button-primary" type="submit"><?= $editRound ? 'Simpan perubahan' : 'Tambahkan babak' ?></button>
                <?php if ($editRound): ?><a class="button button-secondary" href="/admin/rounds.php">Batal</a><?php endif; ?>
            </div>
        </form>
    </section>

    <section class="panel panel-wide" aria-labelledby="round-list-title">
        <h2 id="round-list-title">Daftar babak</h2>
        <?php if (!$rounds): ?>
            <div class="empty-state"><h3>Belum ada babak</h3><p>Tambahkan aturan babak sebelum memulai pertandingan.</p></div>
        <?php else: ?>
            <div class="round-list">
                <?php foreach ($rounds as $round): ?>
                    <article class="round-row">
                        <span class="round-order"><?= (int) $round['display_order'] ?></span>
                        <div>
                            <h3><?= e($round['name']) ?></h3>
                            <p>Benar <?= (int) $round['correct_score'] ?>, salah <?= (int) $round['wrong_score'] ?>, waktu <?= (int) $round['answer_seconds'] ?> detik</p>
                        </div>
                        <div class="record-actions">
                            <a class="text-link" href="?edit=<?= (int) $round['id'] ?>">Ubah</a>
                            <form method="post" data-confirm="Hapus babak ini?">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="round_id" value="<?= (int) $round['id'] ?>">
                                <button class="text-button text-danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

