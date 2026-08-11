'use strict';

const assert = require('node:assert/strict');
const { test } = require('node:test');

// Loading the browser file under Node takes the `typeof document` branch and
// yields the pure helpers; nothing here needs a DOM.
const { isSubmittableNote, copyResultLabel } = require('../static/script.js');

test('a note with content is submittable', () => {
	assert.equal(isSubmittableNote('hello'), true);
	assert.equal(isSubmittableNote('  padded  '), true);
	assert.equal(isSubmittableNote('line one\nline two'), true);
});

test('an empty or whitespace-only note is not', () => {
	assert.equal(isSubmittableNote(''), false);
	assert.equal(isSubmittableNote('   \n\t '), false);
});

test('a missing value is not submittable either', () => {
	assert.equal(isSubmittableNote(undefined), false);
	assert.equal(isSubmittableNote(null), false);
});

test('the copy outcome shows the label the page sent', () => {
	const dataset = { labelCopied: 'Kopiert', labelFailed: 'Kopieren fehlgeschlagen' };
	assert.equal(copyResultLabel(true, dataset), 'Kopiert');
	assert.equal(copyResultLabel(false, dataset), 'Kopieren fehlgeschlagen');
});

test('a missing label falls back to English rather than to nothing', () => {
	assert.equal(copyResultLabel(true, {}), 'Copied');
	assert.equal(copyResultLabel(false, {}), 'Copy failed');
});
