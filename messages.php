<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require_admin();
$pageTitle = 'Contact messages';
$rootPrefix = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('error', 'Your form session expired. Reload and try again.');
    } elseif (($_POST['action'] ?? '') === 'delete' && ($id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT))) {
        $delete = db()->prepare('DELETE FROM tc_messages WHERE id = ?');
        $delete->execute([$id]);
        set_flash('success', 'Message deleted.');
    }
    header('Location: messages.php', true, 303);
    exit;
}
$flash = take_flash();
$stmt = db()->query('SELECT m.id, m.name, m.email, m.message, m.created_at, s.name AS specimen_name FROM tc_messages m LEFT JOIN `' . SPECIMEN_TABLE . '` s ON s.id = m.specimen_id ORDER BY m.created_at DESC, m.id DESC LIMIT 200');
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="Review messages sent to Terra Cabinet."><title>Contact messages | Terra Cabinet</title><link rel="stylesheet" href="styles.css"></head><body>
<?php require __DIR__ . '/includes/header.php'; ?>
<main><section class="page-content message-page"><span class="kicker">Catalogue management</span><h1>Contact messages</h1><p>Messages are stored in the class database. Reply using the sender's email address.</p>
<?php if ($flash): ?><p class="form-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
<?php if (!$messages): ?><p>No messages yet.</p><?php endif; ?>
<?php foreach ($messages as $message): ?><article class="message-card"><div class="message-meta"><h2><?= e($message['name']) ?></h2><time datetime="<?= e(date(DATE_ATOM, strtotime($message['created_at']))) ?>"><?= e(date('M j, Y · g:i a', strtotime($message['created_at']))) ?></time></div><p><a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a><?php if ($message['specimen_name']): ?> · About <?= e($message['specimen_name']) ?><?php endif; ?></p><p class="message-body"><?= nl2br(e($message['message'])) ?></p><form method="post" onsubmit="return confirm('Delete this message?');"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$message['id'] ?>"><button class="manage-link delete-link" type="submit">Delete message</button></form></article><?php endforeach; ?>
</section></main>
<?php require __DIR__ . '/includes/footer.php'; ?></body></html>
