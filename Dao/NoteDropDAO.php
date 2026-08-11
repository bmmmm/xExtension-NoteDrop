<?php
declare(strict_types=1);

require_once __DIR__ . '/NoteDropSchema.php';

/**
 * The notes live in a table of their own, denormalised to nothing: a note is a
 * string and the moment it was dropped, and it references no feed, entry or
 * category that could be purged from under it.
 *
 * Deliberately no `FreshRSS_` prefix on the class name — that namespace belongs
 * to core and a future release could collide with it.
 *
 * The table name is written as `_notedrop_note` throughout: Minz_Pdo::autoPrefix()
 * turns the leading backtick-underscore into the real prefix, which is empty for
 * SQLite (one database file per user) and `<prefix><user>_` for MySQL and
 * PostgreSQL. Per-user separation comes out of that for free.
 *
 * @phpstan-type NoteDropRow array{id:string, content:string, created_at:int}
 */
final class NoteDropDAO extends Minz_ModelPdo {
	/**
	 * Once per process and per table: install() creates the table, but an
	 * installation where that never ran (enabled by hand, restored from a backup)
	 * would otherwise fail on every single request instead of repairing itself once.
	 *
	 * Keyed by the PDO prefix rather than a plain flag, because `_notedrop_note` is
	 * a different table per user on MySQL and PostgreSQL. One process serving two
	 * users would otherwise take the first user's table as proof that the second
	 * one's exists.
	 *
	 * @var array<string,true>
	 */
	private static array $tableChecked = [];

	/** @return bool false if the table is missing and could not be created */
	public function ensureTableExists(): bool {
		$key = $this->pdo->prefix();
		if (isset(self::$tableChecked[$key])) {
			return true;
		}
		foreach (NoteDropSchema::createTable($this->pdo->dbType()) as $sql) {
			if ($this->pdo->exec($sql) === false) {
				Minz_Log::error('NoteDrop: cannot create table: ' . json_encode($this->pdo->errorInfo()));
				return false;
			}
		}
		self::$tableChecked[$key] = true;
		return true;
	}

	public function add(string $content, int $timestamp): bool {
		if (!$this->ensureTableExists()) {
			return false;
		}
		$stm = $this->pdo->prepare(NoteDropSchema::insert());
		if ($stm !== false &&
			$stm->bindValue(':content', $content, PDO::PARAM_STR) &&
			$stm->bindValue(':created_at', $timestamp, PDO::PARAM_INT) &&
			$stm->execute()) {
			return true;
		}
		$info = $stm === false ? $this->pdo->errorInfo() : $stm->errorInfo();
		Minz_Log::error('NoteDrop: cannot add a note: ' . json_encode($info));
		return false;
	}

	/**
	 * Every note, newest first. Deliberately not paginated: this is a drop box
	 * that is emptied as it is used, not an archive that grows without bound —
	 * pagination becomes worth its complexity when someone's box proves otherwise
	 * (TODO.md).
	 *
	 * @return list<NoteDropRow>
	 */
	public function listAll(): array {
		if (!$this->ensureTableExists()) {
			return [];
		}
		$sql = 'SELECT ' . NoteDropSchema::COLUMNS . ' FROM `_notedrop_note` ' . NoteDropSchema::ORDER;
		$stm = $this->pdo->query($sql);
		if ($stm === false) {
			Minz_Log::error('NoteDrop: cannot list the notes: ' . json_encode($this->pdo->errorInfo()));
			return [];
		}
		$rows = [];
		/** @var array<string,mixed> $row */
		foreach ($stm->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
			$rows[] = self::normaliseRow($row);
		}
		return $rows;
	}

	public function delete(string $id): bool {
		if (!$this->ensureTableExists()) {
			return false;
		}
		$stm = $this->pdo->prepare('DELETE FROM `_notedrop_note` WHERE id = :id');
		if ($stm !== false && $stm->bindValue(':id', $id, PDO::PARAM_STR) && $stm->execute()) {
			return true;
		}
		$info = $stm === false ? $this->pdo->errorInfo() : $stm->errorInfo();
		Minz_Log::error('NoteDrop: cannot delete note ' . $id . ': ' . json_encode($info));
		return false;
	}

	public function clear(): bool {
		if (!$this->ensureTableExists()) {
			return false;
		}
		if ($this->pdo->exec('DELETE FROM `_notedrop_note`') === false) {
			Minz_Log::error('NoteDrop: cannot clear the notes: ' . json_encode($this->pdo->errorInfo()));
			return false;
		}
		return true;
	}

	/**
	 * SQLite hands back integers where MySQL hands back strings for the same
	 * columns, so callers get one shape either way. The id stays a string like
	 * core treats its own BIGINT ids — bound as PDO::PARAM_STR on the way back in,
	 * it never has to fit a 32-bit int.
	 *
	 * @param array<string,mixed> $row
	 * @return NoteDropRow
	 */
	private static function normaliseRow(array $row): array {
		return [
			'id' => is_scalar($row['id'] ?? null) ? (string)$row['id'] : '',
			'content' => is_scalar($row['content'] ?? null) ? (string)$row['content'] : '',
			'created_at' => is_numeric($row['created_at'] ?? null) ? (int)$row['created_at'] : 0,
		];
	}
}
