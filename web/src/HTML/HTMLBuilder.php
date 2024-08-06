<?php

/** Helper for constructing HTML elements */

namespace EasyTransfer\HTML;

class HTMLBuilder {

	/**
	 * String contents are escaped, HTMLElem objects are not
	 *
	 * @param string $tag
	 * @param string|HTMLElem|(string|HTMLElem)[] $contents
	 * @param array $attribs
	 * @return HTMLElem
	 */
	public static function element(
		string $tag,
		$contents = [],
		array $attribs = []
	): HTMLElem {
		if ( !is_array( $contents ) ) {
			// using (array) casts doesn't work
			$contents = [ $contents ];
		}
		return self::rawElement(
			$tag,
			array_map(
				static fn ( $c ) => is_string( $c ) ? htmlspecialchars( $c ) : $c,
				$contents
			),
			$attribs
		);
	}
	/**
	 * Raw element, contents used as-is
	 *
	 * @param string $tag
	 * @param string|HTMLElem|(string|HTMLElem)[] $contents
	 * @param array $attribs
	 * @return HTMLElem
	 */
	public static function rawElement(
		string $tag,
		$contents = [],
		array $attribs = []
	): HTMLElem {
		if ( !is_array( $contents ) ) {
			// using (array) casts doesn't work
			$contents = [ $contents ];
		}
		$res = "<$tag";
		foreach ( $attribs as $name => $rawValue ) {
			if ( $rawValue === true ) {
				// boolean attribute just needs to be there
				$res .= " $name";
			} elseif ( $rawValue !== false ) {
				// false means a boolean attribute that should be excluded
				$useValue = htmlspecialchars( $rawValue, ENT_QUOTES );
				$res .= " $name=\"$useValue\"";
			}
		}
		if ( $contents === []
			&& in_array( $tag, [ 'hr', 'br' ] )
		) {
			return new HTMLElem( "$res />" );
		}
		$allContents = implode( "\n", $contents );
		return new HTMLElem( "{$res}>{$allContents}</{$tag}>" );
	}

	/**
	 * Create a <tr> for a form, where the first <td> is a <label> for the
	 * <input> in the second <td>
	 */
	public static function formRow(
		string $labelText,
		string $inputType,
		string $inputId
	): HTMLElem {
		$wrapTd = static fn ( $html ) => self::element( 'td', $html );
		return self::element(
			'tr',
			[
				$wrapTd( self::element(
						'label',
						$labelText,
						[ 'for' => $inputId ]
					)
				),
				$wrapTd(
					self::element(
						'input',
						[],
						[
							'type' => $inputType,
							'id' => $inputId,
							'name' => $inputId,
							'value' => $_REQUEST[$inputId] ?? '',
						]
					)
				),
			]
		);
	}

}