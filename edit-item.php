<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require __DIR__ . '/image-helper.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('A valid specimen ID is required.'); }
$find = db()->prepare('SELECT * FROM `' . SPECIMEN_TABLE . '` WHERE id = ?');
$find->execute([$id]);
$item = $find->fetch();
if (!$item) { http_response_code(404); exit('Specimen not found.'); }
$original = $item; // untouched copy: used for the current image, old slug, and cleanup

$categories = all_categories();
$selectedCategoryIds = specimen_category_ids((int)$id);
$limits = ['name' => 150, 'summary' => 255, 'formula' => 255, 'hardness' => 100, 'locality' => 150];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) $errors[] = 'Your form session expired. Reload and try again.';

    $fields = [];
    foreach (['name', 'summary', 'description', 'formula', 'hardness', 'locality'] as $field) {
        $fields[$field] = trim((string)($_POST[$field] ?? ''));
    }
    $selectedCategoryIds = array_map('intval', (array)($_POST['categories'] ?? []));
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);

    if ($fields['name'] === '' || $fields['summary'] === '' || $fields['description'] === '' || $fields['locality'] === '') $errors[] = 'Fill in all required fields.';
    foreach ($limits as $field => $max) {
        if (mb_strlen($fields[$field]) > $max) $errors[] = ucfirst($field) . " must be $max characters or fewer.";
    }
    $validCategoryIds = array_map('intval', array_column($categories, 'id'));
    if (!$selectedCategoryIds || array_diff($selectedCategoryIds, $validCategoryIds)) $errors[] = 'Choose at least one valid category.';
    if ($price === false || $price < 0 || $price > 99999999) $errors[] = 'Enter a valid price (0 or more).';

    // Optional replacement photo: same crop/resize as the add form.
    $newImage = $original['image'];
    $file = $_FILES['image'] ?? null;
    if (!$errors && $file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = save_specimen_image($file);
        if (isset($result['error'])) $errors[] = $result['error'];
        else $newImage = $result['path'];
    }

    if (!$errors) {
        // Keep the item's URL stable unless its name actually changed.
        $slug = (string)$original['slug'];
        if ($fields['name'] !== (string)$original['name']) {
            $slugBase = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $fields['name']), '-')) ?: 'specimen';
            $slug = $slugBase;
            $check = db()->prepare('SELECT COUNT(*) FROM `' . SPECIMEN_TABLE . '` WHERE slug = ? AND id <> ?');
            for ($suffix = 2; ; $suffix++) { $check->execute([$slug, $id]); if ((int)$check->fetchColumn() === 0) break; $slug = $slugBase . '-' . $suffix; }
        }

        $saved = false;
        $pdo = null;
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $update = $pdo->prepare('UPDATE `' . SPECIMEN_TABLE . '` SET slug=?,name=?,summary=?,description=?,formula=?,hardness=?,locality=?,price=?,image=? WHERE id=?');
            $update->execute([$slug,$fields['name'],$fields['summary'],$fields['description'],$fields['formula'],$fields['hardness'],$fields['locality'],$price,$newImage,$id]);
            save_specimen_categories($id, $selectedCategoryIds);
            $pdo->commit();
            $saved = true;
        } catch (Throwable $e) {
            error_log('Terra Cabinet edit error: ' . $e->getMessage());
            if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Could not save your changes. Please try again.';
            if ($newImage !== $original['image']) @unlink(__DIR__ . '/' . $newImage); // don't keep the unused new photo
        }

        if ($saved) {
            // Delete the replaced photo, but only if it lives in uploads/ (never the seeded images/).
            if ($newImage !== $original['image']) {
                $old = realpath(__DIR__ . '/' . ltrim((string)$original['image'], '/'));
                $uploads = realpath(__DIR__ . '/uploads');
                if ($old && $uploads && str_starts_with($old, $uploads . DIRECTORY_SEPARATOR)) @unlink($old);
            }
            header('Location: detail.php?slug=' . rawurlencode($slug), true, 303); exit;
        }
    }

    // Show what the user typed again after an error.
    foreach ($fields as $key => $value) $item[$key] = $value;
    $item['price'] = (string)($_POST['price'] ?? $original['price']);
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Edit <?= e((string)$original['name']) ?> | Terra Cabinet</title><link rel="stylesheet" href="styles.css"></head><body>
<header class="site-header"><a class="logo" href="index.php">Terra Cabinet</a><nav><ul><li><a href="index.php">Home</a></li><li><a href="about.php">About</a></li><li><a href="contact.php">Contact</a></li><li><a href="add-item.php">Add a specimen</a></li></ul></nav></header>
<main><section class="page-content add-form-page"><span class="kicker">Catalogue management</span><h1>Edit a specimen</h1><p>Change the specimen details below, then save your updates. Upload a replacement image only if you want to change the current photo; it will be cropped to the same 4:3 format as the others.</p><?php foreach ($errors as $error): ?><p class="form-error"><?= e($error) ?></p><?php endforeach; ?>
<form class="specimen-form" method="post" enctype="multipart/form-data"><input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<label>Name *<input name="name" required maxlength="150" value="<?= e((string)$item['name']) ?>"></label>
<fieldset class="category-choices"><legend>Categories *</legend><?php foreach ($categories as $category): ?><label><input type="checkbox" name="categories[]" value="<?= (int)$category['id'] ?>" <?= in_array((int)$category['id'], $selectedCategoryIds, true) ? 'checked' : '' ?>> <?= e($category['name']) ?></label><?php endforeach; ?><a class="manage-link" href="manage-categories.php">Manage categories</a></fieldset>
<label>Short summary *<input name="summary" required maxlength="255" value="<?= e((string)$item['summary']) ?>"></label>
<label>Description *<textarea name="description" rows="5" required><?= e((string)$item['description']) ?></textarea></label>
<label>Formula<input name="formula" maxlength="255" value="<?= e((string)$item['formula']) ?>"></label>
<label>Hardness<input name="hardness" maxlength="100" value="<?= e((string)$item['hardness']) ?>"></label>
<label>Locality *<input name="locality" required maxlength="150" value="<?= e((string)$item['locality']) ?>"></label>
<label>Price (₱) *<input type="number" name="price" min="0" step="0.01" required value="<?= e((string)$item['price']) ?>"></label>
<p class="current-image">Current image: <img src="<?= e(ltrim((string)$original['image'], '/')) ?>" alt="<?= e((string)$original['name']) ?>"></p>
<label>Replace image (optional; JPG, PNG, WEBP, GIF; max 5 MB)<input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"></label>
<button class="btn" type="submit">Update specimen</button> <a class="manage-link" href="detail.php?slug=<?= rawurlencode((string)$original['slug']) ?>">Cancel</a></form></section></main><script src="image-optimizer.js" defer></script></body></html>
