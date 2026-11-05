-- Import in phpMyAdmin after selecting u455679702_db_3A.
CREATE TABLE `YOUR_STUDENT_ID_specimens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `catalogue_no` VARCHAR(32) NOT NULL,
  `slug` VARCHAR(190) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `summary` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `formula` VARCHAR(255) NOT NULL DEFAULT '',
  `hardness` VARCHAR(100) NOT NULL DEFAULT '',
  `locality` VARCHAR(150) NOT NULL DEFAULT '',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `image` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_specimens_slug` (`slug`),
  UNIQUE KEY `uq_specimens_catalogue_no` (`catalogue_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tc_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tc_specimen_categories` (
  `specimen_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`specimen_id`, `category_id`),
  CONSTRAINT `fk_specimen_categories_specimen` FOREIGN KEY (`specimen_id`) REFERENCES `YOUR_STUDENT_ID_specimens` (`id`) ON DELETE CASCADE,
  KEY `idx_specimen_categories_category` (`category_id`),
  CONSTRAINT `fk_specimen_categories_category` FOREIGN KEY (`category_id`) REFERENCES `tc_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tc_categories` (`name`) VALUES
  ('Quartz Family'), ('Raw & Rough'), ('Tumbled & Polished'), ('Other');

-- Initial catalogue items and their category associations are inserted by seed.php.

