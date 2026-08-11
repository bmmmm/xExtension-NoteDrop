# Changelog

## 0.1.0 — unreleased

First release.

* A **Note drop** page in the header menu: a textarea to drop a note or a link
  from any device, a list of every note newest first.
* A **Copy** button per note (Clipboard API, outcome shown on the button
  itself), an **Open** button for notes that are exactly one `http(s)` link.
* Per-note delete and delete-all, both behind FreshRSS' own confirm.
* Per-user storage on SQLite, MySQL/MariaDB and PostgreSQL; the notes survive
  the extension being disabled.
