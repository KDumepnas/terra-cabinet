<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
$query = trim((string)($_GET['q'] ?? ''));
$pageTitle = 'Mineral & Crystal Specimens';
$rootPrefix = '';
$sql = 'SELECT * FROM `' . SPECIMEN_TABLE . '`';
if ($query !== '') {
    $sql .= ' WHERE name LIKE :q0 OR summary LIKE :q1 OR description LIKE :q2 OR category LIKE :q3 OR locality LIKE :q4';
}
$sql .= ' ORDER BY catalogue_no, id';
try {
    $stmt = db()->prepare($sql);
    $like = '%' . $query . '%';
    $stmt->execute($query !== '' ? [':q0' => $like, ':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like] : []);
    $items = $stmt->fetchAll();
    $groups = [];
    foreach ($items as $item) {
        $groups[$item['category']][] = $item;
    }
    $featured = null;
    $recentItems = [];
    $collectionStats = ['total' => 0, 'categories' => 0];
    if ($query === '') {
        $collectionStats = db()->query('SELECT COUNT(*) AS total, COUNT(DISTINCT category) AS categories FROM `' . SPECIMEN_TABLE . '`')->fetch();
        $featured = db()->query('SELECT * FROM `' . SPECIMEN_TABLE . '` ORDER BY id ASC LIMIT 1')->fetch() ?: null;
        $recentItems = db()->query('SELECT * FROM `' . SPECIMEN_TABLE . '` ORDER BY created_at DESC, id DESC LIMIT 3')->fetchAll();
    }
} catch (Throwable $error) {
    http_response_code(500);
    $items = [];
    $dbError = $error->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Terra Cabinet</title>
    <meta name="description" content="A hand-catalogued mineral and crystal collection.">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header class="site-header"><a class="logo" href="index.php">Terra Cabinet</a><nav><ul><li><a href="index.php" aria-current="page">Home</a></li><li><a href="about.php">About</a></li><li><a href="contact.php">Contact</a></li><li><a href="add-item.php">Add a specimen</a></li></ul></nav></header>
<section class="hero">
    <div class="hero-grid">
        <div class="hero-copy">
            <h1>Rare finds, carefully catalogued</h1>
            <p>Shop a growing collection of mineral specimens, each with its own field notes and price.</p>
            <a href="#collection" class="btn">Shop the collection</a>
        </div>
        <div class="ledger-tag" aria-hidden="true"><div class="tag-no">SPECIMEN LOG</div><div class="tag-title"><?= count($items) ?> specimens</div><dl><dt>Status</dt><dd>Catalogued</dd><dt>Added</dt><dd>One at a time</dd></dl></div>
    </div>
</section>
<?php if ($query === '' && !isset($dbError) && $featured): ?>
<section class="collection-intro" aria-labelledby="intro-title">
    <div class="intro-copy">
        <p class="eyebrow">A growing field catalogue</p>
        <h2 id="intro-title">Every stone has a story.</h2>
        <p>Explore a carefully recorded collection of minerals, crystals, and geological curiosities. Each entry includes notes on its character, composition, and place of origin.</p>
        <a class="link-arrow" href="add-item.php">Contribute a specimen &rarr;</a>
    </div>
    <dl class="collection-facts">
        <div><dt>Specimens</dt><dd><?= (int)$collectionStats['total'] ?></dd></div>
        <div><dt>Categories</dt><dd><?= (int)$collectionStats['categories'] ?></dd></div>
        <div><dt>Catalogue</dt><dd>Open for additions</dd></div>
    </dl>
</section>
<section class="spotlight" aria-labelledby="featured-title">
    <div class="section-kicker">From the cabinet</div>
    <div class="featured-card">
        <?php $featuredUrl = is_file(__DIR__ . '/specimens/' . $featured['slug'] . '.php') ? 'specimens/' . rawurlencode($featured['slug']) . '.php' : 'detail.php?slug=' . rawurlencode($featured['slug']); ?>
        <a class="featured-image" href="<?= e($featuredUrl) ?>"><img src="<?= e($featured['image']) ?>" alt="<?= e($featured['name']) ?>" fetchpriority="high" decoding="async"></a>
        <div class="featured-copy">
            <p class="eyebrow">Featured specimen · No. <?= e($featured['catalogue_no']) ?></p>
            <h2 id="featured-title"><?= e($featured['name']) ?></h2>
            <p><?= e($featured['summary']) ?></p>
            <a class="link-arrow" href="<?= e($featuredUrl) ?>">Read its field notes &rarr;</a>
        </div>
    </div>
</section>
<?php if ($recentItems): ?>
<section class="recent-section" aria-labelledby="recent-title">
    <div class="section-heading"><div><p class="eyebrow">Fresh from the ledger</p><h2 id="recent-title">Recently added</h2></div><a class="link-arrow" href="#collection">Browse all specimens &rarr;</a></div>
    <div class="drawer-grid recent-grid">
        <?php foreach ($recentItems as $recent): ?>
            <?php $recentUrl = is_file(__DIR__ . '/specimens/' . $recent['slug'] . '.php') ? 'specimens/' . rawurlencode($recent['slug']) . '.php' : 'detail.php?slug=' . rawurlencode($recent['slug']); ?>
            <article class="shop-card">
            <a href="<?= e($recentUrl) ?>" class="specimen-card">
                <div class="card-image"><img src="<?= e($recent['image']) ?>" alt="<?= e($recent['name']) ?>" loading="lazy"></div>
                <div class="card-body"><div class="card-number">No. <?= e($recent['catalogue_no']) ?></div><h3><?= e($recent['name']) ?></h3><p><?= e($recent['summary']) ?></p></div>
                <div class="card-foot"><span class="locality"><?= e($recent['locality']) ?></span><span class="price">₱<?= e(number_format((float)$recent['price'], 0)) ?></span></div>
            </a>
            <div class="shop-actions"><a class="btn btn-small" href="<?= e($recentUrl) ?>">View details</a><a class="manage-link btn-small" href="edit-item.php?id=<?= (int)$recent['id'] ?>">Edit</a></div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php endif; ?>
<main id="collection">
    <section class="drawer">
        <div class="drawer-head"><h2><?= $query !== '' ? 'Search results' : 'The Collection' ?></h2><p><?= $query !== '' ? 'Results for “' . e($query) . '”' : 'Search the catalogue or explore the specimens below.' ?></p></div>
        <form class="search-form" action="index.php" method="get" role="search">
            <label for="q">Search specimens</label>
            <input id="q" name="q" type="search" value="<?= e($query) ?>" placeholder="Name, mineral, or locality">
            <button class="btn" type="submit">Search</button>
            <?php if ($query !== ''): ?><a class="link-arrow" href="index.php">Clear search</a><?php endif; ?>
        </form>
        <?php if (isset($dbError)): ?><p class="form-error">Database connection failed. Check the server configuration and import database.sql. Details: <?= e($dbError) ?></p><?php endif; ?>
        <?php if (!$items && !isset($dbError)): ?><p>No specimens matched your search.</p><?php endif; ?>
        <?php foreach (($groups ?? []) as $category => $groupItems): ?>
            <section class="collection-group">
                <h3><?= e($category) ?></h3>
                <div class="drawer-grid">
                    <?php foreach ($groupItems as $item): ?>
                    <?php $detailUrl = is_file(__DIR__ . '/specimens/' . $item['slug'] . '.php') ? 'specimens/' . rawurlencode($item['slug']) . '.php' : 'detail.php?slug=' . rawurlencode($item['slug']); ?>
                    <article class="shop-card">
                    <a href="<?= e($detailUrl) ?>" class="specimen-card">
                        <div class="card-image"><img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>" loading="lazy"></div>
                        <div class="card-body"><div class="card-number">No. <?= e($item['catalogue_no']) ?></div><h3><?= e($item['name']) ?></h3><p><?= e($item['summary']) ?></p></div>
                        <div class="card-foot"><span class="locality"><?= e($item['locality']) ?></span><span class="price">₱<?= e(number_format((float)$item['price'], 0)) ?></span></div>
                    </a>
                    <div class="shop-actions"><a class="btn btn-small" href="<?= e($detailUrl) ?>">View details</a><a class="manage-link btn-small" href="edit-item.php?id=<?= (int)$item['id'] ?>">Edit</a></div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </section>
</main>
<footer class="site-footer">
    <div class="footer-content"><div class="footer-brand"><h3>Terra Cabinet</h3><p>A small, hand-catalogued mineral collection.</p></div><nav class="footer-nav"><a href="index.php" aria-current="page">Home</a><a href="about.php">About</a><a href="contact.php">Contact</a><a href="add-item.php">Add a specimen</a></nav></div>
    <p class="footer-note">&copy; <?= date('Y') ?> Terra Cabinet.</p>
</footer>
</body>
</html>
