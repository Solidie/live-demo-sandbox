<?php
/**
 * File uploader functionalities
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Models;

/**
 * File and directory handler class
 */
class FileManager {


	/**
	 * Delete WP files
	 *
	 * @param  int|array $file_id File ID or array of files IDs
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
	 * @param  string $folder Dir path to delete including files and sub folders
	 * @return bool
	 */
	public static function deleteDirectory( string $folder ) {

		// Check if the folder exists
		if ( ! is_string( $folder ) || ! file_exists( $folder ) || ! is_dir( $folder ) ) {
			return false;
		}

		// Check if it's a directory
		if ( ! is_dir( $folder ) ) {
			return false;
		}

		// Open the directory
		$dir = opendir( $folder );

		// Loop through the contents of the directory
		while ( ( $file = readdir( $dir ) ) !== false ) {
			// Skip the special '.' and '..' folders
			if ( '.' === $file || '..' === $file ) {
				continue;
			}

			// Build the full path to the item
			$path = $folder . DIRECTORY_SEPARATOR . $file;

			// Recursively delete directories or just delete files
			if ( is_dir( $path ) ) {
				self::deleteDirectory( $path );
			} else {
				wp_delete_file( $path );
			}
		}

		// Close the directory
		closedir( $dir );

		// Delete the folder itself
		return rmdir( $folder );
	}

	/**
	 * Get the directory name inside the zip file
	 *
	 * @param  string $zip_file_path The zip file path to get dir name from inside
	 * @return string|null
	 */
	public static function getOnlyFolderNameInZip( $zip_file_path ) {

		if ( ! file_exists( $zip_file_path ) ) {
			return null;
		}

		$dir = null;
		$zip = new \ZipArchive();

		if ( $zip->open( $zip_file_path ) === true ) {

			$stat     = $zip->statIndex( 0 );
			$filename = is_array( $stat ) ? ( $stat['name'] ?? '' ) : '';
			$dir_name = explode( '/', $filename );
			$dir      = $dir_name[0] ?? null;

			$zip->close();
		}

		return $dir;
	}
}
