<?php
// Copy to config.local.php on the server. Never commit the real config.local.php.
// Generate the password hash with: php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"
$dbHost = 'localhost';
$dbPort = '3307';
$dbName = 'your_database_name';
$dbUser = 'your_database_user';
$dbPassword = 'your_database_password';
$adminUsername = 'admin';
$adminPasswordHash = 'paste_password_hash_here';
// Set this to your assigned table prefix only if your hosting setup requires it.
$studentId = 'your_student_id';
