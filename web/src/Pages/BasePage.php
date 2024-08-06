<?php

/**
 * Used to create the output page
 */

namespace EasyTransfer\Pages;

use EasyTransfer\Authentication;
use EasyTransfer\Data\Database;
use EasyTransfer\Data\UserAccount;
use EasyTransfer\HTML\HTMLBuilder;
use EasyTransfer\HTML\HTMLElem;

abstract class BasePage {
	/** @var HTMLElem[] */
	private array $headElements = [];

	/** @var HTMLElem[] */
	private array $bodyElements = [];

	private bool $pageBuilt = false;

	protected function __construct( string $title ) {
		// Prevent trying to read a favicon that we don't have
		$this->addHeadElement(
			HTMLBuilder::element(
				'link',
				[],
				[ 'rel' => 'icon', 'href' => 'data:,' ]
			)
		);
		$this->addHeadElement(
			HTMLBuilder::element( 'title', $title )
		);
		// Always add default-styles.css
		$this->addStyleSheet( 'default-styles.css' );
	}

	protected function addHeadElement( HTMLElem $elem ): static {
		$this->headElements[] = $elem;
		return $this;
	}

	protected function addBodyElement( HTMLElem $elem ): static {
		$this->bodyElements[] = $elem;
		return $this;
	}

	protected function addScript( string $fileName ): static {
		return $this->addHeadElement(
			HTMLBuilder::element(
				'script',
				[],
				[ 'src' => "./resources/{$fileName}" ]
			)
		);
	}

	protected function addStyleSheet( string $fileName ): static {
		return $this->addHeadElement(
			HTMLBuilder::element(
				'link',
				[],
				[
					'rel' => 'stylesheet',
					'type' => 'text/css',
					'href' => "./resources/{$fileName}",
				]
			)
		);
	}

	public function getOutput(): string {
		if ( !$this->pageBuilt ) {
			$this->buildPage();
			$this->pageBuilt = true;
		}
		$content = HTMLBuilder::element(
			'div',
			[ $this->getNavBar(), ...$this->bodyElements ],
			[ 'class' => 'content-wrapper' ]
		);
		$html = HTMLBuilder::element(
			'html',
			[
				HTMLBuilder::element( 'head', $this->headElements ),
				$content
			]
		);
		return "<!DOCTYPE html>\n" . $html;
	}

	private function getNavBar(): HTMLElem {
		$links = [ 'Home' => './index.php', 'Transfer' => './transfer.php' ];
		if ( Authentication::isLoggedIn() ) {
			$db = new Database;
			$user = $db->getAccountById( Authentication::getLoggedInUserId() );
			if ( $user->hasFlag( UserAccount::FLAG_MANAGER ) ) {
				$links['Management'] = './manage.php';
			}
			$links['Log out'] = './logout.php';
		} else {
			$links['Log in'] = './login.php';
			$links['Sign up'] = './signup.php';
		}
		$linkElems = [];
		$urlParts = explode( '/', $_SERVER['SCRIPT_NAME'] );
		$currPage = './' . end( $urlParts );
		foreach ( $links as $display => $href ) {
			if ( $href === $currPage ) {
				$linkElems[] = HTMLBuilder::element( 'strong', $display );
			} else {
				$linkElems[] = HTMLBuilder::element(
					'a',
					$display,
					[ 'href' => $href ]
				);
			}
		}
		return HTMLBuilder::element(
			'div',
			$linkElems,
			[ 'id' => 'et-navbar' ]
		);
	}

	// Subclasses put their logic here to set up the page
	abstract protected function buildPage(): void;
}