<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) render_error_page(403, 'Request denied. Return to the catalogue and try again.');
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) render_error_page(400, 'A valid specimen ID is required.');
$find = db()->prepare('SELECT image FROM `' . SPECIMEN_TABLE . '` WHERE id = ?');
$find->execute([$id]);
$item = $find->fetch();
if ($item) {
    $unlink = db()->prepare('DELETE FROM `' . SPECIMEN_CATEGORY_TABLE . '` WHERE specimen_id = ?');
    $unlink->execute([$id]);
    $delete = db()->prepare('DELETE FROM `' . SPECIMEN_TABLE . '` WHERE id = ?');
    $delete->execute([$id]);
    $image = realpath(__DIR__ . '/' . ltrim($item['image'], '/'));
    $uploads = realpath(__DIR__ . '/uploads');
    if ($image && $uploads && str_starts_with($image, $uploads . DIRECTORY_SEPARATOR)) @unlink($image);
}
set_flash('success', 'The specimen and its category links were deleted.');
header('Location: index.php?deleted=1#collection', true, 303);
