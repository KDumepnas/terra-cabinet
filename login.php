<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if (is_admin()) {
    header('Location: ' . validated_next_path($_GET['next'] ?? null), true, 303);
    exit;
}

$next = validated_next_path($_GET['next'] ?? $_POST['next'] ?? null);
$error = '';
$configurationReady = $adminUsername !== '' && $adminPasswordHash !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Your form session expired. Reload and try again.';
    } elseif (!$configurationReady) {
        $error = 'Admin login is not configured on this server.';
    } else {
        $attempts = (int)($_SESSION['login_attempts'] ?? 0);
        if ($attempts >= 5) usleep(min(2000000, ($attempts - 4) * 500000));
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if (hash_equals((string)$adminUsername, $username) && password_verify($password, (string)$adminPasswordHash)) {
            session_regenerate_id(true);
            $_SESSION['admin_username'] = (string)$adminUsername;
            unset($_SESSION['login_attempts']);
            header('Location: ' . $next, true, 303);
            exit;
        }
        $_SESSION['login_attempts'] = $attempts + 1;
        $error = 'The username or password was not correct.';
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="Terra Cabinet specimen catalogue and mineral collection."><title>Admin login | Terra Cabinet</title><link rel="stylesheet" href="styles.css"></head><body>
<?php require (__DIR__ . '/includes/header.php'); ?>
<main><section class="page-content add-form-page"><span class="kicker">Catalogue management</span><h1>Admin login</h1><p>Sign in to manage specimens and categories.</p>
<?php if ($error !== ''): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form class="specimen-form" method="post" action="login.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="next" value="<?= e($next) ?>">
<label>Username<input name="username" autocomplete="username" required maxlength="100"></label>
<label>Password<input type="password" name="password" autocomplete="current-password" required></label>
<button class="btn" type="submit">Sign in</button></form></section></main>
<?php require (__DIR__ . '/includes/footer.php'); ?></body></html>

