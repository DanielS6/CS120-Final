<?php

namespace EasyTransfer\Pages;

use EasyTransfer\Authentication;
use EasyTransfer\Data\Database;
use EasyTransfer\HTML\HTMLBuilder;
use EasyTransfer\HTML\HTMLElem;

class LoginPage extends BasePage {

	// true=success, false=not attempted, string=error
	private string|bool $loginResult;

	public function __construct() {
		parent::__construct( 'Login' );
		$this->addStyleSheet( 'form-styles.css' );
	}

	protected function buildPage(): void {
		$this->addBodyElement(
			HTMLBuilder::element( 'h1', 'Login' )
		);

		if ( Authentication::isLoggedIn() ) {
			$this->addBodyElement( $this->getAlreadyLoggedInError() );
			return;
		}

		if ( ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) === 'POST' ) {
			$this->loginResult = $this->trySubmit();
		} else {
			$this->loginResult = false;
		}

		if ( $this->loginResult === true ) {
			$this->addBodyElement(
				HTMLBuilder::element(
					'p',
					'Login successful!'
				)
			);
			$this->addBodyElement(
				HTMLBuilder::element(
					'a',
					HTMLBuilder::element(
						'button',
						'Go Home',
						[ 'class' => 'et-form-redirect' ]
					),
					[ 'href' => './index.php' ]
				)
			);
		} else {
			// Login failed or wasn't attempted
			$this->addBodyElement( $this->getForm() );
		}
	}

	private function trySubmit(): string|true {
		$email = $_POST['et-email'] ?? '';
		$pass = $_POST['et-password'] ?? '';
		if ( $email === '' ) {
			return 'Missing email';
		} elseif ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return 'Invalid email';
		} elseif ( $pass === '' ) {
			return 'Missing password';
		}
		$db = new Database;
		$accountInfo = $db->getAccountByEmail( $email );
		if ( $accountInfo === null ) {
			return 'Email not associated with an account';
		}
		if ( !$accountInfo->matchesPassword( $pass ) ) {
			return 'Incorrect password';
		}
		Authentication::loginSession( $accountInfo->getId() );
		return true;
	}

	private function getAlreadyLoggedInError(): HTMLElem {
		return HTMLBuilder::element(
			'div',
			'ERROR: Already logged in to an account!',
			[ 'class' => 'et-error' ]
		);
	}

	private function getForm(): HTMLElem {
		return HTMLBuilder::element(
			'form',
			$this->getFormFields(),
			[
				'id' => 'et-login',
				'action' => './login.php',
				'method' => 'POST',
			]
		);
	}

	private function getFormFields(): array {
		$formRows = [
			HTMLBuilder::formRow( 'Email:', 'email', 'et-email' ),
			HTMLBuilder::formRow( 'Password:', 'password', 'et-password' ),
		];
		$fields = [
			HTMLBuilder::element(
				'table',
				HTMLBuilder::element( 'tbody', $formRows )
			)
		];
		if ( is_string( $this->loginResult ) ) {
			$fields[] = HTMLBuilder::element(
				'p',
				$this->loginResult,
				[ 'class' => 'et-error' ]
			);
		}
		$fields[] = HTMLBuilder::element(
			'button',
			'Login',
			[ 'type' => 'submit', 'id' => 'et-login-submit', 'class' => 'et-form-button' ]
		);
		return $fields;
	}
}