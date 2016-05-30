<?php
/**
 *
 */
class resumable_model {

	private $dest;

	private $modelFs;
	/**
	 *
	 */
	public function __construct($siteId, $dest, $modelFs) {

		$this->dest = $dest; // 分段上传文件的保存位置

		$this->modelFs = $modelFs;
	}
	/**
	 *
	 * Logging operation
	 *
	 * @param string $str - the logging string
	 */
	private function _log($str) {
	}
	/**
	 *
	 * Delete a directory RECURSIVELY
	 *
	 * @param string $dir - directory path
	 * @link http://php.net/manual/en/function.rmdir.php
	 */
	private function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir . "/" . $object) == "dir") {
						$this->rrmdir($dir . "/" . $object);
					} else {
						unlink($dir . "/" . $object);
					}
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}
	/**
	 *
	 * Check if all the parts exist, and
	 * gather all the parts of the file together
	 *
	 * @param string $chunkDir - the temporary directory holding all the parts of the file
	 * @param string $fileName - the original file name
	 * @param string $chunkSize - each chunk size (in bytes)
	 * @param string $totalSize - original file size (in bytes)
	 */
	private function createFileFromChunks($chunkDir, $fileName, $chunkTotal) {
		// count all the parts of this file
		$uploadedNumber = 0;
		foreach (scandir($chunkDir) as $file) {
			if (stripos($file, \TMS_MODEL::toLocalEncoding($fileName)) !== false) {
				$uploadedNumber++;
			}
		}
		// check that all the parts are present
		// the size of the last part is between chunkSize and 2*$chunkSize
		if ($uploadedNumber == $chunkTotal) {
			// create the final destination file
			if (($fp = fopen($this->modelFs->rootDir . '/' . $this->dest, 'w')) !== false) {
				for ($i = 1; $i <= $uploadedNumber; $i++) {
					$partname = $chunkDir . '/' . \TMS_MODEL::toLocalEncoding($fileName) . '.part' . $i;
					$content = file_get_contents($partname);
					fwrite($fp, $content);
					$this->_log('writing chunk ' . $i);
				}
				fclose($fp);
			} else {
				$this->_log('cannot create the destination file');
				return false;
			}
			// rename the temporary directory (to avoid access from other
			// concurrent chunks uploads) and than delete it
			if (rename($chunkDir, $chunkDir . '_UNUSED')) {
				$this->rrmdir($chunkDir . '_UNUSED');
			} else {
				$this->rrmdir($chunkDir);
			}
		}
	}
	/**
	 * 处理分段上传的请求
	 */
	public function handleRequest($resumabled) {
		$chunkNumber = $resumabled['resumableChunkNumber'];
		$filename = str_replace(' ', '_', $resumabled['resumableFilename']);
		$chunkDir = $resumabled['resumableIdentifier'] . '_part';
		$chunkFile = \TMS_MODEL::toLocalEncoding($filename) . '.part' . $chunkNumber;
		$content = base64_decode(preg_replace('/data:(.*?)base64\,/', '', $resumabled['resumableChunkContent']));
		$ret = $this->modelFs->write($chunkDir . '/' . $chunkFile, $content);
		if (false === $ret) {
			$this->_log('Error saving chunk ' . $chunkNumber . ' for file ' . $filename);
		} else {
			// check if all the parts present, and create the final destination file
			$absChunkDir = $this->modelFs->rootDir . '/' . $chunkDir;
			$this->createFileFromChunks($absChunkDir, $filename, $resumabled['resumableTotalChunks']);
		}
	}
}