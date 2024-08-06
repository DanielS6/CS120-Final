<?php

namespace EasyTransfer\Data;

use stdClass;

class UserAccount {
	private ?int $id;
	private string $email;
	private string $passHash;
	private int $flags;

	public const FLAGS_NONE = 0;
	// Paid accounts
	public const FLAG_PREMIUM = 1;

	// Access to the front end management to manage paid accounts, leaving some
	// extra space between the premium flag and the management flag in case
	// there are more flags needed in the future
	public const FLAG_MANAGER = 64;

	private function __construct(
		?int $id,
		string $email,
		string $passHash,
		int $flags
	) {
		$this->id = $id;
		$this->email = strtolower( $email );
		$this->passHash = $passHash;
		$this->flags = $flags;
	}

	public static function hashPassword( string $raw ): string {
		// No, this isn't secure, but it at least centralizes where to fix the
		// logic when it does become secure; public for use with
		// Database::ensureManagerAccount()
		return md5( $raw );
	}

	// A user that hasn't been added to the database yet
	public static function newUninserted(
		string $email,
		string $password,
		int $flags
	): UserAccount {
		return new UserAccount(
			null,
			$email,
			self::hashPassword( $password ),
			$flags
		);
	}

	// A user that exists in the database
	public static function newFromRow( stdClass $row ): UserAccount {
		return new UserAccount(
			$row->user_id,
			$row->user_email,
			$row->user_pass_hash,
			$row->user_flags
		);
	}

	public function hasId(): bool {
		return $this->id !== null;
	}

	public function getId(): int {
		if ( $this->id === null ) {
			throw new LogicException( "getId() called on uninserted account" );
		}
		return $this->id;
	}

	public function getEmail(): string {
		return $this->email;
	}

	// No public access to the password hash, you can just check matches
	public function matchesPassword( string $toCheck ): bool {
		return $this->passHash === self::hashPassword( $toCheck );
	}

	public function insertInto( Database $db ): void {
		if ( $this->id !== null ) {
			throw new LogicException( "Cannot insert an existing account!" );
		}
		$this->id = $db->insertAccount(
			$this->email,
			$this->passHash,
			$this->flags
		);
	}

	public function getFlags(): int {
		return $this->flags;
	}

	public function updateFlagsIn( Database $db, int $newFlags ): void {
		if ( $this->id === null ) {
			throw new LogicException(
				"Cannot update flags for uninserted account!"
			);
		}
		$db->updateAccountFlags( $this->id, $newFlags );
		$this->flags = $newFlags;
	}

	public function hasFlag( int $toCheck ): bool {
		return ( ( $this->flags & $toCheck ) === $toCheck );
	}

}