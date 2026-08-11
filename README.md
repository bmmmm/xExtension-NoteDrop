# Note Drop

A [FreshRSS](https://freshrss.org) extension that adds a drop box: leave a note
or a link from one device, pick it up on another with one click on a copy
button.

## Why

Moving a link or a few lines of text from the phone to the desktop — or back —
otherwise takes a detour: a messenger chat with yourself, a mail draft, or a
note app that syncs through someone else's cloud. FreshRSS is already open on
both devices, already responsive, and already behind your own login; a page
that holds a few lines of text until they are picked up is all that was
missing.

The sister extension [Share via QR
Code](https://github.com/bmmmm/xExtension-ShareViaQRCode) covers the other
direction of the same errand: it moves a link desktop→phone through the camera,
without touching the server. This one stores, because picking text up on a
desktop means the clipboard, and a camera cannot fill that.

## What it does

* Adds a **Note drop** page, reachable from the header menu (top right, gear
  icon) next to “Logs” and “About”.
* A text box at the top: type or paste, press **Drop it**, done. Works the same
  in a phone browser — that is the point.
* Every note is listed newest first with a **Copy** button that puts the note's
  exact text on the clipboard, using the browser's Clipboard API.
* A note that is exactly one web link also gets an **Open** button. Only plain
  `http(s)` is ever made clickable; anything else stays text.
* Notes can be deleted one by one, or all at once.
* Notes live in a table of their own, per user, and survive the extension being
  disabled. Deleting them is what the delete buttons are for.

## What it is not

**Not a vault.** Notes are stored in FreshRSS' database in plain text and
protected the way everything else in FreshRSS is: by your login, your HTTPS,
and your server's own security — no additional encryption on top. That is the
right level for links and notes in transit between your own devices. Passwords
and other secrets belong in a password manager, not here.

## Requirements

* FreshRSS **1.29.0 or newer** — CI analyses the extension against exactly that
  release, so anything newer than its API fails the build rather than your
  install.
* SQLite, MySQL/MariaDB and PostgreSQL are all supported; the schema statements
  for all three are executed in CI.
* Copying needs a secure context (HTTPS) — the Clipboard API is not available
  without one. A FreshRSS that is reachable from your phone should be running
  HTTPS anyway.

## Install

1. Download this repository and place the `xExtension-NoteDrop` directory into
   the `extensions/` directory of your FreshRSS installation.
2. Enable **Note Drop** under *Configuration → Extensions*.
3. Open **Note drop** from the header menu (top right, gear icon).

## Development

Linting and tests mirror the sister extensions: PHPStan (level 10, against a
`.freshrss-core` checkout), PHP_CodeSniffer with FreshRSS' ruleset, ESLint
mirroring core's config, `node --test` for the pure JS helpers, and
`tests/schema.php` against a real database. See `.github/workflows/ci.yml`.

## Support

If this extension is useful to you, you can support development on
[Ko-fi](https://ko-fi.com/bmabma).

## License

[AGPL-3.0](LICENSE), matching FreshRSS itself.
