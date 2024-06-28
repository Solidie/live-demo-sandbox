<?php
/**
 * Array methods
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Helpers;

/**
 * The enriched array class
 */
class _Array {

	/**
	 * Apply order to every array elements
	 *
	 * @param  array  $array     The array to add order ins
	 * @param  string $order_key The key to store order index
	 * @return array
	 */
	public static function addOrderColumn( array $array, string $order_key ) {
		// Start from
		$order = 1;

		// Loop through the array and assign sequence order
		foreach ( $array as $index => $element ) {

			$element[ $order_key ] = $order;
			$array[ $index ]       = $element;

			$order++;
		}

		return $array;
	}

	/**
	 * Multipurpose array preparation
	 *
	 * @param mixed $array              Expected array, however anything else could be passed to convert to array element
	 * @param bool  $mutate             Whether to make the non array element to array element
	 * @param mixed $non_empty_fallback The fallback array element when the array is empty
	 *
	 * @return array
	 */
	public static function getArray( $array, $mutate = false, $non_empty_fallback = null ) {

		// Set the array as empty or convert the non array to array
		if ( ! is_array( $array ) ) {
			$array = $mutate ? array( $array ) : array();
		}

		// Avoid non empty array by adding fallback element for IN query epecially
		if ( empty( $array ) && null !== $non_empty_fallback ) {
			$array = array( $non_empty_fallback );
		}

		// Convert data types to near ones
		$array = self::castRecursive( $array );

		return $array;
	}

	/**
	 * Check if an array is two dimensional
	 *
	 * @param  array $array The array to check if it is two dimensional
	 * @return bool
	 */
	public static function isTwoDimensionalArray( $array ) {
		if ( ! empty( $array ) && is_array( $array ) ) {
			return is_array( current( $array ) );
		}
		return false;
	}

	/**
	 * Cast number, bool from string.
	 *
	 * @param  array $array The array to cast data recursively
	 * @return array
	 */
	public static function castRecursive( array $array ) {
		// Loop through array elements
		foreach ( $array as $index => $value ) {

			// If it is also array, pass through recursion
			if ( is_array( $value ) ) {
				$array[ $index ] = self::castRecursive( $value );
				continue;
			}

			$array[ $index ] = _String::castValue( $value );
		}

		return $array;
	}

	/**
	 * Make an array column value index of the array
	 *
	 * @param  array  $array          Array to indexify
	 * @param  string $column         The field to use the value as index
	 * @param  string $singular_field To store only a single column for the index as value
	 * @return array
	 */
	public static function indexify( array $array, string $column, $singular_field = null ) {
		$new_array = array();
		foreach ( $array as $element ) {
			$new_array[ $element[ $column ] ] = $singular_field ? ( $element[ $singular_field ] ?? null ) : $element;
		}

		return $new_array;
	}

	/**
	 * Append column to a two dimensional array
	 *
	 * @param  array  $array The array to append column into
	 * @param  string $key   The key to use as index of the column
	 * @param  array  $new   New field to use as the value
	 * @return array
	 */
	public static function appendColumn( array $array, string $key, $new ) {
		foreach ( $array as $index => $element ) {
			$array[ $index ][ $key ] = $new;
		}

		return $array;
	}

	/**
	 * Get single array from a two dimensional array, similar to 'find' method in JavaScript.
	 *
	 * @param  array  $array   The array to find in
	 * @param  string $key     The key to match in the second dimension
	 * @param  mixed  $value   The value to match in the second dimension
	 * @param  mixed  $default The default return value if not found
	 * @return array
	 */
	public static function find( array $array, $key, $value, $default = null ) {
		foreach ( $array as $row ) {
			if ( ( $row[ $key ] ?? null ) === $value ) {
				return $row;
			}
		}
		return $default;
	}

