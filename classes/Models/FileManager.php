<?php
/**
 * File uploader functionalities
 *
 * @package solidie
 */

namespace Solidie_Sandbox\Models;

/**
 * File and directory handler class
 */
class FileManager {

	/**
	 * Delete WP files
	 *
	 * @param int|array $file_id File ID or array of files IDs
	 * @return void
	 */
	public static function deleteFile( $file_id ) {
		if ( ! is_array( $file_id ) ) {
			$file_id = array( $file_id );
		}

		// Loop through file IDs and delete
		foreach ( $file_id as $id ) {
			if ( ! empty( $id ) && is_numeric( $id ) ) {
				wp_delete_attachment( $id, true );
			}
		}
	}

	/**
	 * Delete directory
	 *
	 * @param string $dir Dir path to delete including files and sub folders
	 * @return bool
	 */
	public static function deleteDirectory( string $dir ) {
		if ( ! is_dir( $dir ) ) {
			return false;
		}

		$files = glob( $dir . '/*' );
		foreach ( $files as $file ) {
			is_dir( $file ) ? self::deleteDirectory( $file ) : unlink( $file );
		}

		return rmdir( $dir );
	}
}
