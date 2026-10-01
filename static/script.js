'use strict';

// The drop box page: one button per note that puts the note's text on the
// clipboard, long notes cut down until expanded, and a guard that keeps a
// whitespace-only note from being posted.
(function () {
	// The class that cuts a note to its first lines (style.css).
	const CLAMPED = 'note-drop-clamped';
	// Whether a note is worth submitting at all — empty and whitespace-only are
	// not. The textarea deliberately carries no `required` (themes paint an
	// empty required field as an error, see the view), so this guard is the one
	// in front of the browser; the server ignores an empty note regardless.
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

	// Whether a cut-down note hides any of its text. The pixel of slack absorbs
	// the rounding between the two heights, which would otherwise offer to
	// expand a note that is already shown whole.
	function isOverflowing(scrollHeight, clientHeight) {
		return scrollHeight > clientHeight + 1;
	}

	// The label a toggle shows for the state it is in. Same arrangement as the
	// copy labels: translated by the view, English only as the net.
	function toggleLabel(expanded, dataset) {
		return expanded
			? (dataset.labelCollapse || 'Collapse')
			: (dataset.labelExpand || 'Expand');
	}

	function contentFor(toggle) {
		return document.getElementById(toggle.getAttribute('aria-controls'));
	}

	// Cuts the note down if, at the width it is shown at, it runs past the cut,
	// and shows its toggle only then. Measured with the cut applied: scrollHeight
	// is the whole text, clientHeight what the cut leaves of it. An expanded
	// note is left alone — whoever opened it is reading it.
	function fitNote(toggle) {
		const content = contentFor(toggle);
		if (content === null || toggle.getAttribute('aria-expanded') === 'true') {
			return;
		}
		content.classList.add(CLAMPED);
		const overflowing = isOverflowing(content.scrollHeight, content.clientHeight);
		content.classList.toggle(CLAMPED, overflowing);
		toggle.hidden = !overflowing;
	}

	function fitAll() {
		document.querySelectorAll('.note-drop-toggle').forEach(fitNote);
	}

	function toggleNote(toggle) {
		const content = contentFor(toggle);
		if (content === null) {
			return;
		}
		const expanded = toggle.getAttribute('aria-expanded') !== 'true';
		toggle.setAttribute('aria-expanded', String(expanded));
		toggle.textContent = toggleLabel(expanded, toggle.dataset);
		content.classList.toggle(CLAMPED, !expanded);
		if (!expanded) {
			// Collapsing from the bottom of a long note: a browser with scroll
			// anchoring keeps the button where it was, one without keeps the
			// scroll offset, and the cut-down note ends up above the screen.
			// Either way the note's start belongs in view.
			toggle.closest('.note-drop-item').scrollIntoView({ block: 'nearest' });
		}
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
			const toggle = ev.target.closest && ev.target.closest('.note-drop-toggle');
			if (toggle) {
				toggleNote(toggle);
			}
		});

		// The script is loaded async, so the list may not be parsed yet. Measured
		// again once the web fonts are in — they change how many lines a note
		// takes —, once the page has loaded, and after the width changes, e.g. a
		// phone turned sideways. The load event is the only one of these that
		// waits for style.css: measured before it arrives, the cut has no rule
		// yet, every note seems to fit, and no long note would be cut.
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fitAll);
		} else {
			fitAll();
		}
		if (document.fonts) {
			document.fonts.ready.then(fitAll);
		}
		if (document.readyState !== 'complete') {
			window.addEventListener('load', fitAll);
		}
		let resizeTimer;
		window.addEventListener('resize', function () {
			window.clearTimeout(resizeTimer);
			resizeTimer = window.setTimeout(fitAll, 150);
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
			isOverflowing: isOverflowing,
			toggleLabel: toggleLabel,
		};
		return;
	}

	init();
})();
