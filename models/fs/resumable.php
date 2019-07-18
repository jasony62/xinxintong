<?php
/**
 * 分段上传文件
 */
class resumable_model {

	private $siteId;
	/**
	 * 文件存储区域
	 */
	private $domain;
	/**
	 * 文件保存位置
	 */
	private $dest;
	/**
	 *
	 */
	public function __construct($siteId, $dest, $domain = '') {
		$this->siteId = $siteId;

		$this->dest = $dest; // 分段上传文件的保存位置

		$this->domain = $domain;
	}
	/**
	 *
	 * Logging operation
	 *
	 * @param string $str - the logging string
	 */
	private function _log($str) {}
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
	private function _createFileFromChunks($chunkDir, $fileName, $chunkTotal) {
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
			$localFs = \TMS_APP::M('fs/local', $this->siteId, '_resumable');
			if (($fp = $localFs->createAndOpen(\TMS_MODEL::toLocalEncoding($this->dest), 'w')) !== false) {
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
			// rename the temporary directory (to avoid access from other concurrent chunks uploads) and than delete it
			if (rename($chunkDir, $chunkDir . '_UNUSED')) {
				$this->rrmdir($chunkDir . '_UNUSED');
			} else {
				$this->rrmdir($chunkDir);
			}
			if (!empty($this->domain)) {
				if (defined('APP_FS_USER') && APP_FS_USER === 'ali-oss') {
					$fsAlioss = \TMS_APP::M('fs/alioss', $this->siteId, $this->domain);
					$fullpath = $localFs->getPath($this->dest, false);
					$fsAlioss->create_mpu_object($this->dest, $fullpath);
					$fullpath = $localFs->getPath($this->dest, true);
					unlink($fullpath);
				}
			}
		}

		return [true];
	}
	/**
	 *
	 * Check if all the parts exist, and
	 * gather all the parts of the file together
	 *
	 * @param string $temp_dir - the temporary directory holding all the parts of the file
	 * @param string $fileName - the original file name
	 * @param string $chunkSize - each chunk size (in bytes)
	 * @param string $totalSize - original file size (in bytes)
	 */
	private function _createFileFromChunksSae($chunkDir, $fileName, $chunkSize, $totalSize) {
		$fsSae = \TMS_APP::M('fs/saestore', $this->siteId);
		// count all the parts of this file
		$total_files = 0;
		$rst = $fsSae->getListByPath($chunkDir);
		foreach ($rst['files'] as $file) {
			if (stripos($file['Name'], $fileName) !== false) {
				$total_files++;
			}
		}
		// check that all the parts are present
		// the size of the last part is between chunkSize and 2*$chunkSize
		if ($total_files * $chunkSize >= ($totalSize - $chunkSize + 1)) {
			// create the final destination file
			$tmpfname = tempnam(SAE_TMP_PATH, 'xxt');
			$handle = fopen($tmpfname, "w");
			for ($i = 1; $i <= $total_files; $i++) {
				$content = $fsSae->read($chunkDir . '/' . $fileName . '.part' . $i);
				fwrite($handle, $content);
				$fsSae->delete($chunkDir . '/' . $fileName . '.part' . $i);
			}
			fclose($handle);
			//
			if (defined('APP_FS_USER') && APP_FS_USER === 'ali-oss') {
				$fsAlioss = \TMS_APP::M('fs/alioss', $this->siteId);
				$rsp = $fsAlioss->create_mpu_object($this->dest, $tmpfname);
				if (false === $rsp[0]) {
					return $rsp;
				}
			}
		}

		return [true];
	}
	/**
	 * 处理分段上传的请求
	 */
	public function handleRequest($aResumabled) {
		// 文件大小限制
		if (TMS_UPLOAD_FILE_MAXSIZE > 0) {
			$maxSize = (int) TMS_UPLOAD_FILE_MAXSIZE * 1024 * 1024;
			if ($aResumabled['resumableTotalSize'] > $maxSize) {
				return [false, '文件上传失败，超出最大值' . TMS_UPLOAD_FILE_MAXSIZE . 'M'];
			}
		}
		// 限制文件类型 白名单
		if (defined('TMS_UPLOAD_FILE_CONTENTTYPE_WHITE') && !empty(TMS_UPLOAD_FILE_CONTENTTYPE_WHITE)) {
			$contentType = explode(',', TMS_UPLOAD_FILE_CONTENTTYPE_WHITE);
			if (!in_array($aResumabled['resumableType'], $contentType)) {
				return [false, '文件上传失败，只支持' . TMS_UPLOAD_FILE_CONTENTTYPE_WHITE . '格式的文件'];
			}
		}
		//
		if (defined('SAE_TMP_PATH')) {
			$chunkDir = $aResumabled['resumableIdentifier'];
			$dest_file = $chunkDir . '/' . $aResumabled['resumableFilename'] . '.part' . $aResumabled['resumableChunkNumber'];
			$content = base64_decode(preg_replace('/data:(.*?)base64\,/', '', $aResumabled['resumableChunkContent']));
			// move the temporary file
			$fsSae = \TMS_APP::M('fs/saestore', $this->siteId);
			if (!$fsSae->write($dest_file, $content)) {
				return [false, 'Error saving (move_uploaded_file) chunk ' . $aResumabled['resumableChunkNumber'] . ' for file ' . $aResumabled['resumableFilename']];
			} else {
				// check if all the parts present, and create the final destination file
				$this->_createFileFromChunksSae($chunkDir, $aResumabled['resumableFilename'], $aResumabled['resumableChunkSize'], $aResumabled['resumableTotalSize']);
				return [true];
			}
		} else {
			$localFs = \TMS_APP::M('fs/local', $this->siteId, '_resumable');
			$chunkNumber = $aResumabled['resumableChunkNumber'];
			$filename = str_replace(' ', '_', $aResumabled['resumableFilename']);
			$chunkDir = $aResumabled['resumableIdentifier'] . '_part';
			$chunkFile = \TMS_MODEL::toLocalEncoding($filename) . '.part' . $chunkNumber;
			$content = base64_decode(preg_replace('/data:(.*?)base64\,/', '', $aResumabled['resumableChunkContent']));
			$ret = $localFs->write($chunkDir . '/' . $chunkFile, $content);
			if (false === $ret) {
				return [false, 'Error saving chunk ' . $chunkNumber . ' for file ' . $filename];
			} else {
				// check if all the parts present, and create the final destination file
				$absChunkDir = $localFs->rootDir . '/' . $chunkDir;
				$rsp = $this->_createFileFromChunks($absChunkDir, $filename, $aResumabled['resumableTotalChunks'], $aResumabled['resumableTotalSize']);
				return $rsp;
			}
		}
	}
}