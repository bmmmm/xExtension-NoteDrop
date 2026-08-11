'use strict';

// The drop box page: one button per note that puts the note's text on the
// clipboard, and a guard that keeps a whitespace-only note from being posted.
(function () {
	// Whether a note is worth submitting at all. `required` on the textarea
	// already blocks the empty case in the browser; this also catches
	// whitespace-only, which the browser counts as filled in.
	function isSubmittableNote(text) {
		return typeof text === 'string' && text.trim() !== '';
	}

	// Which label a finished copy attempt should show. The labels arrive on the
	// button as data attributes, already translated by the view; the English
	// here is only the net under a template that lost one. Split out as a pure
	// function for the tests — the clipboard itself needs a browser.
	function copyResultLabel(ok, dataset) {
		return ok
			? (dataset.labelCopied || 'Copied')
			: (dataset.labelFailed || 'Copy failed');
	}

	// The note a copy button belongs to. textContent undoes the view's HTML
	// escaping, so what lands on the clipboard is the text as it was dropped.
	function noteTextFor(button) {
		const item = button.closest('.note-drop-item');
		const content = item && item.querySelector('.note-drop-content');
		return content ? content.textContent : '';
	}

	// Shows the outcome on the button itself, where the eyes already are, and
	// puts the original label back once there was time to read it. The timer
	// lives in a WeakMap rather than on the element: dataset can only hold
	// strings, and an expando property would be the one untyped thing here.
	const resetTimers = new WeakMap();
	function showOutcome(button, ok) {
		if (!('labelIdle' in button.dataset)) {
			button.dataset.labelIdle = button.textContent;
		}
		button.textContent = copyResultLabel(ok, button.dataset);
		window.clearTimeout(resetTimers.get(button));
		resetTimers.set(button, window.setTimeout(function () {
			button.textContent = button.dataset.labelIdle;
		}, 2000));
	}

	function copyNote(button) {
		const text = noteTextFor(button);
		// No async clipboard means no secure context (a plain-http install):
		// nothing this code can do about that, so it says so via the failed
		// label instead of pretending.
		if (text === '' || !navigator.clipboard) {
			showOutcome(button, false);
			return;
		}
		navigator.clipboard.writeText(text).then(
			function () { showOutcome(button, true); },
			function () { showOutcome(button, false); }
		);
	}

	function init() {
		// One delegated listener per event: the list re-renders with every add
		// and delete, and buttons that arrive later need no preparation.
		document.addEventListener('click', function (ev) {
			const button = ev.target.closest && ev.target.closest('.note-drop-copy');
			if (button) {
				copyNote(button);
			}
		});

		document.addEventListener('submit', function (ev) {
			const form = ev.target;
			if (!form.matches || !form.matches('.note-drop-add')) {
				return;
			}
			const area = form.querySelector('textarea[name="content"]');
			if (area === null || !isSubmittableNote(area.value)) {
				ev.preventDefault();
			}
		});
	}

	// Under the test runner there is no document and only the pure helpers are
	// exported; see tests/notedrop.test.js.
	if (typeof document === 'undefined') {
		module.exports = {
			isSubmittableNote: isSubmittableNote,
			copyResultLabel: copyResultLabel,
		};
		return;
	}

	init();
})();
