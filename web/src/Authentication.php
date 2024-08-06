<?php

/**
 * Authentication handling
 */

namespace EasyTransfer;

use LogicException;

class Authentication {

	private const SESSION_KEY = 'easy_transfer_user_id';

	private function __construct() {
		$this->db = new Database;
		$this->userFlags = Management::FLAGS_NONE;
	}

	public static function loginSession( int $userId ): void {
		$_SESSION[self::SESSION_KEY] = $userId;
	}

	public static function isLoggedIn(): bool {
		return isset( $_SESSION[self::SESSION_KEY] );
	}

	public static function getLoggedInUserId(): int {
		if ( !self::isLoggedIn() ) {
			throw new LogicException(
				__METHOD__ . ' can only be called when the user is logged in!'
			);
		}
		return (int)$_SESSION[self::SESSION_KEY];
	}

	public static function logOut(): void {
		// For future uses within the current session
		unset( $_SESSION[self::SESSION_KEY] );
		// for future page views
		session_destroy();
	}
}