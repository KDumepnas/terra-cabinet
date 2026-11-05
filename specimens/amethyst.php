<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
$slug = 'amethyst';
$stmt = db()->prepare('SELECT * FROM `' . SPECIMEN_TABLE . '` WHERE slug = ? LIMIT 1');
$stmt->execute([$slug]);
$item = $stmt->fetch();
if (!$item) { http_response_code(404); exit('Specimen not found. Import database.sql and run seed.php if this is an original specimen.'); }
$categoryStmt = db()->prepare('SELECT c.name FROM ' . CATEGORY_TABLE . ' c INNER JOIN ' . SPECIMEN_CATEGORY_TABLE . ' sc ON sc.category_id = c.id WHERE sc.specimen_id = ? ORDER BY c.name');
$categoryStmt->execute([(int)$item['id']]);
$itemCategories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
$pageTitle = $item['name'];
$rootPrefix = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="description" content="Terra Cabinet specimen catalogue and mineral collection.">
  <title><?= e($pageTitle) ?> | Terra Cabinet</title>
  <link rel="stylesheet" href="../styles.css">
</head>
<body>
  <?php require (dirname(__DIR__) . '/includes/header.php'); ?>
  <main>
    <section class="specimen-detail">
      <a href="../index.php" class="back-link">&larr; Back to collection</a>
      <div class="detail-grid">
        <div class="detail-image"><img src="../<?= e(ltrim($item['image'], '/')) ?>" alt="<?= e($item['name']) ?>"></div>
        <div class="detail-info">
          <div class="catalogue-number">Catalogue No. TC-<?= e($item['catalogue_no']) ?></div>
          <h1><?= e($item['name']) ?></h1>
          <span class="price">₱<?= e(number_format((float)$item['price'], 0)) ?></span>
          <p class="description"><?= e($item['description']) ?></p>
          <dl class="specs">
            <div class="spec-row"><dt>Categories</dt><dd><?= e(implode(', ', $itemCategories)) ?></dd></div>
            <div class="spec-row"><dt>Formula</dt><dd><?= e($item['formula']) ?></dd></div>
            <div class="spec-row"><dt>Hardness</dt><dd><?= e($item['hardness']) ?></dd></div>
            <div class="spec-row"><dt>Locality</dt><dd><?= e($item['locality']) ?></dd></div>
          </dl>
          <a href="mailto:terracabinet@gmail.com?subject=Specimen%20inquiry%3A%20<?= rawurlencode($item['name']) ?>" class="btn">Inquire about this piece</a>
        </div>
      </div>
    </section>
  </main>
  <?php require (dirname(__DIR__) . '/includes/footer.php'); ?>
</body>
</html>


