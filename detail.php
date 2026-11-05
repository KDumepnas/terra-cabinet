<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
$flash = take_flash();
$slug = (string)($_GET['slug'] ?? '');
$stmt = db()->prepare('SELECT * FROM `' . SPECIMEN_TABLE . '` WHERE slug = ? LIMIT 1');
$stmt->execute([$slug]);
$item = $stmt->fetch();
if (!$item) render_error_page(404, 'That specimen could not be found.');
$categoryStmt = db()->prepare('SELECT c.id, c.name FROM `' . CATEGORY_TABLE . '` c INNER JOIN `' . SPECIMEN_CATEGORY_TABLE . '` sc ON sc.category_id = c.id WHERE sc.specimen_id = ? ORDER BY c.name');
$categoryStmt->execute([(int)$item['id']]);
$itemCategories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="description" content="Terra Cabinet specimen catalogue and mineral collection.">
    <title><?= e($item['name']) ?> | Terra Cabinet</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php require (__DIR__ . '/includes/header.php'); ?>
<main><section class="specimen-detail">
    <?php if ($flash): ?><p class="form-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
    <a href="index.php" class="back-link">&larr; Back to collection</a>
    <div class="detail-grid">
        <div class="detail-image"><img src="<?= e(ltrim($item['image'], '/')) ?>" alt="<?= e($item['name']) ?>"></div>
        <div class="detail-info">
            <div class="catalogue-number">Catalogue No. TC-<?= e($item['catalogue_no']) ?></div>
            <h1><?= e($item['name']) ?></h1>
            <span class="price">₱<?= e(number_format((float)$item['price'], 0)) ?></span>
            <p class="description"><?= e($item['description']) ?></p>
            <dl class="specs">
                <div class="spec-row"><dt>Categories</dt><dd><ul class="detail-category-list"><?php foreach ($itemCategories as $category): ?><li><span><?= e($category['name']) ?></span><?php if (is_admin()): ?><form method="post" action="remove-category-link.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="specimen_id" value="<?= (int)$item['id'] ?>"><input type="hidden" name="category_id" value="<?= (int)$category['id'] ?>"><button class="manage-link delete-link" type="submit" aria-label="Remove <?= e($category['name']) ?> from this specimen">Remove</button></form><?php endif; ?></li><?php endforeach; ?></ul><?php if (is_admin()): ?><a class="manage-link" href="edit-item.php?id=<?= (int)$item['id'] ?>">Assign categories</a><?php endif; ?></dd></div>
                <div class="spec-row"><dt>Formula</dt><dd><?= e($item['formula']) ?></dd></div>
                <div class="spec-row"><dt>Hardness</dt><dd><?= e($item['hardness']) ?></dd></div>
                <div class="spec-row"><dt>Locality</dt><dd><?= e($item['locality']) ?></dd></div>
            </dl>
            <a href="mailto:terracabinet@gmail.com?subject=Specimen%20inquiry%3A%20<?= rawurlencode($item['name']) ?>" class="btn">Inquire to purchase</a>
            <?php if (is_admin()): ?><div class="detail-manage"><a class="manage-link" href="edit-item.php?id=<?= (int)$item['id'] ?>">Edit listing</a><form method="post" action="delete-item.php" onsubmit="return confirm('Delete this specimen from the catalogue?');"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="manage-link delete-link" type="submit">Delete listing</button></form></div><?php endif; ?>
        </div>
    </div>
</section></main>
<?php require (__DIR__ . '/includes/footer.php'); ?>
</body>
</html>

