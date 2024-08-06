<?php

namespace EasyTransfer\Pages;

use EasyTransfer\Authentication;
use EasyTransfer\Data\Database;
use EasyTransfer\Data\Transfer;
use EasyTransfer\Data\TransferType;
use EasyTransfer\Data\UserAccount;
use EasyTransfer\HTML\HTMLBuilder;

class TransferPage extends BasePage {

	public function __construct() {
		parent::__construct( 'Transfer' );
		$this->addStyleSheet( 'transfer-styles.css' );
		$this->addScript( 'transfer-logic.js' );
	}

	protected function buildPage(): void {
		$this->addBodyElement(
			HTMLBuilder::element( 'h1', 'Transfer' )
		);
		$didSave = $this->maybeSubmit();
		if ( $didSave ) {
			return;
		}
		$this->addForm( $this->getSavedTransfer() );
	}

	private function maybeSubmit(): bool {
		$isPost = ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) === 'POST';
		if ( !$isPost
			|| !isset( $_POST['transfer-content'] )
			|| !isset( $_POST['transfer-type'] ) ) {
			return false;
		}
		if ( !Authentication::isLoggedIn() ) {
			$this->addBodyElement(
				HTMLBuilder::element(
					'p',
					'Unable to save transfer; please log in',
					[ 'class' => 'et-error' ]
				)
			);
			return false;
		}
		$transferType = $_POST['transfer-type'];
		if ( $transferType === 'url' ) {
			$transferType = TransferType::URL;
		} elseif ( $transferType === 'text' ) {
			$transferType = TransferType::TEXT;
		} else {
			$this->addBodyElement(
				HTMLBuilder::element(
					'p',
					'Unable to save transfer of unknown type: ' . $transferType,
					[ 'class' => 'et-error' ]
				)
			);
			return false;

		}
		$db = new Database;
		$transfer = Transfer::newToSave(
			$transferType,
			$_POST['transfer-content']
		);
		$db->setUserTransfer(
			Authentication::getLoggedInUserId(),
			$transfer
		);
		$this->addBodyElement(
			HTMLBuilder::element(
				'p',
				'Transfer content saved successfully!'
			)
		);
		return true;
	}

	private function getSavedTransfer(): ?Transfer {
		if ( !Authentication::isLoggedIn() ) {
			return null;
		}
		$db = new Database;
		$userId = Authentication::getLoggedInUserId();
		$transfer = $db->getUserTransfer( $userId );
		if ( $transfer === null ) {
			return null;
		}
		// Only need to check user flags if the transfer has not expired
		if ( !$transfer->hasExpired() ) {
			return $transfer;

		}
		$user = $db->getAccountById( $userId );
		$paid = $user->hasFlag( UserAccount::FLAG_PREMIUM );
		if ( $paid ) {
			return $transfer;
		}
		$this->addBodyElement(
			HTMLBuilder::element(
				'p',
				'Stored transfer has expired :('
			)
		);
		return null;
	}

	private function addForm( ?Transfer $currentTransfer ): void {
		if ( $currentTransfer !== null ) {
			$this->addBodyElement(
				HTMLBuilder::element(
					'strong',
					'Stored transfer content loaded!'
				)
			);
		}
		$startWithText = ( $currentTransfer
			&& $currentTransfer->getType() === TransferType::TEXT
		);
		$this->addBodyElement(
			HTMLBuilder::element(
				'p',
				'Note: the transfer system requires JavaScript for QR code generation'
			)
		);
		$formFields = [
			HTMLBuilder::element(
				'label',
				'Transfer type:',
				[ 'id' => 'et-transfer-type-label' ]
			),
			HTMLBuilder::element( 'br' ),
			HTMLBuilder::element(
				'input',
				[],
				[
					'type' => 'radio',
					'value' => 'url',
					'name' => 'transfer-type',
					'id' => 'et-type-url',
					'checked' => !$startWithText,
				]
			),
			HTMLBuilder::element(
				'label',
				'URL',
				[ 'for' => 'et-type-url' ]
			),
			HTMLBuilder::element(
				'input',
				[],
				[
					'type' => 'radio',
					'value' => 'text',
					'name' => 'transfer-type',
					'id' => 'et-type-text',
					'checked' => $startWithText,
				]
			),
			HTMLBuilder::element(
				'label',
				'Text',
				[ 'for' => 'et-type-text' ]
			),
			HTMLBuilder::element( 'br' ),
			HTMLBuilder::element(
				'label',
				[
					HTMLBuilder::element(
						'span',
						$startWithText ? 'Text' : 'URL',
						[ 'id' => 'et-transfer-type-display']
					),
					'to transfer:'
				],
				[ 'for' => 'et-transfer-content', 'id' => 'et-transfer-content--label' ]
			),
			HTMLBuilder::element( 'br' ),
			HTMLBuilder::element(
				'textarea',
				( $currentTransfer ? $currentTransfer->getContent() : '' ),
				[
					'id' => 'et-transfer-content',
					'name' => 'transfer-content',
					'required' => true,
					// max URL is 2048 but this isn't meant as a replacement
					// for google docs or email, this is for small things
					'maxlength' => 500,
				]
			),
			HTMLBuilder::element(
				'button',
				'Generate QR code',
				[ 'id' => 'et-transfer-generate', 'type' => 'button' ]
			),
		];

		if ( Authentication::isLoggedIn() ) {
			$formFields[] = HTMLBuilder::element(
				'button',
				$currentTransfer ? 'Replace transfer content' : 'Save transfer',
				[ 'id' => 'et-transfer-save', 'type' => 'submit' ]
			);
			$form = HTMLBuilder::element(
				'form',
				$formFields,
				[ 'method' => 'POST' ]
			);
		} else {
			$form = HTMLBuilder::element( 'div', $formFields );
		}

		$this->addBodyElement( $form );

		$this->addBodyElement(
			HTMLBuilder::element(
				'div',
				[],
				[ 'id' => 'et-transfer-QR' ]
			)
		);
	}

}