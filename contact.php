<?php
require __DIR__ . '/config.php';
$pageTitle = 'Contact'; $rootPrefix = '';
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Contact | Terra Cabinet</title><link rel="stylesheet" href="styles.css"></head><body>
<header class="site-header"><a class="logo" href="index.php">Terra Cabinet</a><nav><ul><li><a href="index.php">Home</a></li><li><a href="about.php">About</a></li><li><a href="contact.php" aria-current="page">Contact</a></li><li><a href="add-item.php">Add a specimen</a></li></ul></nav></header>
<main><section class="page-content">
    <span class="kicker">Reach the cabinet</span>
    <h1>Get in touch</h1>
    <p>Have a question about a specimen, or want to ask about a piece not currently listed? Reach out below.</p>
    <dl class="specs contact-specs">
        <div class="spec-row"><dt>Email</dt><dd><a href="mailto:terracabinet@gmail.com">terracabinet@gmail.com</a></dd></div>
        <div class="spec-row"><dt>Location</dt><dd>Lubas, La Trinidad, Benguet, Philippines</dd></div>
        <div class="spec-row"><dt>Response time</dt><dd>Within 2–3 days</dd></div>
    </dl>
</section></main>
<footer class="site-footer"><div class="footer-content"><div class="footer-brand"><h3>Terra Cabinet</h3><p>A small, hand-catalogued mineral collection.</p></div><nav class="footer-nav"><a href="index.php">Home</a><a href="about.php">About</a><a href="contact.php" aria-current="page">Contact</a><a href="add-item.php">Add a specimen</a></nav></div><p class="footer-note">&copy; <?= date('Y') ?> Terra Cabinet. Built for ITWM132.</p></footer></body></html>
