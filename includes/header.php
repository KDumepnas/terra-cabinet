<?php
declare(strict_types=1);
$rootPrefix = $rootPrefix ?? '';
$currentPage = $currentPage ?? basename((string)($_SERVER['SCRIPT_NAME'] ?? ''), '.php');
?>
<header class="site-header">
  <a class="logo" href="<?= e($rootPrefix) ?>index.php">Terra Cabinet</a>
  <nav aria-label="Main navigation"><ul>
    <li><a href="<?= e($rootPrefix) ?>index.php" <?= $currentPage === 'index' ? 'aria-current="page"' : '' ?>>Home</a></li>
    <li><a href="<?= e($rootPrefix) ?>about.php" <?= $currentPage === 'about' ? 'aria-current="page"' : '' ?>>About</a></li>
    <li><a href="<?= e($rootPrefix) ?>contact.php" <?= $currentPage === 'contact' ? 'aria-current="page"' : '' ?>>Contact</a></li>
    <?php if (is_admin()): ?>
      <li><a href="<?= e($rootPrefix) ?>add-item.php" <?= $currentPage === 'add-item' ? 'aria-current="page"' : '' ?>>Add a specimen</a></li>
      <li><a href="<?= e($rootPrefix) ?>manage-categories.php" <?= $currentPage === 'manage-categories' ? 'aria-current="page"' : '' ?>>Categories</a></li>
      <li><a href="<?= e($rootPrefix) ?>messages.php" <?= $currentPage === 'messages' ? 'aria-current="page"' : '' ?>>Messages</a></li>
      <li><form method="post" action="<?= e($rootPrefix) ?>logout.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="nav-action" type="submit">Log out</button></form></li>
    <?php else: ?>
      <li><a href="<?= e($rootPrefix) ?>login.php" <?= $currentPage === 'login' ? 'aria-current="page"' : '' ?>>Admin login</a></li>
    <?php endif; ?>
  </ul></nav>
</header>
