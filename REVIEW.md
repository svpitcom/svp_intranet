# Code review and bug fixes

Reviewed the application's PHP controllers, models, core routing/session/upload code, templates, frontend scripts, and Apache rules. Third-party code in `vendor/` and bundled assets were not modified.

## Fixed findings

- PM record uploads were silently omitted because the form lacked `multipart/form-data`.
- The PM history template was missing; per-schedule history also rendered twice.
- Attachments were directly accessible without authentication. Links now use authenticated download routes, and Apache denies direct access to the upload directory. All authenticated roles retain access, matching the existing PM report permissions.
- Upload validation now rejects malformed upload structures, non-HTTP-upload files, empty files, and files over 10 MB based on actual size. Download paths are restricted to generated PDF filenames within the expected folder.
- PM record insertion and schedule advancement now share a transaction and lock the schedule row. Backdated entries no longer move the latest completion backwards.
- Failed PM saves discard newly uploaded files. Replacing a reference document removes the superseded file after commit. Deletion cleans up attachments only after successful database deletion, respecting existing foreign-key behavior.
- PM date, frequency, result-status, nested checklist, and scalar input validation prevents malformed requests from causing errors or storing invalid values.
- State-changing forms now include server-validated CSRF tokens, including AJAX-rendered user tables. Logout uses POST.
- Login regenerates the session ID. Authenticated requests refresh account status and role, so deactivated accounts and revoked roles take effect on the next request. Password whitespace is preserved.
- User role validation, duplicate username checks, and missing-user/device update checks were added.
- User search now ignores obsolete responses and obsolete cleanup handlers, checks HTTP errors, and handles login redirects.
- Device status sorting, subfolder sidebar highlighting, the PDF department query, local font paths, and the missing Thai font declaration in the PM history PDF were corrected. PDF remote resource loading is disabled.
- Malformed route IDs no longer reach integer-typed actions. Apache rules protect private project directories if the repository root is served.

## Verification

Run from the project root:

```text
php tests/regression.php
php tests/upload_http.php
php tests/pdf_smoke.php
```

- 78 isolated regression checks cover routing, CSRF, validation, rollback, backdated PM records, upload rejection, view rendering, and registered handlers.
- Four real multipart HTTP checks cover a valid PDF, a disguised text file, an empty file, and a file exceeding 10 MB. These use a temporary loopback server and temporary files.
- All three PDF templates render with an embedded Sarabun font and remote access disabled. This is a rendering/font smoke test, not a visual layout audit.
- Local Apache checks: login returns 200, direct uploads return 403, unauthenticated attachment requests redirect to login, and login POST without CSRF returns 403.
- PHP syntax checks and `git diff --check` pass.

## Limits and deployment notes

- No production database records or existing uploaded documents were changed during verification. Database tests use isolated SQLite and do not simulate MySQL row-lock concurrency or verify the deployed schema/foreign-key rules. Transactions require transactional tables such as InnoDB.
- This installation uses Apache Alias `/svp_intranet` pointing to `public/`; its existing `RewriteBase` is retained. Other servers must enforce equivalent restrictions on uploads and private directories.
- Existing direct attachment bookmarks will return 403. Open attachments through their PM pages instead. Refresh any forms opened before the CSRF changes.
- The local PHP upload temporary directory produced a fallback notice in the first HTTP test; the isolated test sets a valid temporary directory explicitly. The machine's PHP configuration was not changed.
- Existing orphan files were not bulk-deleted. New cleanup applies only to files involved in a successful replacement/deletion or a failed new save.
