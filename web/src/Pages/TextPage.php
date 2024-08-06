<?php

namespace EasyTransfer\Pages;

use EasyTransfer\HTML\HTMLBuilder;

class TextPage extends BasePage {

	public function __construct() {
		parent::__construct( 'Text' );
		$this->addStyleSheet( 'transfer-styles.css' );
	}

	protected function buildPage(): void {
		$this->addBodyElement(
			HTMLBuilder::element( 'h1', 'Text transfer' )
		);
		$text = $_GET['text'] ?? null;
		if ( $text === null ) {
			$this->addBodyElement(
				HTMLBuilder::element(
					'div',
					'No text found in URL',
					[ 'class' => 'et-error' ]
				)
			);
			return;
		}

		$this->addBodyElement(
			HTMLBuilder::element( 'p', 'Transferred text:' )
		);
		$this->addBodyElement(
			HTMLBuilder::element(
				'textarea',
				$text,
				[ 'id' => 'et-tranferred-text', 'readonly' => true ]
			)
		);
	}

}