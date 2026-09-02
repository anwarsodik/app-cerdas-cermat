<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (user()) {
    redirect('/operator.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $statement = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $statement->execute([$username]);
    $account = $statement->fetch();

    if ($account && password_verify($password, $account['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $account['id'],
            'name' => $account['name'],
            'role' => $account['role'],
        ];
        redirect('/operator.php');
    }

    $error = 'Username atau password tidak cocok.';
}

$pageTitle = 'Masuk Operator';
$bodyClass = 'login-page';
require __DIR__ . '/includes/header.php';
?>
<section class="login-layout" aria-labelledby="login-title">
    <div class="login-intro">
        <span class="eyebrow">Konsol pertandingan</span>
        <h1 id="login-title">Cerdas cermat yang tertib sejak bel pertama.</h1>
        <p>Kelola regu, buka soal, tentukan hak jawab, dan perbarui skor dari satu konsol.</p>
    </div>
    <form class="login-card" method="post" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div>
            <span class="brand-mark brand-mark-large" aria-hidden="true">CCI</span>
            <h2>Masuk sebagai operator</h2>
            <p class="muted">Gunakan akun panitia untuk membuka konsol.</p>
        </div>
        <?php if ($error): ?>
            <div class="notice notice-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <label>
            <span>Username</span>
            <input type="text" name="username" autocomplete="username" required autofocus>
        </label>
        <label>
            <span>Password</span>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="button button-primary button-block" type="submit">Buka konsol pertandingan</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

