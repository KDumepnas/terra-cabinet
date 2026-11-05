# Terra Cabinet

## Database setup

1. In the hosting control panel, open phpMyAdmin and select the class database.
2. For a fresh database, import `database.sql`. It creates `YOUR_STUDENT_ID_specimens`, plus the category and specimen-category tables. Replace the placeholder with your assigned student ID before importing.
   For an existing installation, back up the database and run `migration-m2m-categories.sql`. It creates the relationship table, keeps the old category column, and copies existing category assignments. Replace the specimen table placeholder in the migration with its actual table name.
   For either setup, run `migration-catalogue-no.sql` after replacing its table placeholder. Review any duplicate catalogue numbers it reports and resolve them before rerunning so it can add the unique key.
3. Copy `config.local.example.php` to `config.local.php` on the server and enter the MySQL connection settings. Keep the real file out of source control.
4. Configure the admin username and a `password_hash()` value in `config.local.php` or the `ADMIN_USERNAME` and `ADMIN_PASSWORD_HASH` environment variables.
5. Open `login.php` to manage specimens and categories. Use the public collection to search and view detail pages.
6. After seeding the catalogue once, remove `seed.php` from the public server.

The upload form accepts JPEG, PNG, WEBP, and GIF images up to 5 MB. Uploaded files are stored in `uploads/`. Categories can be managed at `manage-categories.php`; each specimen can belong to multiple categories.

## Apache requirements

The `.htaccess` rules protect private config, SQL, and Markdown files and disable directory listings. On Apache, enable `mod_authz_core`, `mod_expires`, `mod_headers`, and `mod_deflate`, and allow `AllowOverride All` (or at least `FileInfo AuthConfig Options`) for this site and the uploads directory. Confirm `.htaccess` files are honored after deployment.
