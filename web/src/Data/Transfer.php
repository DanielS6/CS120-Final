<?php

/**
 * Represents the details of a transfer stored in the database; the transfer
 * is stored in a single TEXT field, that is encoded as follows:
 *   {metadata}|{text}
 * the metadata MUST NOT contain a `|`, and should indicate the
 *   - type of transfer
 *   - time the data was stored
 */

namespace EasyTransfer\Data;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use stdClass;

class Transfer {
	private TransferType $type;
	private DateTimeImmutable $created;
	private string $content;
	private string $forDb;

	private function __construct(
		TransferType $type,
		DateTimeImmutable $created,
		string $content
	) {
		$this->type = $type;
		$this->created = $created;
		$this->content = $content;
		$this->forDb = $type->value . '@' . $created->format( 'YmdHis' )
			. '|' . $content;
	}

	public static function newToSave(
		TransferType $type,
		string $content
	): Transfer {
		return new Transfer(
			$type,
			new DateTimeImmutable( "now", new DateTimeZone( "UTC" ) ),
			$content
		);
	}

	public static function newFromDatabase( string $fromDb ) {
		$type = TransferType::from( $fromDb[0] );
		$created = new DateTimeImmutable(
			substr( $fromDb, 2, 14 ),
			new DateTimeZone( "UTC" )
		);
		$content = substr( $fromDb, 17 );
		return new Transfer( $type, $created, $content );
	}

	public function getType(): TransferType {
		return $this->type;
	}

	public function hasExpired(): bool {
		$expirationTime = $this->created->add(
			DateInterval::createFromDateString( '1 minute' )
		);
		$now = new DateTimeImmutable( "now", new DateTimeZone( "UTC" ) );
		return ( $now > $expirationTime );
	}

	public function getContent(): string {
		return $this->content;
	}

	public function getDBencoding(): string {
		return $this->forDb;
	}
}