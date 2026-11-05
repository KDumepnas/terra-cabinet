<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require_admin();

$errors = [];
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Your form session expired. Reload and try again.';
    } elseif (($_POST['action'] ?? '') === 'add') {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Enter a category name up to 100 characters.';
        else {
            try {
                $stmt = db()->prepare('INSERT INTO `' . CATEGORY_TABLE . '` (name) VALUES (?)');
                $stmt->execute([$name]);
                $notice = 'Category added.';
            } catch (PDOException $e) {
                $errors[] = $e->getCode() === '23000' ? 'That category already exists.' : 'Could not save the category.';
                if ($e->getCode() !== '23000') error_log('Terra Cabinet category add error: ' . $e->getMessage());
            }
        }
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        if (!$categoryId) $errors[] = 'Choose a valid category.';
        else {
            $stmt = db()->prepare('DELETE FROM `' . CATEGORY_TABLE . '` WHERE id = ?');
            $stmt->execute([$categoryId]);
            $notice = 'Category deleted. Its specimen links were removed.';
        }
    }
}
$categories = all_categories();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Manage categories | Terra Cabinet</title><link rel="stylesheet" href="styles.css"></head><body>
<header class="site-header"><a class="logo" href="index.php">Terra Cabinet</a><nav><ul><li><a href="index.php">Home</a></li><li><a href="add-item.php">Add a specimen</a></li></ul></nav></header>
<main><section class="page-content add-form-page"><span class="kicker">Catalogue management</span><h1>Manage categories</h1><p>Categories can be shared by many specimens. Select more than one when adding or editing a listing.</p>
<?php foreach ($errors as $error): ?><p class="form-error"><?= e($error) ?></p><?php endforeach; ?>
<?php if ($notice !== ''): ?><p class="form-success"><?= e($notice) ?></p><?php endif; ?>
<form class="specimen-form" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="add"><label>New category<input name="name" maxlength="100" required></label><button class="btn" type="submit">Add category</button></form>
<h2>Current categories</h2><ul class="category-list"><?php foreach ($categories as $category): ?><li><?= e($category['name']) ?><form method="post" onsubmit="return confirm('Delete this category and its specimen associations?');"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="category_id" value="<?= (int)$category['id'] ?>"><button class="manage-link delete-link" type="submit">Delete</button></form></li><?php endforeach; ?></ul>
<a class="manage-link" href="add-item.php">Return to add specimen</a></section></main></body></html>
