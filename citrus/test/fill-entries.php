#!/usr/bin/php
<?php
namespace njpanderson\Citrus;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Fill the DB tables citrus_uris and citrus_entries with URI data
 * in order to allow load testing on purge requests.
 */
class EntryFill
{
	private $args;
	private $db;
	private $stmts;
	private $uriSuffix = '&citrusfilltest=1';
	private $hashAlgo = 'crc32';
	private $date;

	public function __construct()
	{
		$this->args = array_merge(array(
			'p' => 'craft_',
			'uri' => '/',
			'host' => 'localhost',
			'n' => 5
		), getopt(
			'e:hp:u:t:d:cn:',
			array(
				'dsn:',
				'uri:'
			)
		));

		$this->date = date('Y-m-d H:i:s');

		if ($this->init()) {
			$job = $this->getJob();

			switch ($job) {
				case 'clear':
					$this->clear();
					break;

				default:
					$this->fill();
			}
		}
	}

	public function init()
	{
		$this->write("Citrus Entry Filler");

		if (isset($this->args['h'])) {
			// Just output help
			$this->exit(array(
				'Arguments:',
				'-e [entry_id]     Define the entry ID (required)',
				'-h                Produce this help',
				'-p [prefix]       Define the table prefix (default \'craft_\')',
				'-d [db]           Database name (required)',
				'-t [table]        Database table name (required)',
				'-u [username]     Database username (required, password is requested on run)',
				'-n [num]          Number of URIs to create for the entry (default 100)',
				'-c                Clear all current test data (instead of inserting new data)',
				'',
				'--uri [prefix]    Define the URI prefix (default \'/\')',
				'--host [hostname] Define the DB hostname (default \'localhost\')'
			));
		}

		// Validation
		if (!isset($this->args['e'])
			|| !isset($this->args['u'])
			|| !isset($this->args['t'])
			|| !isset($this->args['d'])) {
			$this->write(
				"-e (entry id), -u (username), -d (db name), and -t (table name) must be defined."
			);
			exit;
		}

		// Sanity checking
		$this->args['n'] = (int) $this->args['n'];
		$this->args['e'] = (int) $this->args['e'];

		if ($this->args['n'] > 10000) {
			$this->args['n'] = 10000;
		} elseif ($this->args['n'] < 1) {
			$this->args['n'] = 1;
		}

		// Get DB password
		$this->write('Connecting to \'mysql:host=' . $this->args['host'] . ';dbname=' . $this->args['d'] . '\'...');

		$this->write('Password for user ' . $this->args['u'] . ': ', '');

		$handle = fopen('php://stdin', 'r');

		$this->args['password'] = trim(fgets($handle));

		try {
			// Connect to the DB
			$this->db = new PDO(
				'mysql:host=' . $this->args['host'] . ';dbname=' . $this->args['d'],
				$this->args['u'],
				$this->args['password'],
				array(
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
				)
			);

			// Get prepared statements
			$this->prepStatements();
		} catch (\Exception $e) {
			$this->error("Could not connect to DB - ", $e);
		}

		return true;
	}

	public function fill()
	{
		// Insert URIs
		$ids = $this->insertUris($this->args['n'], $this->args['uri']);

		// Insert entries
		$this->insertEntries($ids, $this->args['e']);

		$this->write(number_Format($this->args['n'], 0) . ' test URIs inserted.');
	}

	public function clear()
	{
		$this->execute('clear_uris', array(
			':uriSuffix' => '%' . $this->uriSuffix . '%'
		));

		$this->execute('clear_entries');

		$this->write('All test URIs and empty entries cleared.');
	}

	private function prepStatements()
	{
		$this->stmts = array(
			'uris' => $this->db->prepare("
				INSERT INTO
					`" . $this->args['p'] . "citrus_uris`(
						uriHash,
						uri,
						dateCreated,
						dateUpdated
					)
				VALUES (
					:uriHash,
					:uri,
					:date,
					:date
				)
			"),
			'entries' => $this->db->prepare("
				INSERT INTO
					`" . $this->args['p'] . "citrus_entries`(
						uriId,
						entryId,
						dateCreated,
						dateUpdated
					)
				VALUES (
					:uriId,
					:entryId,
					:date,
					:date
				)
			"),
			'clear_uris' => $this->db->prepare("
				DELETE FROM
					`" . $this->args['p'] . "citrus_uris`
				WHERE
					uri LIKE :uriSuffix
			"),
			'clear_entries' => $this->db->prepare("
				DELETE
					`" . $this->args['p'] . "citrus_entries`
				FROM
					`" . $this->args['p'] . "citrus_entries`
				LEFT JOIN
					`" . $this->args['p'] . "citrus_uris` ON (
						`" . $this->args['p'] . "citrus_uris`.id = `" . $this->args['p'] . "citrus_entries`.uriId
					)
				WHERE
					`" . $this->args['p'] . "citrus_uris`.id IS NULL
			")
		);
	}

	private function insertUris(int $count, string $prefix)
	{
		$result = array();
		$data = array(
			':uriHash' => '',
			':uri' => '',
			':date' => $this->date
		);

		for ($a = 0; $a < $count; $a += 1) {
			$data[':uri'] = $prefix . '?n=' . $this->uuid() . $this->uriSuffix;
			$data[':uriHash'] = $this->hash($data[':uri']);

			$this->write($data[':uri']);

			$this->execute('uris', $data);

			array_push($result, $this->db->lastInsertId());
		}

		return $result;
	}

	private function insertEntries(array $uriIds, int $entryId)
	{
		foreach ($uriIds as $id) {
			$this->execute('entries', array(
				':uriId' => $id,
				':entryId' => $entryId,
				':date' => $this->date
			));
		}
	}

	private function write($str, $terminator = "\n")
	{
		if (is_array($str)) {
			$str = implode("\n", $str);
		}

		echo $str . $terminator;
	}

	private function error($message, $e = false)
	{
		$this->write('Error - ' . $message . ($e ? ' ' . $e->getMessage() : ''));
		exit(1);
	}

	private function exit($str, $terminator = "\n")
	{
		$this->write($str, $terminator . $terminator);
		exit;
	}

	private function hash($str)
	{
		return hash($this->hashAlgo, $str);
	}

	private function uuid()
	{
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,
			// 48 bits for "node"
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff)
		);
	}

	private function getJob()
	{
		if (isset($this->args['c'])) {
			return 'clear';
		}

		return 'fill';
	}

	private function execute($stmt, $params = null)
	{
		$this->stmts[$stmt]->execute($params);

		if ($this->stmts[$stmt]->errorCode() != 0) {
			error($this->stmts[$stmt]->errorInfo()[1]);
		}
	}
}

// Get the ball rolling...
$fill = new EntryFill;
