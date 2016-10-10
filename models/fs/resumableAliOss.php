<?php
/**
 * 阿里云分段上传
 */
class resumableAliOss_model {

	private $siteId;
	/**
	 * 文件上传后的保存位置
	 */
	private $dest;

	private $domain;

	public function __construct($siteId, $dest, $domain = null) {

		$this->siteId = $siteId;

		$this->dest = $dest;

		$this->domain = $domain;
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
	private function createFileFromChunks($temp_dir, $fileName, $chunkSize, $totalSize) {
		$fsSae = \TMS_APP::M('fs/saestore', $this->siteId);
		// count all the parts of this file
		$total_files = 0;
		$rst = $fsSae->getListByPath($temp_dir);
		foreach ($rst['files'] as $file) {
			if (stripos($file['Name'], $fileName) !== false) {
				$total_files++;
			}
		}
		// check that all the parts are present
		// the size of the last part is between chunkSize and 2*$chunkSize
		if ($total_files * $chunkSize >= ($totalSize - $chunkSize + 1)) {
			$fsAlioss = \TMS_APP::M('fs/alioss', $this->siteId, 'xxt-attachment');
			// create the final destination file
			if (defined('SAE_TMP_PATH')) {
				$tmpfname = tempnam(SAE_TMP_PATH, 'xxt');
			} else {
				$tmpfname = tempnam(sys_get_temp_dir(), 'xxt');
			}
			$handle = fopen($tmpfname, "w");
			for ($i = 1; $i <= $total_files; $i++) {
				$content = $fsSae->read($temp_dir . '/' . $fileName . '.part' . $i);
				fwrite($handle, $content);
				$fsSae->delete($temp_dir . '/' . $fileName . '.part' . $i);
			}
			fclose($handle);
			//
			$rsp = $fsAlioss->create_mpu_object($this->siteId . $this->dest, $tmpfname);
			echo (json_encode($rsp));
		}
	}
	/**
	 *
	 */
	public function handleRequest($resumabled) {
		// init the destination file (format <filename.ext>.part<#chunk>
		// the file is stored in a temporary directory
		$temp_dir = $resumabled['resumableIdentifier'];
		$dest_file = $temp_dir . '/' . $resumabled['resumableFilename'] . '.part' . $resumabled['resumableChunkNumber'];
		$content = base64_decode(preg_replace('/data:(.*?)base64\,/', '', $resumabled['resumableChunkContent']));
		// move the temporary file
		$fsSae = \TMS_APP::M('fs/saestore', $this->siteId);
		if (!$fsSae->write($dest_file, $content)) {
			return array(false, 'Error saving (move_uploaded_file) chunk ' . $resumabled['resumableChunkNumber'] . ' for file ' . $resumabled['resumableFilename']);
		} else {
			// check if all the parts present, and create the final destination file
			$this->createFileFromChunks($temp_dir, $resumabled['resumableFilename'], $resumabled['resumableChunkSize'], $resumabled['resumableTotalSize']);
			return array(true);
		}
	}
}