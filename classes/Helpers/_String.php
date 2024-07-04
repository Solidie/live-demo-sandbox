<?php
/**
 * String related functions
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Helpers;

/**
 * String handler class
 */
class _String {
	/**
	 * Allowed html elements for kses
	 *
	 * @var array
	 */
	private static $allowed_html = array(
		'a',
		'b',
		'br',
		'code',
		'del',
		'div',
		'em',
		'i',
		'ins',
		'kbd',
		'li',
		'ol',
		'p',
		'small',
		'span',
		'strong',
		'sub',
		'sup',
		'u',
		'ul',
	);

	/**
	 * Allowed attributes for kses
	 *
	 * @var array
	 */
	private static $allowed_attributes = array(
		'style'  => array(),
		'class'  => array(),
		'id'     => array(),
		'href'   => array(),
		'alt'    => array(),
		'title'  => array(),
		'width'  => array(),
		'height' => array(),
		'size'   => array(),
	);

	/**
	 * Generate random string
	 *
	 * @param stirng $prefix Prefix
	 * @param stirng $postfix Postfix
	 *
	 * @return string
	 */
	public static function getRandomString( $prefix = 'r', $postfix = 'r' ) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$ms         = (string) microtime( true );
		$ms         = str_replace( '.', '', $ms );
		$string     = $prefix . $ms;

		for ( $i = 0; $i < 5; $i++ ) {
			$string .= $characters[ wp_rand( 0, strlen( $characters ) - 1 ) ];
		}

		return $string . $postfix;
	}

	/**
	 * Apply kses filter on string
	 *
	 * @param string $string The string to apply kses filter
	 * @param bool   $echo Whether to echo rather than return or not
	 * @return string
	 */
	public static function applyKses( string $string, $echo = false ) {
		
		// For now return direct string. Because users might write code snippet for content, articles, comments etc.
		$string = preg_replace( '#<script(.*?)>(.*?)</script>#is', '', $string );

		/* static $allowed = null;

		// Prepare allowed array only once by defining as static
		if ( null === $allowed ) {

			$allowed = array();

			// Loop through tags
			foreach ( self::$allowed_html as $tag ) {

				// And assign supported attributes per tag
				$allowed[ $tag ] = self::$allowed_attributes;
			}
		} 
		
		$string = wp_kses( $string, $allowed ); */

		if ( $echo ) {
			echo $string;
		} else {
			return $string;
		}
	}

	/**
	 * Check if a value is float
	 *
	 * @param string|int|float $numeric_string The value to check if float
	 * @return boolean
	 */
	public static function isFloat( $numeric_string ) {
		return is_numeric( $numeric_string ) && strpos( $numeric_string, '.' ) !== false;
	}

	/**
	 * Cast a string value to nearest data type
	 *
	 * @param string $value The value to convert to nearest data type
	 *
	 * @return mixed
	 */
	public static function castValue( $value ) {

		if ( is_string( $value ) ) {

			if ( is_numeric( $value ) ) {
				// Cast number
				$value = self::isFloat( $value ) ? (float) $value : (int) $value;

			} elseif ( 'true' === $value ) {
				// Cast boolean true
				$value = true;

			} elseif ( 'false' === $value ) {
				// Cast boolean false
				$value = false;

			} elseif ( 'null' === $value ) {
				// Cast null
				$value = null;

			} elseif ( '[]' === $value ) {
				// Cast empty array
				$value = array();

			} else {
				// Maybe unserialize
				$value = maybe_unserialize( $value );
			}
		}

		return $value;
	}

	/**
	 * Helper method to generate placeholders for in query
	 *
	 * @param int|array $count The amount of placeholders or the data array to get count of.
	 * @param string    $placeholder The placeholder. Default %d for numeric values as it is mostly used.
	 * @return string
	 */
	public static function getPlaceHolders( $count, string $placeholder = '%d' ) {
		$count = is_array( $count ) ? count( $count ) : $count;
		return implode( ', ', array_fill( 0, $count, $placeholder ) );
	}

	/**
	 * Consolidate string
	 *
	 * @param string  $input_string
	 * @param boolean $replace_newlines
	 * @return string
	 */
	public static function consolidate( string $input_string, $replace_newlines = false ) {
		$pattern = $replace_newlines ? '/[\s\t\r\n]+/' : '/[\s\t]+/';
		return preg_replace( $pattern, ' ', trim( $input_string ) );
	}

	/**
	 * Clean base path
	 *
	 * @param string $str
	 * @return string
	 */
	public static function purgeBasePath( $str ) {
		// Remove non-alphanumeric characters (except spaces and hyphens)
		$cleaned_str = preg_replace('/[^a-zA-Z0-9\s-]/', '', $str);

		// Replace spaces with hyphens
		$cleaned_str = preg_replace('/\s+/', '-', $cleaned_str);

		// Consolidate multiple hyphens into a single hyphen
		$cleaned_str = preg_replace('/-+/', '-', $cleaned_str);

		// Return the cleaned string
		return $cleaned_str;
	}
}