	/**
	 * Sanitize contents recursively
	 *
	 * @param mixed      $value The value to sanitize
	 * @param string|int $key   Current key in recursion. Do not pass it from outside of this function. It's for internal use only.
	 *
	 * @return mixed
	 */
	public static function sanitizeRecursive( $value, $key = null ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $_key => $_value ) {
				// If it is kses, then remove the key prefix from array key as it is not necessary in core applications.
				$index           = strpos( $_key, 'kses_' ) === 0 ? substr( $_key, 5 ) : $_key;
				$value[ $index ] = self::sanitizeRecursive( $_value, $_key );
			}
		} elseif ( is_string( $value ) ) {
			// If the prefix is kses_, it means rich text editor content and get it through kses filter. Otherise normal sanitize.
			$value = strpos( $key, 'kses_' ) === 0 ? _String::applyKses( $value ) : _String::castValue( sanitize_text_field( $value ) );
		}

		return $value;
	}

	/**
	 * Get method parameter names
	 *
	 * @param class  $class  The class to get method info from
	 * @param string $method The method to get parameters definition
	 *
	 * @return array
	 */
	public static function getMethodParams( $class, $method ) {

		$reflection_method = new \ReflectionMethod( $class, $method );
		$parameters        = $reflection_method->getParameters();
		$_params           = array();

		$type_map = array(
			'int'   => 'integer',
			'float' => 'double',
			'bool'  => 'boolean',
		);

		// Loop through method parameter definition and get configurations
		foreach ( $parameters as $parameter ) {

			$type = (string) $parameter->getType();

			$_params[ $parameter->getName() ] = array(
				'type'    => $type_map[ $type ] ?? $type,
				'default' => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
			);
		}

		return $_params;
	}

	/**
	 * Convert multidimensional array into one
	 *
	 * @param  array $array The array to flatten
	 * @return array
	 */
	public static function flattenArray( array $array ) {
		$result = array();
		foreach ( $array as $element ) {
			if ( is_array( $element ) ) {
				$result = array_merge( $result, self::flattenArray( $element ) );
			} else {
				$result[] = $element;
			}
		}
		return $result;
	}

	/**
	 * Parse comments from php file as array
	 *
	 * @param  string         $path     File path  to parse data from
	 * @param  ARRAY_A|OBJECT $ret_type Either object or array to return
	 * @return array|object
	 */
	public static function getManifestArray( string $path, $ret_type = OBJECT ) {
		$result = [];

		// Use regular expressions to match the first PHP comment block
		preg_match( '/\/\*\*(.*?)\*\//s', file_get_contents( $path ), $matches );

		if ( isset( $matches[1] ) ) {
			$comment = $matches[1];

			// Remove leading asterisks and split lines
			$lines = preg_split( '/\r\n|\r|\n/', trim( preg_replace( '/^\s*\*\s*/m', '', $comment ) ) );

			foreach ( $lines as $line ) {
				// Check if the line contains a colon
				if ( strpos( $line, ':' ) !== false ) {
					list($key, $value) = array_map( 'trim', explode( ':', $line, 2 ) );

					$key            = strtolower( str_replace( ' ', '_', $key ) );
					$result[ $key ] = $value;
				}
			}
		}

		$result['file']     = $path;
		$result['dir']      = dirname( $path ) . '/';
		$result['url']      = plugin_dir_url( $path );
		$result['dist_url'] = $result['url'] . 'dist/';

		$result = self::castRecursive( $result );

		return ARRAY_A === $ret_type ? $result : (object) $result;
	}

	/**
	 * Build nested array
	 *
	 * @param  array  $elements        The array to get nested data from
	 * @param  int    $parent_id       The parent ID to start the level from
	 * @param  string $col_name        The column name that holds parent ID
	 * @param  string $parent_col_name The column name that holds the index numbers
	 * @return array
	 */
	public static function buildNestedArray( $elements, $parent_id, $col_name, $parent_col_name ) {
		$nested_array = array();

		foreach ( $elements as $element ) {
			if ( is_array( $element ) && ( $element[ $col_name ] ?? null ) === $parent_id ) {
				$children = self::buildNestedArray( $elements, $element[ $parent_col_name ], $col_name, $parent_col_name );

				if ( ! empty( $children ) ) {
					$element['children'] = $children;
				}

				$nested_array[] = $element;
			}
		}

		return $nested_array;
	}

	/**
	 * Group multiple rows by a common field
	 *
	 * @param  array  $array    The table array to group rows
	 * @param  string $col_name The column name to group by
	 * @return array
	 */
	public static function groupRows( $array, $col_name ) {
		$grouped_array = array();

		foreach ( $array as $item ) {
			$group_key = $item[ $col_name ];

			if ( ! isset( $grouped_array[ $group_key ] ) ) {
				$grouped_array[ $group_key ] = array();
			}

			$grouped_array[ $group_key ][] = $item;
		}

		return $grouped_array;
	}

	/**
	 * Convert nested table to single table
	 *
	 * @param  array  $tables          The nested array to make linear
	 * @param  string $nested_col_name Then column name that holds children
	 * @return array
	 */
	public static function convertToSingleTable( array $tables, string $nested_col_name ) {
		$new_array = array();
		foreach ( $tables as $index => $rows ) {
			foreach ( $rows as $col_name => $col ) {
				if ( $col_name === $nested_col_name && is_array( $col ) ) {
					$new_array = array_merge( $new_array, self::convertToSingleTable( $col, $nested_col_name ) );
				} else {
					$new_array[ $index ][ $col_name ] = $col;
				}
			}
		}
		return $new_array;
	}

	/**
	 * Equivalent to array_column, but recursive
	 *
	 * @param array  $array           Nested array
	 * @param string $column          The column name to get
	 * @param string $children_column The column name that holds children
	 *
	 * @return array The Linear array containing column values from nested array
	 */
	public static function arrayColumnRecursive( $array, $column, $children_column ) {

		$values = array();

		foreach ( $array as $element ) {
			if ( isset( $element[ $column ] ) ) {
				$values[] = $element[ $column ];
			}

			if ( is_array( $element[ $children_column ] ?? null ) ) {
				$values = array_merge( $values, self::arrayColumnRecursive( $element[ $children_column ], $column, $children_column ) );
			}
		}

		return $values;
	}

	/**
	 * Get descendent count
	 *
	 * @param  array  $array The main array
	 * @param  string $count_col Count col string name
	 * @param  string $add_count_to Where to add count to
	 * @return array
	 */
	public static function getDescendentCount( array $array, string $count_col, string $add_count_to = null ) {
		foreach ( $array as $index => $element ) {

			$count = $element[ $count_col ];

			if ( is_array( $element ) && is_array( $element['children'] ?? null ) ) {
				$children                      = self::getDescendentCount( $element['children'], $count_col, $add_count_to );
				$count                         = $count + array_sum( array_column( $children, $count_col ) );
				$array[ $index ]['children']   = $children;
				$array[ $index ][ $count_col ] = $count;

			}

			if ( ! empty( $count ) && $add_count_to ) {
				$array[ $index ][ $add_count_to ] = $element[ $add_count_to ] . ' (' . $count . ')';
			}
		}
		return $array;
	}
}
