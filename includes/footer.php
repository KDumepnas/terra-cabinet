<footer class="site-footer">
  <div class="footer-content">
    <div class="footer-brand"><h3>Terra Cabinet</h3><p>A small, hand-catalogued mineral collection.</p></div>
    <nav class="footer-nav" aria-label="Footer navigation"><a href="<?= e($rootPrefix ?? '') ?>index.php">Home</a><a href="<?= e($rootPrefix ?? '') ?>about.php">About</a><a href="<?= e($rootPrefix ?? '') ?>contact.php">Contact</a><?php if (is_admin()): ?><a href="<?= e($rootPrefix ?? '') ?>add-item.php">Add a specimen</a><?php else: ?><a href="<?= e($rootPrefix ?? '') ?>login.php">Admin login</a><?php endif; ?></nav>
  </div>
  <p class="footer-note">&copy; <?= date('Y') ?> Terra Cabinet. Built for ITWM132.</p>
</footer>
