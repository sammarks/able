<?php

namespace Able\Helpers;

class ScaffoldManager {

	private $files = array();

	function __construct($scaffold)
	{
		// Get the path for the scaffold.
		$path = LIBS_ROOT . DIRECTORY_SEPARATOR . 'scaffolds' . DIRECTORY_SEPARATOR . $scaffold;

		// Load the scaffold into memory from the libs folder.
		$this->files = $this->readDirectory($path);
	}

	/**
	 * Updates the current object, performing the replacements specified in the
	 * $replacements array.
	 *
	 * @param array $replacements The replacements to perform. Must have 'files'
	 *                            and 'contents' keys. 'files' is directory and
	 *                            file names, 'contents' is file contents.
	 */
	function performReplacements($replacements)
	{
		// Split the $replacements array into files and contents.
		$files = (array_key_exists('files', $replacements)) ? $replacements['files'] : array();
		$contents = (array_key_exists('contents', $replacements)) ? $replacements['contents'] : array();

		// Perform the replacements.
		$this->files = $this->replaceKeys($this->files, array_keys($files), array_values($files));
		$this->files = $this->replaceValues($this->files, array_keys($contents), array_values($contents));
	}

	/**
	 * Does a str_replace on all keys of an array, recursively.
	 *
	 * @param array $array       The array to perform the replacements on.
	 * @param array $search      A string or array for what to search for.
	 * @param array $replacement A string or array for what to replace it with.
	 *
	 * @return array The resulting array.
	 */
	function replaceKeys($array, $search, $replacement)
	{
		$new_array = array();

		foreach ($array as $key => $value) {

			// Replace the current item.
			$new_key = str_replace($search, $replacement, $key);

			if (is_array($value)) {
				$new_array[$new_key] = $this->replaceKeys($value, $search, $replacement);
			} else {
				$new_array[$new_key] = $value;
			}

		}

		return $new_array;
	}

	/**
	 * Replaces all values in an array, recursively.
	 *
	 * @param array $array       The array to perform the replacements on.
	 * @param mixed $search      A string or array of what to search for.
	 * @param mixed $replacement A string or array of what to replace it with.
	 *
	 * @return array The resulting array.
	 */
	function replaceValues($array, $search, $replacement)
	{
		$new_array = array();

		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$array[$key] = $this->replaceValues($value, $search, $replacement);
			} else {
				$array[$key] = str_replace($search, $replacement, $value);
			}
		}

		return $new_array;
	}

	/**
	 * Writes the contents of $files to the filesystem at the specified path.
	 *
	 * @param string $path The path to write the scaffold to.
	 *
	 * @throws \Exception
	 */
	function write($path)
	{
		// Check to see if the path exists.
		if (!is_dir($path)) {
			throw new \Exception("The path ('{$path}') doesn't exist.");
		}

		// Write recursively to the filesystem at the specified path.
		$this->writeItem($path, $this->files);
	}

	/**
	 * Recursively writes the array to the filesystem.
	 *
	 * @param string $pathPrefix The path to write the files to.
	 * @param array  $array      The array of files and directories to create.
	 *
	 * @throws \Exception
	 */
	function writeItem($pathPrefix, $array)
	{
		// Check to see if the path exists.
		if (!is_dir($pathPrefix)) {
			throw new \Exception("That path prefix ('{$pathPrefix}') does not exist.");
		}

		foreach ($array as $key => $item) {

			$path = $pathPrefix . DIRECTORY_SEPARATOR . $key;

			// If it's a directory, create the directory and add the children.
			if (is_array($item)) {
				if (mkdir($path) === false)
					throw new \Exception("There was an error creating the directory '{$path}'");
				$this->writeItem($path, $item);
			} else {
				if (file_put_contents($path, $item) === false)
					throw new \Exception("There was an error creating the file '{$path}'");
			}

		}
	}

	/**
	 * Recursively reads the contents of a directory and stores them in an array.
	 *
	 * @param string $directory The path for the directory to search.
	 *
	 * @return array The directory map.
	 * @throws \Exception
	 */
	function readDirectory($directory)
	{
		// Make sure the directory exists.
		if (!is_dir($directory)) {
			throw new \Exception("The specified directory ('{$directory}') does not exist.");
		}

		$result = array();
		foreach (scandir($directory) as $item) {
			if ($item == '.' || $item == '..') continue;
			$path = $directory . DIRECTORY_SEPARATOR . $item;
			if (is_dir($path)) {
				$result[$item] = $this->readDirectory($path);
			} else {
				$result[$item] = file_get_contents($path);
			}
		}

		return $result;
	}

}
