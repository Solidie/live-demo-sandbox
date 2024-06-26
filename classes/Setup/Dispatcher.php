<?php
/**
 * The dispatcher where all the ajax request pass through after validation
 *
 * @package solidie
 */

namespace Solidie_Sandbox\Setup;

use Solidie_Sandbox\Main;
use Solidie_Sandbox\Models\User;
use Solidie_Sandbox\Helpers\_Array;
use Solidie_Sandbox\Controllers\InstanceController;
use Error;
use Solidie_Sandbox\Controllers\SandboxController;

/**
 * Dispatcher class
 */
class Dispatcher {

	/**
	 * Controlles class array
	 *
	 * @var array
	 */
	private static $controllers = array(
		InstanceController::class,
		SandboxController::class,
	);

	/**
	 * Dispatcher registration in constructor
	 *
	 * @return void
	 * @throws Error If there is any duplicate ajax handler across controllers.
	 */
	public function __construct() {
		// Register ajax handlers only if it is ajax call
		if ( ! wp_doing_ajax() ) {
			return;
		}

		add_action( 'plugins_loaded', array( $this, 'registerControllers' ), 11 );
	}

	/**
	 * Register ajax request handlers
	 *
	 * @throws Error If there is any duplicate ajax handler across controllers.
	 * @return void
	 */
	public function registerControllers() {

		$registered_methods = array();
		$controllers        = apply_filters( 'solidie_controllers', self::$controllers );

		// Loop through controllers classes
		foreach ( $controllers as $class ) {

			// Loop through controller methods in the class
			foreach ( $class::PREREQUISITES as $method => $prerequisites ) {
				if ( in_array( $method, $registered_methods, true ) ) {
					// translators: Show the duplicate registered endpoint
					throw new Error( sprintf( esc_html__( 'Duplicate endpoint %s not possible', 'solidie' ), esc_html( $method ) ) );
				}

				// Determine ajax handler types
				$handlers    = array();
				$handlers [] = 'wp_ajax_' . Main::$configs->app_id . '_' . $method;

				// Check if norpriv necessary
				if ( ( $prerequisites['nopriv'] ?? false ) === true ) {
					$handlers[] = 'wp_ajax_nopriv_' . Main::$configs->app_id . '_' . $method;
				}

				// Loop through the handlers and register
				foreach ( $handlers as $handler ) {
					add_action(
						$handler,
						function() use ( $class, $method, $prerequisites ) {
							$this->dispatch( $class, $method, $prerequisites );
						}
					);
				}

				$registered_methods[] = $method;
			}
		}
	}

	/**
	 * Dispatch request to target handler after doing verifications
	 *
	 * @param string $class         The class to dispatch the request to
	 * @param string $method        The method of the class to invoke
	 * @param array  $prerequisites Controller access prerequisites
	 *
	 * @return void
	 */
	public function dispatch( $class, $method, $prerequisites ) {

		// Nonce verification
		$matched = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), sanitize_text_field( wp_unslash( $_POST['nonce_action'] ?? '' ) ) );
		$matched = $matched || wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ?? '' ) ), sanitize_text_field( wp_unslash( $_GET['nonce_action'] ?? '' ) ) );
		$is_post = strtolower( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) === 'post';

		// We can't really restrict GET requests for nonce.
		// Because GET requests usually comes from bookmarked URL or direct links where nonce doesn't really make any sense.
		// Rather we've enhanced security by verifying accepted argument data types, sanitizing and escaping in all cases.
		if ( $is_post && ! $matched ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Nonce verification failed!', 'solidie' ) ) );
		}

		// Validate access privilege
		$required_roles = $prerequisites['role'] ?? array();
		$required_roles = is_array( $required_roles ) ? $required_roles : array( $required_roles );
		$required_roles = in_array( 'administrator', $required_roles ) ? array_unique( $required_roles ) : array();
		if ( ! User::validateRole( get_current_user_id(), $required_roles ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You are not authorized!', 'solidie' ) ) );
		}

		// Now pass to the action handler function
		if ( ! class_exists( $class ) || ! method_exists( $class, $method ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid Endpoint!', 'solidie' ) ) );
		}

		// Prepare request data
		$params = _Array::getMethodParams( $class, $method );

		// Pick only the used arguments in the mathod from request data
		$args = array();
		foreach ( $params as $param => $configs ) {
			$args[ $param ] = wp_unslash( ( $is_post ? ( $_POST[ $param ] ?? null ) : ( $_GET[ $param ] ?? null ) ) ?? $_FILES[ $param ] ?? $configs['default'] ?? null );
		}

		// Sanitize and type cast
		$args = _Array::sanitizeRecursive( $args );

		// Now verify all the arguments expected data types after casting
		foreach ( $args as $name => $value ) {

			// The request data value
			$arg_type = gettype( $value );

			// The accepted type by the method
			$param_type = $params[ $name ]['type'];

			// Check if request data type and accepted type matched
			if ( $arg_type != $param_type ) {

				if ( 'string' === $param_type && is_numeric( $value ) ) {
					$args[ $name ] = ( string ) $value;

				} else if ( 'double' === $param_type && is_numeric( $value ) ) {
					$args[ $name ] = ( float ) $value;

				} else if ( 'integer' === $param_type && is_numeric( $value ) ) {
					$args[ $name ] = ( int ) $value;

				} else if ( 'array' === $param_type && 'integer' === $arg_type ) {
					// Sometimes 0 can be passed instead of array
					// Then use empty array rather
					// So far the seneario has found when thumbnail is not set in content editor
					$args[ $name ] = array();

				} else {
					wp_send_json_error(
						array( 
							'message'  => __( 'Invalid request data!', 'hr-management' ),
							'param'    => $name,
							'accepts'  => $param_type,
							'received' => $arg_type,
						) 
					);
				}
			}
		}

		// Then pass to method with spread as the parameter count is variable.
		$args = array_values( $args );
		$class::$method( ...$args );
	}
}
