# TODO

* **Tag v0.1.0** once CI has passed on the first push, and keep the README's
  install step pointing at a release from then on (the sister repos already
  do).
* **Pagination** if a real drop box ever grows past what one page renders
  comfortably — `NoteDropDAO::listAll()` deliberately has no LIMIT today, on
  the theory that a drop box is emptied as it is used. Revisit when that
  theory meets someone's 500-note box.
