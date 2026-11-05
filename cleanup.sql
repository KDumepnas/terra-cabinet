-- Optional cleanup for the known test row. Review the SELECT result first.
-- Replace YOUR_STUDENT_ID_specimens with your assigned specimen table.
-- SQL deletes the row and category links, detaches message references; it does not delete
-- the photo file. If you intentionally remove the row, remove its image manually.
SELECT id, catalogue_no, slug, name, hardness, image
FROM `YOUR_STUDENT_ID_specimens`
WHERE id = 23 AND slug = 'qwerty' AND hardness = '12';

-- Run only after confirming the row above is the disposable test record.
DELETE FROM `YOUR_STUDENT_ID_specimens`
WHERE id = 23 AND slug = 'qwerty' AND hardness = '12'
LIMIT 1;

