# Terra Cabinet

Terra Cabinet is a plain PHP and MySQL mineral specimen catalogue. Visitors can browse and search specimens, view their category assignments, and submit contact messages. An authenticated administrator can manage specimens, categories, and messages.

## Requirements

- PHP 8.0 or later with PDO MySQL, Fileinfo, and mbstring enabled. GD is optional; without GD, accepted source images are stored without resizing.
- MySQL or MariaDB with InnoDB support.
- Apache with `.htaccess` overrides enabled for the project and `uploads/` directories.

## Database setup

Choose one database setup path:

### Fresh database

1. Replace `YOUR_STUDENT_ID` in `database.sql` with the assigned table prefix, then import it into the class database. It creates the specimen table, `tc_categories`, and `tc_specimen_categories` with foreign keys and unique catalogue numbers.
2. Run `migration-contact-messages.sql` after replacing its table placeholder. It creates `tc_messages` and links its optional specimen reference with `ON DELETE SET NULL`.

### Existing installation

1. Back up the database.
2. Replace the table placeholder in `migration-m2m-categories.sql` with the existing specimen table name and run it. This creates the category junction table and carries forward legacy category assignments without deleting the old category column.
3. Run `migration-catalogue-no.sql` after replacing its specimen table placeholder. Review the duplicate-number query it returns. Resolve duplicates, then rerun so it can add the unique catalogue-number key. If duplicates remain, the migration intentionally does not add the key.
4. Run `migration-contact-messages.sql` with the existing specimen table name.

For a fresh database, `database.sql` already adds the catalogue-number key, so `migration-catalogue-no.sql` is not needed unless you want to check an existing table.

## Application configuration

1. Copy `config.local.example.php` to `config.local.php` on the server. Set the database host, port, database name, user, password, admin username, admin password hash, and assigned student ID. The student ID is used to form the `<student_id>_specimens` table name.
2. Generate the admin password hash on a trusted machine with:

   ```sh
   php -r "echo password_hash('replace-with-a-strong-password', PASSWORD_DEFAULT), PHP_EOL;"
   ```

   Put the resulting hash in `adminPasswordHash`; do not put the plaintext password in the PHP file. Environment variables `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `ADMIN_USERNAME`, `ADMIN_PASSWORD_HASH`, and `STUDENT_ID` can be used instead.
3. Keep `config.local.php` out of source control. The supplied `.gitignore` excludes it, and `.htaccess` denies direct web access to local configuration files.
4. After importing the database, open `seed.php` once while signed in as admin if you want the sample specimens. Delete `seed.php` from the public server immediately afterward.
5. Sign in at `login.php`. Admin actions are protected by the session and CSRF checks. Log out using the navigation button.

## Features and data behavior

- Specimens and categories have a many-to-many relationship through `tc_specimen_categories`. Each specimen can be assigned several categories; each category can be shared across specimens.
- Add and edit forms update category assignments together with specimen data. Removing a category on a detail page removes only that connection. A specimen must keep at least one category.
- The public contact form stores messages in `tc_messages`; it does not send email. Admins can review and delete messages at `messages.php` and reply through their email client.
- Uploaded images must be valid JPEG, PNG, WEBP, or GIF files no larger than 5 MB, 8,000 pixels on either side, or 40 megapixels total. They are stored in `uploads/`; the directory rules block script execution.
- Application errors are logged on the server and generic messages are shown to visitors. Check the PHP or web server error log when diagnosing a failed request.

## SQL files and run order

| File | When to run |
| --- | --- |
| `database.sql` | Once for a fresh database; substitute the student ID placeholder first. |
| `migration-m2m-categories.sql` | Existing single-category database only; run before using category features. |
| `migration-catalogue-no.sql` | Existing catalogue table when it lacks a unique catalogue-number key; resolve any duplicates it reports first. |
| `migration-contact-messages.sql` | After the specimen table exists; required for the contact form and admin inbox. |
| `cleanup.sql` | Optional manual cleanup only. Review its `SELECT` first and execute its `DELETE` only if the exact test row is confirmed disposable. It does not remove the associated image file. |

Do not run `cleanup.sql` as part of normal setup.

## Apache deployment

Enable `mod_authz_core`, `mod_expires`, `mod_headers`, and `mod_deflate`; allow `AllowOverride All` (or the required `FileInfo`, `AuthConfig`, and `Options` overrides). Confirm that the root and `uploads/.htaccess` rules are honored. Directory indexes are disabled, PHP errors are hidden from responses, and direct access to config, SQL, and Markdown files is denied.

## Main files

- `index.php`, `detail.php`: public catalogue, search, details, and category associations.
- `add-item.php`, `edit-item.php`, `delete-item.php`, `manage-categories.php`, `remove-category-link.php`: authenticated catalogue and relationship management.
- `login.php`, `logout.php`, `messages.php`: administrator session and contact inbox.
- `config.php`, `config.local.example.php`: shared settings and local setup example.
- `includes/header.php`, `includes/footer.php`: shared site navigation and footer.
- `image-helper.php`, `image-optimizer.js`: upload validation and client-side image preparation.
- `database.sql`, `migration-*.sql`, `cleanup.sql`: schema, migrations, and optional reviewed cleanup.
