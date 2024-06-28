<?php
/**
 * Color pallete
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Helpers;

/**
 * Color helper class
 */
class Colors {


	const CONTRAST_FACTOR = 88;

	/**
	 * Get color shades, technically opacity
	 *
	 * @return array
	 */
	public static function getOpacities() {

		$ops = array();

		for ( $i = 1; $i >= 0.1; $i = $i - 0.1 ) {
			$ops[] = (float) number_format( $i, 1, '.', '' );
			$ops[] = (float) number_format( $i / 10, 2, '.', '' );
		}

		return $ops;
	}

	/**
	 * Convert hexa to rgba color
	 *
	 * @param  string $hex_color The color to convert
	 * @param  float  $opacity   Opacity to achieve
	 * @return string
	 */
	private static function hexToRgba( $hex_color, $opacity = 1 ) {
		// Remove any leading '#' from the hex color code
		$hex_color = ltrim( $hex_color, '#' );

		// Convert the hex color to RGB values
		$r = hexdec( substr( $hex_color, 0, 2 ) );
		$g = hexdec( substr( $hex_color, 2, 2 ) );
		$b = hexdec( substr( $hex_color, 4, 2 ) );

		// Ensure opacity is within the valid range (0 to 1)
		$opacity = max( 0, min( 1, $opacity ) );

		// Create the RGBA color string
		$rgba_color = "rgba($r, $g, $b, $opacity)";

		return $rgba_color;
	}

	/**
	 * Increase contract to a color
	 *
	 * @param string $hex Hex code of color
	 * @param float  $factor How much intensity
	 * @return string
	 */
	private static function increaseContrast( $hex, $factor = null ) {

		$factor = $factor ? $factor : self::CONTRAST_FACTOR;

		// Ensure the input is a valid hex color code
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		// Convert hex to RGB
		list($r, $g, $b) = sscanf( $hex, '%02x%02x%02x' );

		// Adjust contrast for each color component
		$r = self::adjustContrast( $r, $factor );
		$g = self::adjustContrast( $g, $factor );
		$b = self::adjustContrast( $b, $factor );

		// Convert back to hex
		$new_hex = sprintf( '#%02x%02x%02x', $r, $g, $b );
		return $new_hex;
	}

	/**
	 * Adjust color contrast
	 *
	 * @param string $color The code code
	 * @param float  $factor Intensity
	 * @return int
	 */
	private static function adjustContrast( $color, $factor ) {
		$factor    = ( 259 * ( $factor + 255 ) ) / ( 255 * ( 259 - $factor ) );
		$new_color = $factor * ( $color - 128 ) + 128;

		// Ensure the value is within 0-255 range
		return max( 0, min( 255, round( $new_color ) ) );
	}

	/**
	 * Get the colors to render in frontend
	 *
	 * @return array
	 */
	public static function getColors() {

		// Get possible opacities
		$opacities = self::getOpacities();

		// Define the static colors
		$colors = array(
			'success'     => '#5B9215',
			'warning'     => '#F57A08',
			'error'       => '#EA4545',
			'white'       => '#FFFFFF',
			'transparent' => 'rgba(0, 0, 0, 0)',
		);

		// Assign contrasted synamic colors
		foreach ( $colors as $key => $color ) {
			if ( strpos( $color, '#' ) === 0 ) {
				$colors[ $key . '-150' ] = self::increaseContrast( $color );
			}
		}

		// Prepare colors from dynamically set from settings page
		$schemes = array(
			'color_scheme_materials' => '#0000BB',
			'color_scheme_texts'     => '#121212',
		);

		// Loop through dynamic colors and assign shades
		foreach ( $schemes as $scheme => $color ) {

			$prefix = str_replace( 's', '', str_replace( 'color_scheme_', '', $scheme ) );

			foreach ( $opacities as $shade ) {
				$intensity                    = (int) ( ( $shade / 1 ) * 100 );
				$postfix                      = 100 === $intensity ? '' : '-' . $intensity;
				$colors[ $prefix . $postfix ] = self::hexToRgba( $color, $shade );
			}

			// Contrasted color for hover and active effect
			$colors[ $prefix . '-150' ] = self::increaseContrast( $color );
		}

		return $colors;
	}
}
