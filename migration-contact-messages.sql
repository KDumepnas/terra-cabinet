-- Replace the placeholder with your actual specimen table name before running.
-- Contact messages are stored in the class database; this site does not send email.
SET @specimen_table = 'YOUR_STUDENT_ID_specimens';
SET @safe_table = REPLACE(@specimen_table, '`', '``');
SET @message_table_sql = CONCAT(
  'CREATE TABLE IF NOT EXISTS `tc_messages` (',
  '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,',
  '`name` VARCHAR(100) NOT NULL,',
  '`email` VARCHAR(254) NOT NULL,',
  '`message` TEXT NOT NULL,',
  '`specimen_id` INT UNSIGNED NULL,',
  '`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,',
  'PRIMARY KEY (`id`),',
  'KEY `idx_tc_messages_created` (`created_at`),',
  'CONSTRAINT `fk_tc_messages_specimen` FOREIGN KEY (`specimen_id`) REFERENCES `',
  @safe_table, '` (`id`) ON DELETE SET NULL',
  ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);
PREPARE create_messages_stmt FROM @message_table_sql;
EXECUTE create_messages_stmt;
DEALLOCATE PREPARE create_messages_stmt;
