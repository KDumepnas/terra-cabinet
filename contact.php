<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
$pageTitle = 'Contact';
$rootPrefix = '';
$errors = [];
$flash = take_flash();
$specimens = db()->query('SELECT id, name FROM `' . SPECIMEN_TABLE . '` ORDER BY name')->fetchAll();
$selectedSpecimen = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    $selectedSpecimen = trim((string)($_POST['specimen_id'] ?? ''));
    if (!verify_csrf_token()) $errors[] = 'Your form session expired. Reload and try again.';
    if ((string)($_POST['website'] ?? '') !== '') $errors[] = 'The message could not be accepted.';
    if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Enter your name (up to 100 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) $errors[] = 'Enter a valid email address.';
    if ($message === '' || mb_strlen($message) > 3000) $errors[] = 'Enter a message up to 3,000 characters.';

    $specimenId = null;
    if ($selectedSpecimen !== '') {
        $specimenId = filter_var($selectedSpecimen, FILTER_VALIDATE_INT);
        if (!$specimenId || !in_array((int)$specimenId, array_map(static fn($item) => (int)$item['id'], $specimens), true)) {
            $errors[] = 'Choose a valid specimen or leave the field blank.';
        }
    }

    if (!$errors) {
        $save = db()->prepare('INSERT INTO tc_messages (name, email, message, specimen_id) VALUES (?, ?, ?, ?)');
        $save->execute([$name, $email, $message, $specimenId]);
        set_flash('success', 'Your message has been saved. We will get back to you by email.');
        header('Location: contact.php', true, 303);
        exit;
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="Contact Terra Cabinet about a specimen or mineral listing."><title>Contact | Terra Cabinet</title><link rel="stylesheet" href="styles.css"></head><body>
<?php require __DIR__ . '/includes/header.php'; ?>
<main><section class="page-content">
    <span class="kicker">Reach the cabinet</span><h1>Get in touch</h1>
    <p>Have a question about a specimen, or want to ask about a piece not currently listed? Send a note using the form below.</p>
    <?php if ($flash): ?><p class="form-success" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
    <?php foreach ($errors as $error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endforeach; ?>
    <form class="specimen-form contact-form" method="post" action="contact.php">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label class="honeypot" aria-hidden="true">Leave this field empty<input name="website" tabindex="-1" autocomplete="off"></label>
        <label>Your name *<input name="name" required maxlength="100" autocomplete="name" value="<?= e($_POST['name'] ?? '') ?>"></label>
        <label>Email address *<input type="email" name="email" required maxlength="254" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>"></label>
        <label>Specimen (optional)<select name="specimen_id"><option value="">General question</option><?php foreach ($specimens as $specimen): ?><option value="<?= (int)$specimen['id'] ?>" <?= $selectedSpecimen === (string)$specimen['id'] ? 'selected' : '' ?>><?= e($specimen['name']) ?></option><?php endforeach; ?></select></label>
        <label>Message *<textarea name="message" rows="6" maxlength="3000" required><?= e($_POST['message'] ?? '') ?></textarea></label>
        <button class="btn" type="submit">Send message</button>
    </form>
    <dl class="specs contact-specs"><div class="spec-row"><dt>Email</dt><dd><a href="mailto:terracabinet@gmail.com">terracabinet@gmail.com</a></dd></div><div class="spec-row"><dt>Location</dt><dd>Lubas, La Trinidad, Benguet, Philippines</dd></div><div class="spec-row"><dt>Response time</dt><dd>Within 2–3 days</dd></div></dl>
</section></main>
<?php require __DIR__ . '/includes/footer.php'; ?></body></html>
