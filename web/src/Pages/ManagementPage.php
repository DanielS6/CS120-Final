<?php

namespace EasyTransfer\Pages;

use EasyTransfer\Authentication;
use EasyTransfer\Data\Database;
use EasyTransfer\Data\UserAccount;
use EasyTransfer\HTML\HTMLBuilder;
use EasyTransfer\HTML\HTMLElem;
use stdClass;

/**
 * Front end for managing user accounts being marked as premium; creates a
 * table with a bunch of buttons that are each really an individual form
 *
 */
class ManagementPage extends BasePage {

	private Database $db;

	public function __construct() {
		parent::__construct( 'Management' );
		$this->addStyleSheet( 'management-styles.css' );
		$this->db = new Database();
	}

	private function canUseManagement(): bool {
		if ( !Authentication::isLoggedIn() ) {
			$this->addBodyElement(
				HTMLBuilder::element(
					'p',
					'Management page can only be used when logged in',
					[ 'class' => 'et-error' ]
				)
			);
			return false;
		}
		$userId = Authentication::getLoggedInUserId();
		$account = $this->db->getAccountById( $userId );

		if ( !$account->hasFlag( UserAccount::FLAG_MANAGER ) ) {
			$this->addBodyElement(
				HTMLBuilder::element(
					'p',
					'Management page restricted to authorized users',
					[ 'class' => 'et-error' ]
				)
			);
			return false;
		}
		return true;
	}

	protected function buildPage(): void {
		$this->addBodyElement(
			HTMLBuilder::element( 'h1', 'Account management' )
		);
		if ( !$this->canUseManagement() ) {
			return;
		}
		$didFlagChange = $this->maybeSubmit();
		if ( $didFlagChange ) {
			return;
		}
		$this->addForm();
	}

	private function maybeSubmit(): bool {
		$isPost = ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) === 'POST';
		if ( !$isPost ) {
			return false;
		}
		$manageUser = $_POST['manage-user'] ?? false;
		if ( !$manageUser || !is_numeric( $manageUser ) ) {
			$this->addBodyElement(
				HTMLBuilder::element(
					'p',
					'Unable to manage user: no user set',
					[ 'class' => 'et-error' ]
				)
			);
			return false;
		}

		$manageAction = $_POST['manage-action'] ?? false;
		if ( !$manageAction
			|| !in_array( $manageAction, [ 'upgrade', 'downgrade' ] )
		) {
			$this->addBodyElement(
				HTMLBuilder::element(
					'p',
					'Unable to manage user: missing valid action',
					[ 'class' => 'et-error' ]
				)
			);
			return false;
		}

		$returnLink = HTMLBuilder::element(
			'a',
			'Return to management interface',
			[ 'href' => './manage.php' ]
		);
		// We want to either upgrade or downgrade the user with the $manageUser
		// user id
		$manageUserObj = $this->db->getAccountById( $manageUser );
		$userEmail = HTMLBuilder::element(
			'code',
			$manageUserObj->getEmail()
		);
		if ( $manageAction === 'upgrade' ) {
			if ( $manageUserObj->hasFlag( UserAccount::FLAG_PREMIUM ) ) {
				$this->addBodyElement(
					HTMLBuilder::element(
						'p',
						[
							"Unable to upgrade:",
							$userEmail,
							"is already marked as premium.",
						],
						[ 'class' => 'et-error' ]
					)
				);
				return false;
			}
			$manageUserObj->updateFlagsIn(
				$this->db,
				$manageUserObj->getFlags() | UserAccount::FLAG_PREMIUM
			);
			$this->addBodyElement(
				HTMLBuilder::element(
					'p',
					[
						"Upgraded",
						$userEmail,
						"to premium.",
						$returnLink,
					]
				)
			);
			return true;
		}
		// Must be downgrading
		if ( !$manageUserObj->hasFlag( UserAccount::FLAG_PREMIUM ) ) {
			$this->addBodyElement(
				HTMLBuilder::element(
					'p',
					[
						"Unable to downgrade:",
						$userEmail,
						"is not marked as premium.",
					],
					[ 'class' => 'et-error' ]
				)
			);
			return false;
		}
		$manageUserObj->updateFlagsIn(
			$this->db,
			$manageUserObj->getFlags() & ~UserAccount::FLAG_PREMIUM
		);
		$this->addBodyElement(
			HTMLBuilder::element(
				'p',
				[
					"Downgraded",
					$userEmail,
					"to regular account.",
					$returnLink,
				]
			)
		);
		return true;
	}

	private function hiddenInput( string $name, string $value ): HTMLElem {
		return HTMLBuilder::element(
			'input',
			[],
			[ 'type' => 'hidden', 'name' => $name, 'value' => $value ]
		);
	}

	private function getUserRow( stdClass $user ): HTMLElem {
		$tableCells = [];
		// User ID
		$tableCells[] = HTMLBuilder::element( 'td', (string)$user->user_id );

		// User email
		$tableCells[] = HTMLBuilder::element(
			'td',
			HTMLBuilder::element( 'code', (string)$user->user_email )
		);

		$premiumFlag = UserAccount::FLAG_PREMIUM;
		$isPremium = ( $user->user_flags & $premiumFlag ) === $premiumFlag;

		$action = $isPremium ? 'downgrade' : 'upgrade';
		$changeForm = HTMLBuilder::element(
			'form',
			[
				$this->hiddenInput( 'manage-user', (string)$user->user_id ),
				$this->hiddenInput( 'manage-action', $action ),
				HTMLBuilder::element(
					'button',
					$isPremium ? 'Downgrade' : 'Upgrade',
					[ 'type' => 'submit' ]
				),
			],
			[ 'method' => 'POST' ]
		);

		// 2 columns of buttons, upgrading and then downgrading, each row only
		// has one
		if ( $isPremium ) {
			$tableCells[] = HTMLBuilder::element( 'td', [] );
			$tableCells[] = HTMLBuilder::element( 'td', $changeForm );
		} else {
			$tableCells[] = HTMLBuilder::element( 'td', $changeForm );
			$tableCells[] = HTMLBuilder::element( 'td', [] );
		}

		return HTMLBuilder::element( 'tr', $tableCells );
	}

	private function addForm() {
		$tableRows = array_map(
			fn ( $row ) => $this->getUserRow( $row ),
			$this->db->getAllUsers()
		);
		$columnHeadings = array_map(
			static fn ( $label ) => HTMLBuilder::element( 'th', $label ),
			[ 'ID', 'Email', 'Upgrade', 'Downgrade' ]
		);
		$tableHead = HTMLBuilder::element(
			'thead',
			HTMLBuilder::element( 'tr', $columnHeadings )
		);
		$table = HTMLBuilder::element(
			'table',
			[
				$tableHead,
				HTMLBuilder::element( 'tbody', $tableRows ),
			],
			[ 'id' => 'et-management-table' ]
		);
		$this->addBodyElement( $table );
	}

}