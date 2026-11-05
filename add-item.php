<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require_admin();
require __DIR__ . '/image-helper.php';
$pageTitle = 'Add a specimen'; $rootPrefix = '';

$categories = all_categories();
$categoryIds = array_map('intval', (array)($_POST['categories'] ?? []));
$limits = ['name' => 150, 'summary' => 255, 'formula' => 255, 'hardness' => 100, 'locality' => 150];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) $errors[] = 'Your form session expired. Reload and try again.';

    $name = trim((string)($_POST['name'] ?? ''));
    $summary = trim((string)($_POST['summary'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $formula = trim((string)($_POST['formula'] ?? ''));
    $hardness = trim((string)($_POST['hardness'] ?? ''));
    $locality = trim((string)($_POST['locality'] ?? ''));
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);

    if ($name === '' || $summary === '' || $description === '' || $locality === '') $errors[] = 'Fill in all required fields.';
    $checked = ['name' => $name, 'summary' => $summary, 'formula' => $formula, 'hardness' => $hardness, 'locality' => $locality];
    foreach ($limits as $field => $max) {
        if (mb_strlen($checked[$field]) > $max) $errors[] = ucfirst($field) . " must be $max characters or fewer.";
    }
    $validCategoryIds = array_column($categories, 'id');
    if (!$categoryIds || array_diff($categoryIds, array_map('intval', $validCategoryIds))) $errors[] = 'Choose at least one valid category.';
    if ($price === false || $price < 0 || $price > 99999999) $errors[] = 'Enter a valid price (0 or more).';

    // Photo: required when adding. Only processed once the rest of the form is valid.
    $imagePath = '';
    $file = $_FILES['image'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Choose an image to upload.';
    } elseif (!$errors) {
        $result = save_specimen_image($file);
        if (isset($result['error'])) $errors[] = $result['error'];
        else $imagePath = $result['path'];
    }

    if (!$errors) {
        $pdo = null;
        try {
            $slugBase = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $name), '-')) ?: 'specimen';
            $slug = $slugBase;
            $check = db()->prepare('SELECT COUNT(*) FROM `' . SPECIMEN_TABLE . '` WHERE slug = ?');
            for ($suffix = 2; ; $suffix++) { $check->execute([$slug]); if ((int)$check->fetchColumn() === 0) break; $slug = $slugBase . '-' . $suffix; }
            $next = (int)db()->query('SELECT COALESCE(MAX(id), 0) + 1 FROM `' . SPECIMEN_TABLE . '`')->fetchColumn();
            $catalogueNo = str_pad((string)$next, 3, '0', STR_PAD_LEFT);
            $pdo = db();
            $pdo->beginTransaction();
            $insert = $pdo->prepare('INSERT INTO `' . SPECIMEN_TABLE . '` (catalogue_no,slug,name,summary,description,formula,hardness,locality,price,image) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $insert->execute([$catalogueNo,$slug,$name,$summary,$description,$formula,$hardness,$locality,$price,$imagePath]);
            save_specimen_categories((int)$pdo->lastInsertId(), $categoryIds);
            $pdo->commit();
            header('Location: detail.php?slug=' . rawurlencode($slug), true, 303); exit;
        } catch (Throwable $error) {
            error_log('Terra Cabinet add error: ' . $error->getMessage());
            if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
            // Don't leave an orphaned photo behind when the database save fails.
            if ($imagePath !== '') @unlink(__DIR__ . '/' . $imagePath);
            $errors[] = 'Could not save the specimen. Check the database setup and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Add a specimen | Terra Cabinet</title><link rel="stylesheet" href="styles.css"></head><body>
<header class="site-header"><a class="logo" href="index.php">Terra Cabinet</a><nav><ul><li><a href="index.php">Home</a></li><li><a href="about.php">About</a></li><li><a href="contact.php">Contact</a></li><li><a href="add-item.php" aria-current="page">Add a specimen</a></li></ul></nav></header>
<main><section class="page-content add-form-page">
    <span class="kicker">Grow the collection</span><h1>Add a specimen</h1>
    <p>Enter the specimen details and upload a photo. Images are automatically center-cropped to fit the 4:3 collection format.</p>
    <?php foreach ($errors as $error): ?><p class="form-error"><?= e($error) ?></p><?php endforeach; ?>
    <form class="specimen-form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>Name *<input name="name" required maxlength="150" value="<?= e($_POST['name'] ?? '') ?>"></label>
        <fieldset class="category-choices"><legend>Categories *</legend><?php foreach ($categories as $category): ?><label><input type="checkbox" name="categories[]" value="<?= (int)$category['id'] ?>" <?= in_array((int)$category['id'], $categoryIds, true) ? 'checked' : '' ?>> <?= e($category['name']) ?></label><?php endforeach; ?><a class="manage-link" href="manage-categories.php">Manage categories</a></fieldset>
        <label>Short summary *<input name="summary" required maxlength="255" value="<?= e($_POST['summary'] ?? '') ?>"></label>
        <label>Description *<textarea name="description" rows="5" required><?= e($_POST['description'] ?? '') ?></textarea></label>
        <label>Formula<input name="formula" maxlength="255" value="<?= e($_POST['formula'] ?? '') ?>"></label>
        <label>Hardness<input name="hardness" maxlength="100" value="<?= e($_POST['hardness'] ?? '') ?>"></label>
        <label>Locality *<input name="locality" required maxlength="150" value="<?= e($_POST['locality'] ?? '') ?>"></label>
        <label>Price (₱) *<input type="number" name="price" min="0" step="0.01" required value="<?= e($_POST['price'] ?? '') ?>"></label>
        <label>Specimen image (JPG, PNG, WEBP, GIF; max 5 MB) *<input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" required></label>
        <button class="btn" type="submit">Save specimen</button>
    </form>
</section></main>
<footer class="site-footer"><div class="footer-content"><div class="footer-brand"><h3>Terra Cabinet</h3><p>A small, hand-catalogued mineral collection.</p></div><nav class="footer-nav"><a href="index.php">Home</a><a href="about.php">About</a><a href="contact.php">Contact</a><a href="add-item.php" aria-current="page">Add a specimen</a></nav></div><p class="footer-note">&copy; <?= date('Y') ?> Terra Cabinet. Built for ITWM132.</p></footer><script src="image-optimizer.js" defer></script></body></html>
