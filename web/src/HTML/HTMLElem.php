<?php

/** HTML element that has already been created, shouldn't be escaped again */

namespace EasyTransfer\HTML;

class HTMLElem {
	private string $contents;

	public function __construct( string $contents ) {
		$this->contents = $contents;
	}

	public function __toString(): string {
		return $this->contents;
	}

}