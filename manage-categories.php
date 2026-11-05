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
                set_flash('success', 'Category added.');
                header('Location: manage-categories.php', true, 303);
                exit;
            } catch (PDOException $e) {
                $errors[] = $e->getCode() === '23000' ? 'That category already exists.' : 'Could not save the category.';
                if ($e->getCode() !== '23000') error_log('Terra Cabinet category add error: ' . $e->getMessage());
            }
        }
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        if (!$categoryId) $errors[] = 'Choose a valid category.';
        else {
            $count = db()->prepare('SELECT COUNT(*) FROM `' . SPECIMEN_CATEGORY_TABLE . '` WHERE category_id = ?');
            $count->execute([$categoryId]);
            $assignedCount = (int)$count->fetchColumn();
            $stmt = db()->prepare('DELETE FROM `' . CATEGORY_TABLE . '` WHERE id = ?');
            $stmt->execute([$categoryId]);
            set_flash('success', 'Category deleted. Its links were removed from ' . $assignedCount . ' specimen' . ($assignedCount === 1 ? '' : 's') . '.');
            header('Location: manage-categories.php', true, 303);
            exit;
        }
    }
}
$categories = all_categories();
$categoryCountStmt = db()->query('SELECT category_id, COUNT(*) AS total FROM `' . SPECIMEN_CATEGORY_TABLE . '` GROUP BY category_id');
$categoryCounts = [];
foreach ($categoryCountStmt->fetchAll() as $row) $categoryCounts[(int)$row['category_id']] = (int)$row['total'];
$flash = take_flash();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="Terra Cabinet specimen catalogue and mineral collection."><title>Manage categories | Terra Cabinet</title><link rel="stylesheet" href="styles.css"></head><body>
<?php require (__DIR__ . '/includes/header.php'); ?>
<main><section class="page-content add-form-page"><span class="kicker">Catalogue management</span><h1>Manage categories</h1><p>Categories can be shared by many specimens. Select more than one when adding or editing a listing.</p>
<?php foreach ($errors as $error): ?><p class="form-error"><?= e($error) ?></p><?php endforeach; ?>
<?php if ($flash): ?><p class="form-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
<form class="specimen-form" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="add"><label>New category<input name="name" maxlength="100" required></label><button class="btn" type="submit">Add category</button></form>
<h2>Current categories</h2><ul class="category-list"><?php foreach ($categories as $category): $assignedCount = $categoryCounts[(int)$category['id']] ?? 0; ?><li><?= e($category['name']) ?> <span class="category-count"><?= $assignedCount ?> specimen<?= $assignedCount === 1 ? '' : 's' ?></span><form method="post" onsubmit="return confirm('Delete this category and remove its links from <?= $assignedCount ?> specimen<?= $assignedCount === 1 ? '' : 's' ?>?');"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="category_id" value="<?= (int)$category['id'] ?>"><button class="manage-link delete-link" type="submit">Delete</button></form></li><?php endforeach; ?></ul>
<a class="manage-link" href="add-item.php">Return to add specimen</a></section></main>
<?php require (__DIR__ . '/includes/footer.php'); ?></body></html>


