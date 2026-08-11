<?php

return array(
	'note_drop' => array(
		'menu' => 'Note drop',
		'title' => 'Note drop',
		'empty' => 'Nothing here yet. Drop a note or a link from any device and pick it up on another one.',
		'form' => array(
			'placeholder' => 'A note, a link — anything to move to another device…',
			'submit' => 'Drop it',
		),
		'feedback' => array(
			'added' => 'Note dropped.',
			'add_failed' => 'The note could not be saved, see the FreshRSS logs.',
		),
		'action' => array(
			'copy' => 'Copy',
			'copied' => 'Copied',
			'copy_failed' => 'Copying failed — select and copy by hand',
			'open' => 'Open',
			'delete' => 'Delete',
			'clear' => 'Delete all notes',
		),
		'conf' => array(
			'where_help' => 'The drop box is a page of its own, in the header menu (top right, gear icon) next to “Logs” and “About” — or open it directly:',
		),
	),
);
