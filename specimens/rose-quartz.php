<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
$slug = 'rose-quartz';
$stmt = db()->prepare('SELECT * FROM `' . SPECIMEN_TABLE . '` WHERE slug = ? LIMIT 1');
$stmt->execute([$slug]);
$item = $stmt->fetch();
if (!$item) { http_response_code(404); exit('Specimen not found. Import database.sql and run seed.php if this is an original specimen.'); }
$pageTitle = $item['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> | Terra Cabinet</title>
  <link rel="stylesheet" href="../styles.css">
</head>
<body>
  <header class="site-header">
    <a class="logo" href="../index.php">Terra Cabinet</a>
    <nav><ul>
      <li><a href="../index.php">Home</a></li>
      <li><a href="../about.php">About</a></li>
      <li><a href="../contact.php">Contact</a></li>
      <li><a href="../add-item.php">Add a specimen</a></li>
    </ul></nav>
  </header>
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
            <div class="spec-row"><dt>Category</dt><dd><?= e($item['category']) ?></dd></div>
            <div class="spec-row"><dt>Formula</dt><dd><?= e($item['formula']) ?></dd></div>
            <div class="spec-row"><dt>Hardness</dt><dd><?= e($item['hardness']) ?></dd></div>
            <div class="spec-row"><dt>Locality</dt><dd><?= e($item['locality']) ?></dd></div>
          </dl>
          <a href="mailto:terracabinet@gmail.com?subject=Specimen%20inquiry%3A%20<?= rawurlencode($item['name']) ?>" class="btn">Inquire about this piece</a>
        </div>
      </div>
    </section>
  </main>
  <footer class="site-footer">
    <div class="footer-content">
      <div class="footer-brand"><h3>Terra Cabinet</h3><p>A small, hand-catalogued mineral collection.</p></div>
      <nav class="footer-nav"><a href="../index.php">Home</a><a href="../about.php">About</a><a href="../contact.php">Contact</a></nav>
    </div>
    <p class="footer-note">&copy; <?= date('Y') ?> Terra Cabinet. Built for ITWM132.</p>
  </footer>
</body>
</html>
