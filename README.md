# Terra Cabinet

A PHP specimen catalogue with search, detail pages, and image uploads.

## Setup

1. Create a MySQL database and import `database.sql`.
2. Copy `config.local.example.php` to `config.local.php` and enter your database connection details. Keep `config.local.php` private and out of source control.
3. Open `seed.php` once to add the sample catalogue entries. It can be run again safely.
4. Open `index.php` to browse the catalogue. Use “Add a specimen” to submit an item with a photo.

The upload form accepts JPEG, PNG, WEBP, and GIF images up to 5 MB. Uploaded files are stored in `uploads/`.
