<?php

/**
 * Database handling
 */

namespace EasyTransfer\Data;

use DateTimeImmutable;
use InvalidArgumentException;
use mysqli;
use stdClass;

class Database {

	private mysqli $db;
	private string $sqlDir;
	private const TABLES = [
		'users',
		'text',
	];

	public function __construct() {
		$this->db = new mysqli(
			EASY_TRANSFER_DB_HOST,
			EASY_TRANSFER_DB_USER,
			EASY_TRANSFER_DB_PASS,
			EASY_TRANSFER_DB_NAME
		);
		$this->sqlDir = dirname( __DIR__, 2 ) . '/sql/';
	}

	public function __destruct() {
		// Close the connection
		$this->db->close();
	}

	public function ensureDatabase() {
		foreach ( self::TABLES as $table ) {
			$res = $this->db->query( "SHOW TABLES LIKE '$table';" );
			if ( $res->num_rows === 0 ) {
				$path = $this->sqlDir . "/table-$table.sql";
				$this->db->query( file_get_contents( $path ) );
			}
		}
	}

	public function clearTables() {
		// On the next page view ensureDatabase() will recreate the tables
		foreach ( self::TABLES as $table ) {
			$this->db->query( "DROP TABLE $table;" );
		}
	}

	public static function setup() {
		// So that the constructor can select the database without errors when
		// it doesn't exist (on docker)
		$mysqli = new mysqli(
			EASY_TRANSFER_DB_HOST,
			EASY_TRANSFER_DB_USER,
			EASY_TRANSFER_DB_PASS
		);
		$mysqli->query(
			"CREATE DATABASE IF NOT EXISTS " . EASY_TRANSFER_DB_NAME
		);
		// close the connection
		$mysqli->close();
		$db = new Database;
		$db->ensureDatabase();
		$db->ensureManagerAccount();
	}

	private function ensureManagerAccount(): void {
		// Make sure that EASY_TRANSFER_MANAGEMENT_EMAIL has a management
		// account with the password from EASY_TRANSFER_MANAGEMENT_PASSWORD
		// Not using ON DUPLICATE KEY UPDATE since that messes with the
		// AUTO_INCREMENT user_id
		// Need variables so that they can be passed by reference to
		// bind_param()
		$passHash = UserAccount::hashPassword( EASY_TRANSFER_MANAGER_PASS );
		$email = EASY_TRANSFER_MANAGER_EMAIL;
		$currUser = $this->getAccountByEmail( $email );

		if ( $currUser ) {
			// Set both flags and password if either one is wrong, to simplify;
			// but, allow the flags to change premium or not, just enforcing
			// the presence of FLAG_MANAGER
			if ( $currUser->hasFlag( UserAccount::FLAG_MANAGER )
				&& $currUser->matchesPassword( EASY_TRANSFER_MANAGER_PASS )
			) {
				return;
			}
			$newFlags = $currUser->getFlags() | UserAccount::FLAG_MANAGER;
			$query = $this->db->prepare(
				'UPDATE users SET user_pass_hash = ?, user_flags = ? WHERE' .
				' user_id = ?'
			);
			$managerId = $currUser->getId();
			$query->bind_param( 'sdd', $passHash, $newFlags, $managerId );
			$query->execute();
			return;
		}
		// Adding a new user
		$user = UserAccount::newUninserted(
			EASY_TRANSFER_MANAGER_EMAIL,
			EASY_TRANSFER_MANAGER_PASS,
			UserAccount::FLAG_MANAGER
		);
		$user->insertInto( $this );
	}

	public function getAccountByEmail( string $email ): ?UserAccount {
		$email = strtolower( $email );
		$query = $this->db->prepare( 'SELECT * FROM users WHERE user_email = ?' );
		$query->bind_param( 's', $email );
		$query->execute();
		$rows = $query->get_result()->fetch_all( MYSQLI_ASSOC );
		if ( count( $rows ) === 0 ) {
			return null;
		}
		return UserAccount::newFromRow( (object)( $rows[0]) );
	}
	public function getAccountById( int $userId ): UserAccount {
		$query = $this->db->prepare( 'SELECT * FROM users WHERE user_id = ?' );
		$query->bind_param( 'd', $userId );
		$query->execute();
		// Assumed to exist
		$rows = $query->get_result()->fetch_all( MYSQLI_ASSOC );
		if ( count( $rows ) === 0 ) {
			throw new InvalidArgumentException(
				"No user exists with ID $userId"
			);
		}
		return UserAccount::newFromRow( (object)( $rows[0] ) );
	}

	public function getAllUsers(): array {
		$query = $this->db->prepare(
			'SELECT user_id, user_email, user_flags FROM users'
		);
		$query->execute();
		return array_map(
			static fn ( $arr ) => (object)( $arr ),
			$query->get_result()->fetch_all( MYSQLI_ASSOC )
		);
	}

	// Only for use by UserAccount::insertInto()
	public function insertAccount(
		string $email,
		string $passHash,
		int $flags
	): int {
		$query = $this->db->prepare(
			'INSERT INTO users (user_email, user_pass_hash, user_flags) ' .
			'VALUES (?, ?, ?)'
		);
		$query->bind_param( 'ssd', $email, $passHash, $flags );
		$query->execute();
		return $this->db->insert_id;
	}

	// Only for use by UserAccount::updateFlagsIn()
	public function updateAccountFlags( int $userId, int $flags ): void {
		$query = $this->db->prepare(
			'UPDATE users SET user_flags = ? WHERE user_id = ?'
		);
		$query->bind_param( 'dd', $flags, $userId );
		$query->execute();
	}

	public function getUserTransfer( int $userId ): ?Transfer {
		$query = $this->db->prepare(
			'SELECT text_content FROM text WHERE text_user = ?'
		);
		$query->bind_param( 'd', $userId );
		$query->execute();
		$result = $query->get_result();
		$rows = $result->fetch_all( MYSQLI_ASSOC );
		if ( $rows === [] ) {
			return null;
		}
		return Transfer::newFromDatabase( $rows[0]['text_content'] );
	}

	public function setUserTransfer( int $userId, Transfer $transfer ) {
		// Okay to use ON DUPLICATE KEY UPDATE since there is no AUTO_INCREMENT
		// key
		$query = $this->db->prepare(
			'INSERT INTO text (text_user, text_content) VALUES (?, ?)'
				. ' ON DUPLICATE KEY UPDATE text_content = ?'
		);
		$val = $transfer->getDBencoding();
		$query->bind_param( 'dss', $userId, $val, $val );
		$query->execute();
	}

}