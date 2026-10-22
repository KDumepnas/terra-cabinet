<?php
require __DIR__ . '/config.php';
$pageTitle = 'About'; $rootPrefix = '';
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>About | Terra Cabinet</title><link rel="stylesheet" href="styles.css"></head><body>
<header class="site-header"><a class="logo" href="index.php">Terra Cabinet</a><nav><ul><li><a href="index.php">Home</a></li><li><a href="about.php" aria-current="page">About</a></li><li><a href="contact.php">Contact</a></li><li><a href="add-item.php">Add a specimen</a></li></ul></nav></header>
<main><section class="page-content">
    <span class="kicker">Field notes</span>
    <h1>Why this cabinet exists</h1>
    <p>Terra Cabinet started as a shelf of stones picked up on hikes and swap meets, kept in a shoebox for years before it became anything more organized. What changed it was realizing how little context most crystal listings give — a photo, a price, and nothing about where the piece actually came from or what it is made of.</p>
    <p>Every specimen here is logged the way a geologist would log a field sample: formula, hardness, locality, and a description written from actually handling the piece. Nothing is mass-imported from a supplier catalog. If a specimen is listed, it is because it passed through this cabinet first.</p>
    <p>This is a small, slow-growing collection — new pieces are added individually, not in bulk, and grouped by how they formed rather than dumped into one long list. The goal is to have stones worth cataloguing properly.</p>
</section></main>
<footer class="site-footer"><div class="footer-content"><div class="footer-brand"><h3>Terra Cabinet</h3><p>A small, hand-catalogued mineral collection.</p></div><nav class="footer-nav"><a href="index.php">Home</a><a href="about.php" aria-current="page">About</a><a href="contact.php">Contact</a><a href="add-item.php">Add a specimen</a></nav></div><p class="footer-note">&copy; <?= date('Y') ?> Terra Cabinet. Built for ITWM132.</p></footer></body></html>
