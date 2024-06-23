<?php
/**
 * User functionalities
 *
 * @package solidie
 */

namespace Solidie_Sandbox\Models;

/**
 * User functions
 */
class User {

	/**
	 * Validate if a user has required role
	 *
	 * @param int          $user_id The user ID to validate rule
	 * @param string|array $role    The rule to match
	 * @return bool
	 */
	public static function validateRole( $user_id, $role ) {

		if ( empty( $role ) ) {
			return true;
		}

		$roles          = is_array( $role ) ? $role : array( $role );
		$assigned_roles = self::getUserRoles( $user_id );

		return count( array_diff( $roles, $assigned_roles ) ) < count( $roles );
	}

	/**
	 * Get user roles by user id
	 *
	 * @param int $user_id User ID to get roles of
	 * @return array
	 */
	public static function getUserRoles( $user_id ) {
		$user_data = get_userdata( $user_id );
		return ( is_object( $user_data ) && ! empty( $user_data->roles ) ) ? $user_data->roles : array();
	}

	/**
	 * Get user data by user id
	 *
	 * @param int $user_id The user ID to get data for
	 *
	 * @return array|null
	 */
	public static function getUserData( $user_id ) {
		$user = ! empty( $user_id ) ? get_userdata( $user_id ) : null;
		if ( empty( $user ) ) {
			return null;
		}

		return array(
			'first_name'   => $user->first_name,
			'last_name'    => $user->last_name,
			'display_name' => $user->display_name,
			'avatar_url'   => get_avatar_url( $user_id ),
		);
	}

	/**
	 * Return the user role that can do administrative actions for solidie.
	 *
	 * @return string
	 */
	public static function getSolidieAdminRole() {
		return apply_filters( 'solidie_administrative_role', 'administrator' );
	}

	/**
	 * Check if a user has administrative access to solidie functionalities
	 *
	 * @param int $user_id
	 * @return boolean
	 */
	public static function hasAdministrativeRole( $user_id ) {
		return self::validateRole( $user_id, self::getSolidieAdminRole() );
	}
}
