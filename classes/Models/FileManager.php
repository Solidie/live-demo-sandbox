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
	public static function deleteDirectory( string $folder ) {
		// Check if the folder exists
		if ( ! is_string( $folder ) || !file_exists($folder)) {
			return false;
		}

		// Check if it's a directory
		if (!is_dir($folder)) {
			return false;
		}

		// Open the directory
		$dir = opendir($folder);

		// Loop through the contents of the directory
		while (($file = readdir($dir)) !== false) {
			// Skip the special '.' and '..' folders
			if ($file == '.' || $file == '..') {
				continue;
			}

			// Build the full path to the item
			$path = $folder . DIRECTORY_SEPARATOR . $file;

			// Recursively delete directories or just delete files
			if (is_dir($path)) {
				self::deleteDirectory($path);
			} else {
				unlink($path);
			}
		}

		// Close the directory
		closedir($dir);

		// Delete the folder itself
		return rmdir($folder);
	}

	public static function getOnlyFolderNameInZip( $zipFilePath ) {

		$zip = new \ZipArchive;
		
		if ( $zip->open( $zipFilePath ) === true ) {

			$folderName  = null;
			$folderCount = 0;

			for ( $i = 0; $i < $zip->numFiles; $i++ ) {

				$stat     = $zip->statIndex( $i );
				$fileName = $stat['name'];
				
				// Check if the entry is a directory
				if ( substr( $fileName, -1 ) == '/' ) {

					// Extract the top-level directory name
					$dirName = explode( '/', $fileName )[0];
					
					// If we haven't set a folder name yet, set it
					if ( $folderName === null ) {
						$folderName = $dirName;
						$folderCount++;
					} elseif ( $folderName !== $dirName ) {
						// If we find a different folder name, return null (more than one folder)
						$zip->close();
						return null;
					}
				}
			}

			$zip->close();

			// Return the folder name if exactly one folder is found, otherwise null
			return $folderCount === 1 ? $folderName : null;
		} else {
			return null;
		}
	}
}
