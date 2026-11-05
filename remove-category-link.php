<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
    http_response_code(403);
    exit('Request denied. Return to the specimen and try again.');
}
$specimenId = filter_input(INPUT_POST, 'specimen_id', FILTER_VALIDATE_INT);
$categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
if (!$specimenId || !$categoryId) {
    http_response_code(400);
    exit('A valid specimen and category are required.');
}
$stmt = db()->prepare('DELETE FROM `' . SPECIMEN_CATEGORY_TABLE . '` WHERE specimen_id = ? AND category_id = ?');
$stmt->execute([$specimenId, $categoryId]);
$slugStmt = db()->prepare('SELECT slug FROM `' . SPECIMEN_TABLE . '` WHERE id = ?');
$slugStmt->execute([$specimenId]);
$slug = $slugStmt->fetchColumn();
if (!$slug) {
    header('Location: index.php', true, 303);
    exit;
}
header('Location: detail.php?slug=' . rawurlencode((string)$slug), true, 303);
exit;
