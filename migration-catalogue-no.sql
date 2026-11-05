-- Replace this placeholder with your specimen table name, then run in phpMyAdmin.
SET @specimen_table = 'YOUR_STUDENT_ID_specimens';
SET @safe_table = REPLACE(@specimen_table, '`', '``');

-- Review duplicate catalogue numbers first. Resolve any rows returned before continuing.
SET @duplicate_rows_sql = CONCAT(
  'SELECT catalogue_no, COUNT(*) AS duplicate_count FROM `', @safe_table,
  '` GROUP BY catalogue_no HAVING COUNT(*) > 1'
);
PREPARE duplicate_rows_stmt FROM @duplicate_rows_sql;
EXECUTE duplicate_rows_stmt;
DEALLOCATE PREPARE duplicate_rows_stmt;

SET @duplicate_count_sql = CONCAT(
  'SELECT COUNT(*) INTO @duplicate_catalogue_numbers FROM (',
  'SELECT catalogue_no FROM `', @safe_table,
  '` GROUP BY catalogue_no HAVING COUNT(*) > 1) AS duplicate_numbers'
);
PREPARE duplicate_count_stmt FROM @duplicate_count_sql;
EXECUTE duplicate_count_stmt;
DEALLOCATE PREPARE duplicate_count_stmt;

SET @has_catalogue_unique_key = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = @specimen_table
    AND INDEX_NAME = 'uq_specimens_catalogue_no'
);

-- This adds the unique key only when existing rows have no duplicate numbers.
SET @catalogue_index_sql = IF(
  @duplicate_catalogue_numbers = 0 AND @has_catalogue_unique_key = 0,
  CONCAT('ALTER TABLE `', @safe_table, '` ADD UNIQUE KEY `uq_specimens_catalogue_no` (`catalogue_no`)'),
  'SELECT ''Resolve duplicate catalogue numbers or keep the existing unique key; no index changes were made.'' AS migration_note'
);
PREPARE catalogue_index_stmt FROM @catalogue_index_sql;
EXECUTE catalogue_index_stmt;
DEALLOCATE PREPARE catalogue_index_stmt;
