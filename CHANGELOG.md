# Changelog

## Unreleased

* A long note is cut to its first six lines, with an **Expand** button to show
  all of it and **Collapse** to cut it down again; a pasted article no longer
  pushes every other note out of sight. Long means longer than six lines at
  the width the note is shown at, so a paragraph cut on a phone can be whole
  on a desktop; short notes look as before. **Copy** still copies the whole
  note, and without JavaScript every note is shown in full. Checked in a
  local FreshRSS 1.29.0 in headless Chrome at phone and desktop width, in five
  themes, in English and German.

## 0.2.0 — 2026-10-01

The page reworked for the phone, where it is meant to be used, and made
consistent. Checked in a local FreshRSS 1.29.0 in headless Chrome at phone
width (390px, touch) and desktop width, in five themes (Origine light and
dark, Nord, Swage, Mapco, Dark), in English and German.

* Every note has the same shape: the date above the text, the actions in a
  row of their own below it, Delete set apart at the far end of the row.
  Before, a long date pushed buttons onto a line of their own.
* Per-note Delete in the plain button style; red is kept for "delete all".
  Both still ask for confirmation.
* "Delete all notes" no longer floats over the notes at the bottom of the
  screen (it sat in core's sticky settings bar), and is no longer indented
  on a wide screen.
* Where touch is the main input, every button is at least 44px tall, and the
  text box is set at 16px or more — below that, mobile Safari zooms into a
  field when it is tapped.
* The text box is as wide as the list; on a narrow screen the button to drop
  a note spans the full width. On a wide screen the page is a centred column.
* An empty drop box says so in plain text instead of a warning box.

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
