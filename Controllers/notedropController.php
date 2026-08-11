<?php
declare(strict_types=1);

/**
 * The view of the drop box page. Declaring the property (rather than setting it
 * dynamically on a plain FreshRSS_View) is what lets the template be analysed.
 */
final class NoteDropView extends FreshRSS_View {
	/** @var list<array{id:string, content:string, created_at:int}> */
	public array $notes = [];
}

/**
 * The class name and the file name are both fixed by registerController('notedrop'):
 * Minz_Dispatcher includes Controllers/<base>Controller.php and instantiates
 * FreshExtension_<base>_Controller (lib/Minz/Dispatcher.php). The base name has
 * to be alphanumeric because Minz_Request filters it with ctype_alnum, which is
 * why it is `notedrop` and not `note_drop`.
 *
 * No CSRF check anywhere in here: FreshRSS::initAuth() rejects every POST
 * without a valid token before a controller is ever reached, and its exemption
 * list holds only core's own login/register/refresh actions — no extension can
 * be on it.
 */
final class FreshExtension_notedrop_Controller extends FreshRSS_ActionController {
	/**
	 * @var NoteDropView
	 * @phpstan-ignore property.phpDocType
	 */
	protected $view;

	public function __construct() {
		parent::__construct(NoteDropView::class);
	}

	#[\Override]
	public function firstAction(): void {
		if (!FreshRSS_Auth::hasAccess()) {
			Minz_Error::error(403);
		}
	}

	public function indexAction(): void {
		$this->view->notes = (new NoteDropDAO())->listAll();

		FreshRSS_View::prependTitle(_t('ext.note_drop.title') . ' · ');
	}

	public function addAction(): void {
		if (!Minz_Request::isPost()) {
			Minz_Error::error(405);
			return;
		}

		// Plaintext on purpose: the raw text is what the copy button must later
		// reproduce, and the view escapes on the way out. A textarea arrives with
		// CRLF line endings — that is what the HTML spec makes a form submit —
		// which would otherwise travel into every later paste, so they are folded
		// here. paramString() has already trimmed the ends.
		$content = str_replace("\r\n", "\n", Minz_Request::paramString('content', true));
		if ($content === '') {
			// Nothing to store, nothing to report: the textarea is `required` and
			// the script guards the whitespace-only case, so this is a hand-made
			// POST — it gets the page back, like a GET would.
			$this->backToIndex();
			return;
		}

		if ((new NoteDropDAO())->add($content, time())) {
			Minz_Request::good(_t('ext.note_drop.feedback.added'), ['c' => 'notedrop', 'a' => 'index']);
		} else {
			Minz_Request::bad(_t('ext.note_drop.feedback.add_failed'), ['c' => 'notedrop', 'a' => 'index']);
		}
	}

	public function deleteAction(): void {
		if (!Minz_Request::isPost()) {
			Minz_Error::error(405);
			return;
		}
		$id = Minz_Request::paramString('id');
		if (ctype_digit($id)) {
			(new NoteDropDAO())->delete($id);
		}
		$this->backToIndex();
	}

	public function clearAction(): void {
		if (!Minz_Request::isPost()) {
			Minz_Error::error(405);
			return;
		}
		(new NoteDropDAO())->clear();
		$this->backToIndex();
	}

	private function backToIndex(): void {
		Minz_Request::forward(['c' => 'notedrop', 'a' => 'index'], true);
	}
}
