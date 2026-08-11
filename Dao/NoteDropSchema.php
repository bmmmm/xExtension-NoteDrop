<?php
declare(strict_types=1);

/**
 * Every SQL string the extension runs, per dialect and with nothing else in it.
 *
 * Split out of NoteDropDAO so that it can be executed without one: the DAO
 * extends Minz_ModelPdo and needs a FreshRSS context, which a test harness has
 * no way of standing up. tests/schema.php requires this file and runs these
 * exact strings against a real database, so the statements are checked rather
 * than described.
 *
 * The table is written as `_notedrop_note` throughout: Minz_Pdo::autoPrefix()
 * replaces the leading backtick-underscore with the real prefix, which is empty
 * for SQLite (one database file per user) and `<prefix><user>_` for MySQL and
 * PostgreSQL, and Minz_PdoPgsql additionally turns the backticks into double
 * quotes. Anything running these outside FreshRSS has to do the same.
 *
 * `$dbType` is Minz_Pdo::dbType(): `mysql`, `pgsql`, or `sqlite`. Anything else
 * is treated as SQLite.
 */
final class NoteDropSchema {
	/** The columns every read returns, in the order the row shape declares them. */
	public const COLUMNS = 'id, content, created_at';

	/**
	 * Newest first. The id orders the same way `created_at` would — it only ever
	 * grows — and unlike the timestamp it cannot tie, so two notes dropped in the
	 * same second cannot swap places between renders.
	 */
	public const ORDER = 'ORDER BY id DESC';

	/**
	 * The statements that create the table, in order — one statement per element
	 * because PDO::exec() only runs the first one on some drivers.
	 *
	 * The id generates itself, which is the one thing the three dialects spell
	 * differently. SQLite gets the AUTOINCREMENT keyword on purpose: without it a
	 * deleted id can be handed out again, and a delete form sitting in a stale
	 * tab would then remove a note it was never pointed at.
	 *
	 * `content` stays TEXT on MySQL as well. Nothing here is ever indexed, and
	 * notes are typed or pasted by hand — 64 KiB is not a limit anyone reaches
	 * with text that was meant for a clipboard.
	 *
	 * @return list<string>
	 */
	public static function createTable(string $dbType): array {
		switch ($dbType) {
			case 'mysql':
				return [
					<<<'SQL'
						CREATE TABLE IF NOT EXISTS `_notedrop_note` (
							`id` BIGINT NOT NULL AUTO_INCREMENT,
							`content` TEXT NOT NULL,
							`created_at` BIGINT NOT NULL,
							PRIMARY KEY (`id`)
						) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
						ENGINE = INNODB
						SQL,
				];
			case 'pgsql':
				return [
					<<<'SQL'
						CREATE TABLE IF NOT EXISTS `_notedrop_note` (
							`id` BIGSERIAL PRIMARY KEY,
							`content` TEXT NOT NULL,
							`created_at` BIGINT NOT NULL
						)
						SQL,
				];
			default:
				return [
					<<<'SQL'
						CREATE TABLE IF NOT EXISTS `_notedrop_note` (
							`id` INTEGER PRIMARY KEY AUTOINCREMENT,
							`content` TEXT NOT NULL,
							`created_at` INTEGER NOT NULL
						)
						SQL,
				];
		}
	}

	/** No dialect branching: a plain INSERT reads the same on all three backends. */
	public static function insert(): string {
		return 'INSERT INTO `_notedrop_note` (content, created_at) VALUES (:content, :created_at)';
	}
}
