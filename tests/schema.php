<?php
declare(strict_types=1);

// The part of the extension a running instance would only ever tell us about by
// failing at install time: the CREATE TABLE statements, two dialects of which
// the development installation never runs. Exercised against a real database
// rather than mocked — what is being checked is what the database does with
// those statements.
//
// The statements are the real ones. NoteDropSchema exists so that they can be
// required without a FreshRSS context — the DAO around them extends
// Minz_ModelPdo and cannot be instantiated here, but the SQL it runs is exactly
// what this file executes, so an edit there is caught rather than silently
// diverging from a copy.
//
// Which database it runs against comes from the environment, default in-memory
// SQLite:
//
//   php tests/schema.php
//   NOTEDROP_TEST_DSN='mysql:host=127.0.0.1;dbname=notedrop' \
//     NOTEDROP_TEST_USER=root NOTEDROP_TEST_PASSWORD=secret php tests/schema.php
//   NOTEDROP_TEST_DSN='pgsql:host=127.0.0.1;dbname=notedrop' \
//     NOTEDROP_TEST_USER=postgres NOTEDROP_TEST_PASSWORD=secret php tests/schema.php
//
// The MySQL and PostgreSQL legs run in CI against service containers
// (.github/workflows/ci.yml).
//
// The error mode matches Minz_ModelPdo (ERRMODE_SILENT): a failure is a false
// return read back by the code, not an exception.

require_once __DIR__ . '/../Dao/NoteDropSchema.php';

$dsn = getenv('NOTEDROP_TEST_DSN');
if (!is_string($dsn) || $dsn === '') {
	$dsn = 'sqlite::memory:';
}
$user = getenv('NOTEDROP_TEST_USER');
$password = getenv('NOTEDROP_TEST_PASSWORD');

// Minz_Pdo::dbType() by another route: the driver in the DSN is the same string.
$dbType = strtolower(substr($dsn, 0, (int)strpos($dsn . ':', ':')));
if (!in_array($dbType, ['mysql', 'pgsql', 'sqlite'], true)) {
	fwrite(STDERR, "unsupported DSN: {$dsn}\n");
	exit(1);
}
echo "database: {$dbType}\n";

try {
	$pdo = new PDO($dsn, $user === false ? null : $user, $password === false ? null : $password, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
	]);
} catch (PDOException $e) {
	fwrite(STDERR, 'cannot connect to ' . $dsn . ': ' . $e->getMessage() . "\n");
	exit(1);
}

$failures = 0;
$check = static function (string $what, bool $ok) use (&$failures): void {
	echo $ok ? 'ok   ' : 'FAIL ', $what, "\n";
	if (!$ok) {
		$failures++;
	}
};

/**
 * What Minz_Pdo does to a statement on its way to the driver: `\`_` becomes the
 * table prefix (Minz_Pdo::autoPrefix()), and on PostgreSQL the remaining
 * backticks become double quotes (Minz_PdoPgsql::preSql()).
 */
$prefix = 'ndtest_';
$prepareSql = static function (string $sql) use ($dbType, $prefix): string {
	$sql = str_replace('`_', '`' . $prefix, $sql);
	return $dbType === 'pgsql' ? str_replace('`', '"', $sql) : $sql;
};

/** Runs a statement that is meant to work; a failure here is a broken test, not a finding. */
$exec = static function (string $sql) use ($pdo): void {
	if ($pdo->exec($sql) === false) {
		fwrite(STDERR, 'setup failed: ' . json_encode($pdo->errorInfo()) . "\n  " . $sql . "\n");
		exit(1);
	}
};

/** Column values arrive as mixed and differ in type per driver, so everything is compared as text. */
$asString = static fn(mixed $value): string => is_scalar($value) ? (string)$value : '';

// MySQL and PostgreSQL keep their database between runs, so start from nothing
// rather than from whatever the last run left behind.
$exec($prepareSql('DROP TABLE IF EXISTS `_notedrop_note`'));

// --- A fresh install ---------------------------------------------------------

foreach (NoteDropSchema::createTable($dbType) as $sql) {
	$exec($prepareSql($sql));
}
$check(
	'the created table has every column the code reads',
	$pdo->query($prepareSql('SELECT ' . NoteDropSchema::COLUMNS . ' FROM `_notedrop_note` LIMIT 1')) !== false
);
$check(
	'creating it twice is not an error',
	array_reduce(
		NoteDropSchema::createTable($dbType),
		static fn(bool $ok, string $sql): bool => $ok && $pdo->exec($prepareSql($sql)) !== false,
		true
	)
);

// --- The insert, with the real bindings --------------------------------------
// The id has to generate itself on all three backends, and a multi-line,
// non-ASCII note has to come back byte for byte — it is headed for a clipboard.

$add = static function (string $content, int $timestamp) use ($pdo, $prepareSql): void {
	$stm = $pdo->prepare($prepareSql(NoteDropSchema::insert()));
	if ($stm === false) {
		fwrite(STDERR, 'cannot prepare the insert: ' . json_encode($pdo->errorInfo()) . "\n");
		exit(1);
	}
	$stm->bindValue(':content', $content, PDO::PARAM_STR);
	$stm->bindValue(':created_at', $timestamp, PDO::PARAM_INT);
	if (!$stm->execute()) {
		fwrite(STDERR, 'cannot insert: ' . json_encode($stm->errorInfo()) . "\n");
		exit(1);
	}
};

$multiline = "first line\nsecond line — ü, €, 話";
$add('oldest', 100);
$add($multiline, 200);
// The same second as the note before it: only the id can order these two.
$add('https://example.net/path?a=1', 200);

/** @return list<array<string,mixed>> */
$listed = static function () use ($pdo, $prepareSql): array {
	$sql = 'SELECT ' . NoteDropSchema::COLUMNS . ' FROM `_notedrop_note` ' . NoteDropSchema::ORDER;
	$stm = $pdo->query($prepareSql($sql));
	if ($stm === false) {
		fwrite(STDERR, 'cannot list: ' . json_encode($pdo->errorInfo()) . "\n");
		exit(1);
	}
	$rows = [];
	foreach ($stm->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
		if (is_array($row)) {
			$rows[] = $row;
		}
	}
	return $rows;
};

$rows = $listed();
$contents = array_map(static fn(array $row): string => $asString($row['content'] ?? null), $rows);
$ids = array_map(static fn(array $row): string => $asString($row['id'] ?? null), $rows);

$check('every note is listed, newest first, ties broken by id', $contents === [
	'https://example.net/path?a=1',
	$multiline,
	'oldest',
]);
$check('the ids generated themselves', $ids !== [] && !in_array('', $ids, true) && count(array_unique($ids)) === 3);
$check('a multi-line non-ASCII note comes back byte for byte', $contents[1] === $multiline);

// --- Delete and clear --------------------------------------------------------

$stm = $pdo->prepare($prepareSql('DELETE FROM `_notedrop_note` WHERE id = :id'));
$deleted = $stm !== false && $stm->bindValue(':id', $ids[0], PDO::PARAM_STR) && $stm->execute();
$check('a note can be deleted by its id, bound as a string', $deleted === true && count($listed()) === 2);

$exec($prepareSql('DELETE FROM `_notedrop_note`'));
$check('clearing leaves the box empty', $listed() === []);

// Leave nothing behind in a database that outlives the process.
$exec($prepareSql('DROP TABLE IF EXISTS `_notedrop_note`'));

echo $failures === 0 ? "\nall checks passed\n" : "\n{$failures} check(s) failed\n";
exit($failures === 0 ? 0 : 1);
