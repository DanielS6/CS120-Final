<?php

namespace EasyTransfer\Pages;

use EasyTransfer\Authentication;
use EasyTransfer\HTML\HTMLBuilder;
use EasyTransfer\HTML\HTMLElem;
use EasyTransfer\Data\Database;
use EasyTransfer\Data\UserAccount;

class SignUpPage extends BasePage {

	// true=success, false=not attempted, string=error
	private string|bool $signUpResult;

	public function __construct() {
		parent::__construct( 'SignUp' );
		$this->addStyleSheet( 'form-styles.css' );
	}

	protected function buildPage(): void {
		$this->addBodyElement(
			HTMLBuilder::element( 'h1', 'Sign up' )
		);

		if ( Authentication::isLoggedIn() ) {
			$this->addBodyElement( $this->getAlreadyLoggedInError() );
			return;
		}
		if ( ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) === 'POST' ) {
			$this->signUpResult = $this->trySubmit();
		} else {
			$this->signUpResult = false;
		}

		if ( $this->signUpResult === true ) {
			$this->addBodyElement(
				HTMLBuilder::element(
					'p',
					'Account successfully created!'
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
			// Account creation failed or wasn't attempted
			$this->addBodyElement( $this->getForm() );
		}
	}

	private function trySubmit(): string|true {
		$email = $_POST['et-email'] ?? '';
		$pass = $_POST['et-password'] ?? '';
		$passConfirm = $_POST['et-password-confirm'] ?? '';
		if ( $email === '' ) {
			return 'Missing email';
		} elseif ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return 'Invalid email';
		} elseif ( $pass === '' || $passConfirm === '' ) {
			return 'Missing password or password confirmation';
		} elseif ( $pass !== $passConfirm ) {
			return 'Passwords do not match';
		}

		$db = new Database;
		if ( $db->getAccountByEmail( $email ) !== null ) {
			return 'Email already used';
		}
		$account = UserAccount::newUninserted(
			$email,
			$pass,
			UserAccount::FLAGS_NONE
		);
		$account->insertInto( $db );
		Authentication::loginSession( $account->getId() );
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
				'id' => 'et-create-account',
				'action' => './signup.php',
				'method' => 'POST',
			]
		);
	}

	private function getFormFields(): array {
		$formRows = [
			HTMLBuilder::formRow( 'Email:', 'email', 'et-email' ),
			HTMLBuilder::formRow( 'Password:', 'password', 'et-password' ),
			HTMLBuilder::formRow( 'Confirm password:', 'password', 'et-password-confirm' ),
		];
		$fields = [
			HTMLBuilder::element(
				'table',
				HTMLBuilder::element( 'tbody', $formRows )
			)
		];
		if ( is_string( $this->signUpResult ) ) {
			$fields[] = HTMLBuilder::element(
				'p',
				$this->signUpResult,
				[ 'class' => 'et-error' ]
			);
		}
		$fields[] = HTMLBuilder::element(
			'button',
			'Create account',
			[ 'type' => 'submit',
				'id' => 'et-create-account-submit', 'class' => 'et-form-button' ]
		);
		return $fields;
	}
}