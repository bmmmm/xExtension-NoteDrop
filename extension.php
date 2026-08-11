<?php
declare(strict_types=1);

require_once __DIR__ . '/Dao/NoteDropDAO.php';

final class NoteDropExtension extends Minz_Extension {
	#[\Override]
	public function init(): void {
		parent::init();

		$this->registerController('notedrop');
		$this->registerViews();
		$this->registerTranslates();
		$this->registerHook(Minz_HookType::MenuOtherEntry, [$this, 'menuEntry']);

		// Both assets only do anything on the extension's own page, and the
		// routing has already happened by the time an extension is enabled for
		// the user (Minz_FrontController's constructor runs before
		// FreshRSS::init()), so they can be limited to it.
		if (Minz_Request::controllerName() === 'notedrop') {
			Minz_View::appendStyle($this->getFileUrl('style.css'));
			Minz_View::appendScript($this->getFileUrl('script.js'));
		}
	}

	#[\Override]
	public function handleConfigureAction(): void {
		parent::handleConfigureAction();

		// Extensions are only init()ed once they are enabled, while this action is
		// reached for any listed one, so the translations are registered here too.
		// Nothing is stored: the extension has no settings, and configure.phtml
		// only says where the page lives.
		$this->registerTranslates();
	}

	/**
	 * `metadata.json` says `"type": "user"` on purpose: user extensions are
	 * enabled per logged-in user, so this runs in that user's database context
	 * and every user ends up with their own table. As a system extension it
	 * would only ever run for the admin who flipped the switch.
	 *
	 * @return string|true true on success, an explanation otherwise
	 */
	#[\Override]
	public function install() {
		try {
			if (!(new NoteDropDAO())->ensureTableExists()) {
				return 'Note Drop: the notes table could not be created, see the FreshRSS logs.';
			}
		} catch (Exception $e) {
			return 'Note Drop: ' . $e->getMessage();
		}
		return true;
	}

	/**
	 * Deliberately does not drop the table. FreshRSS calls uninstall() when an
	 * extension is merely *disabled* in the extensions screen, not only when its
	 * files are removed (app/Controllers/extensionController.php), so dropping
	 * here would let one stray click destroy every note. Getting rid of the data
	 * is what the "delete all notes" button is for.
	 *
	 * @return string|true
	 */
	#[\Override]
	public function uninstall() {
		return true;
	}

	/**
	 * The drop box is reachable from every page, so the entry goes into the
	 * header dropdown rather than into the stream's own nav menu: nav_menu.phtml
	 * is only loaded from the three stream views, so a link placed there would
	 * disappear the moment it is followed.
	 */
	public function menuEntry(): string {
		// Text only, no icon: every other entry in this dropdown (Logs, About,
		// the configuration list) is plain text.
		$active = Minz_Request::controllerName() === 'notedrop' ? ' active' : '';
		return '<li class="item' . $active . '"><a href="' . _url('notedrop', 'index') . '">'
			. htmlspecialchars(_t('ext.note_drop.menu'), ENT_COMPAT, 'UTF-8') . '</a></li>';
	}
}
