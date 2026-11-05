# Terra Cabinet

A PHP specimen catalogue with search, detail pages, and image uploads.

## Database setup

1. Create a MySQL database.
2. Set `STUDENT_ID` to your assigned table prefix. Before a fresh install, replace `specimen_specimens` in `database.sql` with `<STUDENT_ID>_specimens`.
3. Import `database.sql`. It creates the specimen table, categories, and the specimen-category junction table.
4. Copy `config.local.example.php` to `config.local.php` on the server and enter the database connection details. Keep `config.local.php` private and out of source control.
5. For an existing installation, back up the database and run `migration-m2m-categories.sql` in phpMyAdmin after replacing `specimen_specimens` with the actual specimen table name. The migration preserves the legacy category column and copies its assignments.
6. Open `seed.php` once to add the sample specimens and category links. It can be run again safely.
7. Open `index.php` to browse the catalogue. Use “Add a specimen” to submit an item with a photo.

Manage shared categories at `manage-categories.php`. A specimen can belong to multiple categories, and individual specimen-category links can be removed from its detail page without deleting either record. The upload form accepts JPEG, PNG, WEBP, and GIF images up to 5 MB; uploaded files are stored in `uploads/`.