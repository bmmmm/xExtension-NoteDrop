# Changelog

## 0.1.0 — 2026-08-11

First release, verified end to end on a live FreshRSS 1.29.1 (enable, drop a
multi-line note, copy it back off the clipboard, open a link note, delete).

* A **Note drop** page in the header menu: a textarea to drop a note or a link
  from any device, a list of every note newest first.
* A **Copy** button per note (Clipboard API, outcome shown on the button
  itself), an **Open** button for notes that are exactly one `http(s)` link.
* Per-note delete and delete-all, both behind FreshRSS' own confirm.
* Per-user storage on SQLite, MySQL/MariaDB and PostgreSQL; the notes survive
  the extension being disabled.
