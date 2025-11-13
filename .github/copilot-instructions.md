The PETTRACKING codebase — quick orientation for AI coding agents

Be concise and make minimal, low-risk changes. This is a PHP web app (no framework) served from the project root under XAMPP.

Key facts
- Entry pages: `public/index.php` (redirects to `login.php`) and many UI pages live at project root and in `public/`.
- DB: `db/db_connect.php` instantiates a PDO `$pdo` for MySQL. Database name expected: `pawsitive_patrol`.
- Sessions: many `includes/*.php` files begin with `session_start()` and check `$_SESSION['owner_id']` to gate owner pages.
- File locations:
  - User-facing pages: `*.php` in project root and `public/` (e.g. `add_pet.php`, `view_pets.php`, `owner_dashboard.php`).
  - Backend handlers / helpers: `includes/` (e.g. `generate_qr.php`, `pet.php`, `mark_lost.php`).
  - DB helpers: `db/db_connect.php` and `db/db_connection.php` (use the PDO `$pdo`).
  - Uploaded images: `uploads/` and generated QR images in `qr/`.

Design & patterns to preserve
- Thin, file-per-page architecture: each PHP file often mixes controller + view. Prefer edits that maintain that style rather than introducing new frameworks.
- Use the existing `$pdo` PDO instance from `db/db_connect.php` for database access. Require it via `require_once` with relative paths exactly as used in other files (e.g. `require_once '../db/db_connect.php'` from `includes/`).
- Session checks are required for owner-only pages. Keep `session_start()` and `if (!isset($_SESSION['owner_id'])) { header("Location: login.php"); exit(); }` unless changing auth consistently across the app.
- File-system checks: codebase often uses `file_exists()` against `uploads/` and `qr/` before showing images — preserve that behavior.

Database and migrations
- The repository includes SQL in `db/install.sql` and `db/pawsitive_patrol.sql`. When proposing DB changes, update these SQL files.

Common code smells and quick fixes
- Unsanitized output: many pages use `htmlspecialchars()` already. Follow that pattern when echoing user data. Prefer `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
- Prepared statements: the app uses prepared PDO statements. Keep using `$pdo->prepare()` and parameter binding; do not switch to string interpolation for queries.
- Error handling: `db/db_connect.php` shows user-friendly HTML on connection issues. Preserve informative messages and do not leak DB credentials in responses.

Developer workflows (how to run & debug)
- Local dev: the app is designed to run under XAMPP on Windows. Ensure Apache + MySQL are running in XAMPP Control Panel.
- DB setup: import `db/pawsitive_patrol.sql` or `db/install.sql` into MySQL (database `pawsitive_patrol`).
- PHP errors: `db/db_connect.php` enables display_errors; use browser to view runtime errors while developing.

When editing code — small PR checklist
1. Keep changes minimal and self-contained. Prefer small commits that modify at most a couple files.
2. Run a quick manual smoke test in the browser (login, view an owner page, generate a QR, view a pet). Document steps changed.
3. Preserve relative require paths. From files in `includes/` use `../db/db_connect.php`; from root use `db/db_connect.php`.
4. Maintain session gating and PDO prepared statements. Use `htmlspecialchars()` for output.

Examples from the codebase
- Generating QR code URL: `includes/generate_qr.php` builds a URL using `$_SERVER['HTTP_HOST']` and the pet's `qr_code` field. Keep this pattern if modifying QR generation.
- Pet detail access: `includes/pet.php` expects `GET id` and owner session. Use `SELECT * FROM pets WHERE pet_id = ? AND owner_id = ?` (example of ownership checks).

Avoid
- Adding new composer / npm dependencies without discussing—they're not present now.
- Changing global error/display settings without noting how to reverse them for production.

If you need clarification
- Ask which pages to test and whether you can modify SQL schema files. Provide small diffs and the manual steps to validate.

If merging with existing `.github/copilot-instructions.md`, preserve any project-specific rules and append or replace sections only when you can confirm outdated content.

End of file.
