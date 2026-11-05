-- Run in phpMyAdmin against the existing database after backing it up.
-- This migration is safe to rerun and does not remove the legacy category column.
-- The prepared statements use the STUDENT_ID environment value embedded in the
-- current table name below. Replace the placeholder table name if your specimen table differs.
CREATE TABLE IF NOT EXISTS `tc_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tc_specimen_categories` (
  `specimen_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`specimen_id`, `category_id`),
  KEY `idx_specimen_categories_category` (`category_id`),
  CONSTRAINT `fk_specimen_categories_category` FOREIGN KEY (`category_id`) REFERENCES `tc_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tc_categories` (`name`) VALUES
  ('Quartz Family'), ('Raw & Rough'), ('Tumbled & Polished'), ('Other');

SET @has_legacy_category = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'specimen_specimens' AND COLUMN_NAME = 'category'
);
SET @copy_legacy_categories = IF(@has_legacy_category > 0,
  'INSERT IGNORE INTO `tc_categories` (`name`) SELECT DISTINCT `category` FROM `specimen_specimens` WHERE LENGTH(`category`) > 0',
  'SELECT 1');
PREPARE copy_categories_stmt FROM @copy_legacy_categories;
EXECUTE copy_categories_stmt;
DEALLOCATE PREPARE copy_categories_stmt;

SET @copy_legacy_links = IF(@has_legacy_category > 0,
  'INSERT IGNORE INTO `tc_specimen_categories` (`specimen_id`, `category_id`) SELECT s.id, c.id FROM `specimen_specimens` s JOIN `tc_categories` c ON c.name = s.category',
  'SELECT 1');
PREPARE copy_links_stmt FROM @copy_legacy_links;
EXECUTE copy_links_stmt;
DEALLOCATE PREPARE copy_links_stmt;

-- Add the specimen foreign key when the existing table has not had it added yet.
SET @has_specimen_fk = (
  SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tc_specimen_categories'
    AND COLUMN_NAME = 'specimen_id'
    AND REFERENCED_TABLE_NAME = 'specimen_specimens'
);
SET @add_specimen_fk = IF(@has_specimen_fk = 0,
  'ALTER TABLE `tc_specimen_categories` ADD CONSTRAINT `fk_specimen_categories_specimen` FOREIGN KEY (`specimen_id`) REFERENCES `specimen_specimens` (`id`) ON DELETE CASCADE',
  'SELECT 1');
PREPARE add_specimen_fk_stmt FROM @add_specimen_fk;
EXECUTE add_specimen_fk_stmt;
DEALLOCATE PREPARE add_specimen_fk_stmt;
