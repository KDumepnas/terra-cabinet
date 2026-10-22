-- Import in phpMyAdmin after selecting your database.
CREATE TABLE `specimen_specimens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `catalogue_no` VARCHAR(32) NOT NULL,
  `slug` VARCHAR(190) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `summary` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `formula` VARCHAR(255) NOT NULL DEFAULT '',
  `hardness` VARCHAR(100) NOT NULL DEFAULT '',
  `locality` VARCHAR(150) NOT NULL DEFAULT '',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `image` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_specimens_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial catalogue items are inserted by seed.php after the table name is set in config.php.
