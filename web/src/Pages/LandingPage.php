<?php

namespace EasyTransfer\Pages;

use EasyTransfer\HTML\HTMLBuilder;

class LandingPage extends BasePage {

	public function __construct() {
		parent::__construct( 'EasyTransfer' );
	}

	protected function buildPage(): void {
		$this->addBodyElement(
			HTMLBuilder::element(
				'div',
				[
					HTMLBuilder::element( 'h1', 'EasyTransfer' ),
					HTMLBuilder::element(
						'p',
						'Welcome to EasyTransfer!'
					),
					HTMLBuilder::element(
						'p',
						'With EasyTransfer, you can easily copy text content' .
						' from one device to another by generating a QR code' .
						' that encodes the desired text. For URLs, the code' .
						' will take you directly to that page; for text, the' .
						' code will bring you to a page where the text can be' .
						' copied.'
					),
					HTMLBuilder::element(
						'p',
						'If you want to send something to a device that cannot' .
						' scan QR codes, you will need to create an account,' .
						' and then the text will be stored with your account.'
					),
					HTMLBuilder::element(
						'p',
						'Note that for free accounts text is only stored for' .
						' one minute, and this tool is meant to be used to' .
						' transfer between two devices in real time. If you' .
						' want to save the text for later, you will need to' .
						' purchase the premium version.'
					)
				]
			)
		);
	}

}