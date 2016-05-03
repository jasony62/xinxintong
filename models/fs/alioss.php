<?php
class alioss_model {

	const ALIOSS_URL = 'http://xinxintong.oss-cn-hangzhou.aliyuncs.com';

	protected $mpid;

	protected $bucket;

	protected $hostname;

	private $rootDir;

	private $mapBucket2Host = array(
		'xinxintong' => 'oss-cn-hangzhou.aliyuncs.com',
		'xxt-attachment' => 'oss-cn-shanghai.aliyuncs.com',
	);
	/**
	 *
	 */
	public function __construct($mpid, $bucket = 'xinxintong', $domain = '_user') {
		$this->mpid = $mpid;

		$this->bucket = $bucket;

		$this->hostname = $this->mapBucket2Host[$bucket];

		$this->rootDir = $mpid . "/" . $domain;
	}
	/**
	 *
	 */
	protected function &get_alioss() {
		require_once dirname(dirname(dirname(__FILE__))) . '/lib/ali-oss/sdk.class.php';

		$oss_sdk_service = new ALIOSS(null, null, $this->hostname);
		//设置是否打开curl调试模式
		$oss_sdk_service->set_debug_mode(FALSE);

		return $oss_sdk_service;
	}
	/**
	 *
	 */
	public function getBaseUrl() {
		$url = 'http://';
		$url .= $this->bucket;
		$url .= '.' . $this->hostname;
		$url .= '/' . $this->rootDir;

		return $url;
	}
	/**
	 *
	 */
	public function getRootDir() {
		return $this->rootDir;
	}
	/**
	 *
	 */
	public function upload_file_by_file($target, $source) {
		$alioss = $this->get_alioss();
		$rsp = $alioss->upload_file_by_file($this->bucket, $target, $source);

		return $rsp;
	}
	/**
	 *
	 */
	public function delete_object($object, $options = NULL) {
		$alioss = $this->get_alioss();
		$rsp = $alioss->delete_object($this->bucket, $object, $options);

		return $rsp;
	}
	/**
	 * 初始化文件分段上传
	 */
	public function initiate_multipart_upload($object) {
		$alioss = $this->get_alioss();
		$upload = $alioss->initiate_multipart_upload($this->bucket, $object);

		if (!$upload->isOK()) {
			return array(false, 'Init multi-part upload failed...');
		}
		$xml = new SimpleXmlIterator($upload->body);
		$uploadId = (string) $xml->UploadId;

		return array(true, $uploadId);
	}
	/**
	 *
	 */
	public function upload_part($object, $uploadId, $filename, $partNumber, $size, $fileSize) {
		$alioss = $this->get_alioss();
		$rsp = $alioss->upload_part($this->bucket, $object, $uploadId, array(
			ALIOSS::OSS_FILE_UPLOAD => $filename,
			'partNumber' => (integer) $partNumber,
			ALIOSS::OSS_SEEK_TO => ($partNumber - 1) * $size,
			ALIOSS::OSS_LENGTH => (integer) $fileSize,
		));

		return $rsp;
	}
	/**
	 *
	 */
	public function complete_multipart_upload($object, $uploadId, $upload_parts) {
		$alioss = $this->get_alioss();
		$rsp = $alioss->complete_multipart_upload($this->bucket, $object, $uploadId, $upload_parts);
		return $rsp;
	}
	/**
	 *
	 */
	public function create_mpu_object($object, $filename) {
		$alioss = $this->get_alioss();

		$rsp = $alioss->create_mpu_object(
			$this->bucket,
			$object,
			array(
				ALIOSS::OSS_FILE_UPLOAD => $filename,
				ALIOSS::OSS_PART_SIZE => 5242880,
			)
		);

		return $rsp;
	}
	/**
	 * 将文件上传到alioss
	 */
	public function writeFile($dir, $filename, $content) {
		/**
		 * 写到临时文件中
		 */
		if (defined('SAE_TMP_PATH')) {
			$tmpfname = tempnam(SAE_TMP_PATH, 'xxt');
		} else {
			$tmpfname = tempnam($dir, 'xxt');
		}
		$handle = fopen($tmpfname, "w");
		fwrite($handle, $content);
		fclose($handle);

		$target = "$this->rootDir/$dir/$filename";

		$alioss = $this->get_alioss();
		$rsp = $alioss->upload_file_by_file($this->bucket, $target, $tmpfname);

		return self::ALIOSS_URL . '/' . $target;
	}
	/**
	 * $url
	 */
	public function remove($url) {
		$file = str_replace(self::ALIOSS_URL . '/', '', $url);
		$alioss = $this->get_alioss();
		$rsp = $alioss->delete_object($this->bucket, $file);

		//if ($rsp->status != 200)
		//    return array(false, $rsp);

		return array(true);
	}
	/**
	 *
	 */
	public function getFile($url) {
		$file = str_replace(self::ALIOSS_URL . '/', '', $url);
		$alioss = $this->get_alioss();
		$rsp = $alioss->get_object($this->bucket, $file);

		//if ($rsp->status != 200)
		//    return array(false, $rsp);

		return array(true, $rsp->body);
	}
}