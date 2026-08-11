# Security policy

## Supported versions

The latest release is the only one that gets fixes. This is a single-file-scale
FreshRSS extension; upgrading means replacing the directory, so there is nothing
a backport would buy anyone.

It is written against **FreshRSS 1.29.0 or newer** and CI analyses it against
exactly that release. A problem that only appears on an older FreshRSS is out of
scope.

## Reporting a vulnerability

**Please do not open a public issue for a security problem.**

Write to **hi@brtsz.de** instead. Say "Note Drop" somewhere in the subject so
it does not get read as a question about something else.

What to expect:

* An acknowledgement within **7 days**. If you have not heard anything by then,
  assume the mail went astray and send it again.
* An assessment — whether it is a vulnerability, and how bad — within **30 days**.
* A fix released before the details are published. The release and the changelog
  entry come first, the advisory afterwards, so that upgrading is possible the
  moment the problem is public.

You will be credited in the changelog unless you would rather not be.

## What to put in the report

* **FreshRSS version** — from *About*, e.g. 1.29.1.
* **Database backend** — SQLite, MySQL/MariaDB, or PostgreSQL. Only SQLite is
  exercised end to end in a running installation; the schema statements for the
  other two are executed in CI but the extension has not been run on them.
* **Browser and version**, if the copy button or anything else on the page is
  involved.
* **The note content that reproduces it** — the exact bytes. A note is typed or
  pasted by the logged-in user, but it is still rendered and copied back, so
  the precise input is usually the whole story.
* **What you saw and what you expected**, and whether it needs an authenticated
  session.

## What is already known and deliberate

Not vulnerabilities, and worth listing before they are reported:

* **Notes are stored in plain text.** They sit in FreshRSS' database behind
  FreshRSS' login like every other user data — feeds, favourites — with no
  additional encryption. The README says the same: this is a drop box for
  links and notes in transit, not a vault. Secrets belong in a password
  manager.
* **Disabling the extension does not delete the notes table.** FreshRSS calls
  an extension's uninstall step when it is merely *disabled*, so dropping the
  table there would let one stray click destroy every note. The "delete all
  notes" button is what removes the data.
* **A non-`http(s)` note is shown as plain text**, never as a link. A
  `javascript:` URL pasted as a note stays visible but is never clickable.

## Scope

In scope: this repository's PHP, JavaScript, SQL and CI configuration.

Out of scope: FreshRSS itself (report to
[FreshRSS/FreshRSS](https://github.com/FreshRSS/FreshRSS/security)), the web
server, and anything that requires an attacker to already have the user's
FreshRSS session.
