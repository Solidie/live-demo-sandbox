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

	/**
	 * Make sure the data is int
	 *
	 * @param mixed $num The value
	 * @param int   $min Minimum
	 * @param int   $max Maximum
	 * @return int
	 */
	public static function getInt( $num, $min = null, $max = null ) {

		$num = is_numeric( $num ) ? (int) $num : 0;

		if ( null !== $min && $num < $min ) {
			$num = $min;
		}

		if ( null !== $max && $num > $max ) {
			$num = $max;
		}

		return $num;
	}
}
