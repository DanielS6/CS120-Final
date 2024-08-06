<?php

namespace EasyTransfer\Pages;

use EasyTransfer\Authentication;
use EasyTransfer\HTML\HTMLBuilder;

class LogoutPage extends BasePage {

	public function __construct() {
		parent::__construct( 'Logout' );
	}

	protected function buildPage(): void {
		Authentication::logOut();
		$this->addBodyElement(
			HTMLBuilder::element(
				'div',
				[
					HTMLBuilder::element( 'h1', 'Log out' ),
					HTMLBuilder::element(
						'p',
						'Log out successful'
					),
				],
				[ 'class' => 'center-table' ]
			)
		);
	}

}