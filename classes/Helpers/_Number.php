<?php
/**
 * Number related functions
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Helpers;

/**
 * Number handler class
 */
class _Number {
	public static function getInt( $num, $min = null, $max = null ) {
		
		$num = is_numeric( $num ) ? ( int ) $num : 0;

		if ( $min !== null && $num < $min ) {
			$num = $min;
		}

		if ( $max !== null && $num > $max ) {
			$num = $max;
		}

		return $num;
	}
}
