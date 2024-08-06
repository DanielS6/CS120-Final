<?php

/**
 * Set up everything that we need (like error reporting, sessions, and
 * autoloading). This should be the first thing included in all entry points.
 */

/**
 * Display all errors, make sure that the server is running as UTC, and ignore
 * user aborts on requests that could affect the database.
 */
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );
ini_set( 'date.timezone', 'UTC' );

if ( ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) === 'POST' ) {
	ignore_user_abort( true );
}

/**
 * EsayTransfer depends on the `mysqli` PHP extension (to interact with the
 * database) and on PHP 8.3 or later.
 */
if ( !extension_loaded( 'mysqli' ) ) {
	trigger_error( 'The `mysqli` extension is missing!', E_USER_ERROR );
}
if ( version_compare( PHP_VERSION, '8.3', '<' ) ) {
	trigger_error(
		'PHP 8.3+ is required, you are using ' . PHP_VERSION,
		E_USER_ERROR
	);
}

// Session
session_start();

// Autoloading of our classes
spl_autoload_register(
	static function ( string $className ) {
		if ( str_starts_with( $className, 'EasyTransfer\\' ) ) {
			// Trim off the `EasyTransfer\`
			$className = substr( $className, 13 );
			require_once str_replace( '\\', '/', $className ) . '.php';
		}
	}
);

/**
 * Database setup, so that local development with docker and production
 * deployment with siteground work the same without needing to change the
 * actual database interaction code.
 */
if ( getenv( 'EASY_TRANSFER_DOCKER' ) !== false ) {
	define( 'EASY_TRANSFER_DB_HOST', 'db' );
	define( 'EASY_TRANSFER_DB_USER', 'root' );
	define( 'EASY_TRANSFER_DB_PASS', 'root' );
	define( 'EASY_TRANSFER_DB_NAME', 'easy_transfer_db' );
} else {
	define( 'EASY_TRANSFER_DB_HOST', 'localhost' );
	define( 'EASY_TRANSFER_DB_USER', 'u5ibud7ary6e1' );
	define( 'EASY_TRANSFER_DB_PASS', 'u8rxgbugnixy' );
	define( 'EASY_TRANSFER_DB_NAME', 'dbqddxqu96wz1m' );
}

/**
 * Management account settings; in a production environment at least the
 * password would get read with getenv() or some other tool that doesn't leak
 * it, but this is just a demo
 */
define( 'EASY_TRANSFER_MANAGER_EMAIL', 'admin@easy-transfer.com' );
define( 'EASY_TRANSFER_MANAGER_PASS', '!aSecurePassword' );

// Database setup
\EasyTransfer\Data\Database::setup();
