<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
$pageTitle = 'Add a specimen'; $rootPrefix = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $summary = trim((string)($_POST['summary'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $formula = trim((string)($_POST['formula'] ?? ''));
    $hardness = trim((string)($_POST['hardness'] ?? ''));
    $locality = trim((string)($_POST['locality'] ?? ''));
    $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($name === '' || $category === '' || $summary === '' || $description === '' || $locality === '') $errors[] = 'Fill in all required fields.';
    if ($price === false || $price < 0) $errors[] = 'Enter a valid non-negative price.';
    $imagePath = '';
    $file = $_FILES['image'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) $errors[] = 'Choose an image to upload.';
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        if ($file['size'] > 5 * 1024 * 1024) $errors[] = 'Image must be 5 MB or smaller.';
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($extensions[$mime])) $errors[] = 'Upload a JPG, PNG, WEBP, or GIF image.';
        if (!$errors) {
            $uploadDir = __DIR__ . '/uploads';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = bin2hex(random_bytes(16)) . '.jpg';
            $targetPath = $uploadDir . '/' . $filename;

            // Center-crop uploads to the site's 4:3 specimen frame, then
            // resize and compress them for consistent display and quick loading.
            $source = false;
            if (function_exists('imagecreatetruecolor')) {
                if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) $source = @imagecreatefromjpeg($file['tmp_name']);
                elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) $source = @imagecreatefrompng($file['tmp_name']);
                elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) $source = @imagecreatefromwebp($file['tmp_name']);
                elseif ($mime === 'image/gif' && function_exists('imagecreatefromgif')) $source = @imagecreatefromgif($file['tmp_name']);
            }

            if ($source) {
                $sourceWidth = imagesx($source);
                $sourceHeight = imagesy($source);
                $targetRatio = 4 / 3;
                $sourceRatio = $sourceWidth / max(1, $sourceHeight);
                if ($sourceRatio > $targetRatio) {
                    $cropHeight = $sourceHeight;
                    $cropWidth = (int)round($sourceHeight * $targetRatio);
                    $cropX = (int)(($sourceWidth - $cropWidth) / 2);
                    $cropY = 0;
                } else {
                    $cropWidth = $sourceWidth;
                    $cropHeight = (int)round($sourceWidth / $targetRatio);
                    $cropX = 0;
                    $cropY = (int)(($sourceHeight - $cropHeight) / 2);
                }
                $scale = min(1, 1200 / $cropWidth, 900 / $cropHeight);
                $width = max(1, (int)round($cropWidth * $scale));
                $height = max(1, (int)round($cropHeight * $scale));
                $resized = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($resized, 255, 255, 255);
                imagefill($resized, 0, 0, $white);
                $saved = imagecopyresampled($resized, $source, 0, 0, $cropX, $cropY, $width, $height, $cropWidth, $cropHeight)
                    && imagejpeg($resized, $targetPath, 84);
                imagedestroy($source);
                imagedestroy($resized);
                if (!$saved) $errors[] = 'The image could not be resized and saved.';
                else $imagePath = 'uploads/' . $filename;
            } else {
                // If GD is unavailable, retain the original supported image.
                $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) $errors[] = 'The image could not be saved.';
                else $imagePath = 'uploads/' . $filename;
            }
        }
    }
    if (!$errors) {
        try {
            $slugBase = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-')) ?: 'specimen';
            $slug = $slugBase;
            $check = db()->prepare('SELECT COUNT(*) FROM `' . SPECIMEN_TABLE . '` WHERE slug = ?');
            for ($suffix = 2; ; $suffix++) { $check->execute([$slug]); if ((int)$check->fetchColumn() === 0) break; $slug = $slugBase . '-' . $suffix; }
            $next = (int)db()->query('SELECT COALESCE(MAX(id), 0) + 1 FROM `' . SPECIMEN_TABLE . '`')->fetchColumn();
            $catalogueNo = str_pad((string)$next, 3, '0', STR_PAD_LEFT);
            $insert = db()->prepare('INSERT INTO `' . SPECIMEN_TABLE . '` (catalogue_no,slug,name,category,summary,description,formula,hardness,locality,price,image) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $insert->execute([$catalogueNo,$slug,$name,$category,$summary,$description,$formula,$hardness,$locality,$price,$imagePath]);
            header('Location: detail.php?slug=' . rawurlencode($slug), true, 303); exit;
        } catch (Throwable $error) { $errors[] = 'Could not save the specimen. Check the database setup and try again.'; }
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
        <label>Name *<input name="name" required maxlength="150" value="<?= e($_POST['name'] ?? '') ?>"></label>
        <label>Category *<select name="category" required><?php foreach (['Quartz Family','Raw & Rough','Tumbled & Polished','Other'] as $option): ?><option value="<?= e($option) ?>" <?= ($_POST['category'] ?? 'Quartz Family') === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></label>
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
