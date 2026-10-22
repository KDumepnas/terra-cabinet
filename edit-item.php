<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('A valid specimen ID is required.'); }
$find = db()->prepare('SELECT * FROM `' . SPECIMEN_TABLE . '` WHERE id = ?');
$find->execute([$id]);
$item = $find->fetch();
if (!$item) { http_response_code(404); exit('Specimen not found.'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) $errors[] = 'Your form session expired. Reload and try again.';
    $fields = [];
    foreach (['name','category','summary','description','formula','hardness','locality'] as $field) $fields[$field] = trim((string)($_POST[$field] ?? ''));
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($fields['name'] === '' || $fields['category'] === '' || $fields['summary'] === '' || $fields['description'] === '' || $fields['locality'] === '') $errors[] = 'Fill in all required fields.';
    if ($price === false || $price < 0) $errors[] = 'Enter a valid non-negative price.';
    $newImage = $item['image'];
    $file = $_FILES['image'] ?? null;
    if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($file['error'] !== UPLOAD_ERR_OK) $errors[] = 'The image upload failed.';
        elseif ($file['size'] > 5 * 1024 * 1024) $errors[] = 'Image must be 5 MB or smaller.';
        else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
            if (!isset($extensions[$mime])) $errors[] = 'Upload a JPG, PNG, WEBP, or GIF image.';
            elseif (!$errors) {
                $dir = __DIR__ . '/uploads';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) $errors[] = 'Could not save the uploaded image.';
                else $newImage = 'uploads/' . $filename;
            }
        }
    }
    if (!$errors) {
        $slugBase = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $fields['name']), '-')) ?: 'specimen';
        $slug = $slugBase;
        $check = db()->prepare('SELECT COUNT(*) FROM `' . SPECIMEN_TABLE . '` WHERE slug = ? AND id <> ?');
        for ($suffix = 2; ; $suffix++) { $check->execute([$slug, $id]); if ((int)$check->fetchColumn() === 0) break; $slug = $slugBase . '-' . $suffix; }
        $update = db()->prepare('UPDATE `' . SPECIMEN_TABLE . '` SET slug=?,name=?,category=?,summary=?,description=?,formula=?,hardness=?,locality=?,price=?,image=? WHERE id=?');
        $update->execute([$slug,$fields['name'],$fields['category'],$fields['summary'],$fields['description'],$fields['formula'],$fields['hardness'],$fields['locality'],$price,$newImage,$id]);
        if ($newImage !== $item['image']) {
            $old = realpath(__DIR__ . '/' . ltrim($item['image'], '/'));
            $uploads = realpath(__DIR__ . '/uploads');
            if ($old && $uploads && str_starts_with($old, $uploads . DIRECTORY_SEPARATOR)) @unlink($old);
        }
        header('Location: detail.php?slug=' . rawurlencode($slug), true, 303); exit;
    }
    foreach ($fields as $key => $value) $item[$key] = $value;
    $item['price'] = (string)($_POST['price'] ?? $item['price']);
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Edit <?= e($item['name']) ?> | Terra Cabinet</title><link rel="stylesheet" href="styles.css"></head><body>
<header class="site-header"><a class="logo" href="index.php">Terra Cabinet</a><nav><ul><li><a href="index.php">Collection</a></li><li><a href="add-item.php">Add a specimen</a></li></ul></nav></header>
<main><section class="page-content add-form-page"><span class="kicker">Catalogue management</span><h1>Edit a specimen</h1><p>Change the specimen details below, then save your updates. Upload a replacement image only if you want to change the current photo.</p><?php foreach ($errors as $error): ?><p class="form-error"><?= e($error) ?></p><?php endforeach; ?>
<form class="specimen-form" method="post" enctype="multipart/form-data"><input type="hidden" name="id" value="<?= (int)$id ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<label>Name *<input name="name" required maxlength="150" value="<?= e($item['name']) ?>"></label>
<label>Category *<select name="category" required><?php $categories = ['Quartz Family','Raw & Rough','Tumbled & Polished','Other']; if (!in_array($item['category'], $categories, true)) array_unshift($categories, $item['category']); foreach ($categories as $option): ?><option value="<?= e($option) ?>" <?= $item['category'] === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></label>
<label>Short summary *<input name="summary" required maxlength="255" value="<?= e($item['summary']) ?>"></label>
<label>Description *<textarea name="description" rows="5" required><?= e($item['description']) ?></textarea></label>
<label>Formula<input name="formula" maxlength="255" value="<?= e($item['formula']) ?>"></label>
<label>Hardness<input name="hardness" maxlength="100" value="<?= e($item['hardness']) ?>"></label>
<label>Locality *<input name="locality" required maxlength="150" value="<?= e($item['locality']) ?>"></label>
<label>Price (₱) *<input type="number" name="price" min="0" step="0.01" required value="<?= e((string)$item['price']) ?>"></label>
<p class="current-image">Current image: <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>"></p>
<label>Replace image (optional; JPG, PNG, WEBP, GIF; max 5 MB)<input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"></label>
<button class="btn" type="submit">Update specimen</button> <a class="manage-link" href="detail.php?slug=<?= rawurlencode($item['slug']) ?>">Cancel</a></form></section></main><script src="image-optimizer.js" defer></script></body></html>
