<?php
require_once dirname(__FILE__) . '/local.php';
require_once dirname(__FILE__) . '/alioss.php';
/**
 * 用户存储
 */
class user_model {

	private $siteId;
	/**
	 * 文件存储服务
	 */
	private $service;

	public function __construct($siteId, $bucket = 'xinxintong') {
		$this->siteId = $siteId;
		if (defined('SAE_TMP_PATH')) {
			$this->service = new alioss_model($siteId, $bucket);
		} else {
			$this->service = new local_model($siteId, '_user');
		}
	}
	/**
	 * 将文件上传到alioss
	 */
	protected function writeFile($dir, $filename, $fileUpload) {
		return $this->service->writeFile($dir, $filename, $fileUpload);
	}
	/**
	 * $url
	 */
	public function remove($url) {
		return $this->service->remove($url);
	}
	/**
	 *
	 */
	public function get($url) {
		return $this->service->getFile($url);
	}
	/**
	 * 存储指定url对应的文件
	 */
	public function storeUrl($url) {
		/**
		 * 下载文件
		 */
		$ext = 'jpg';
		$response = file_get_contents($url);
		$responseInfo = $http_response_header;
		foreach ($responseInfo as $loop) {
			if (strpos($loop, "Content-disposition") !== false) {
				$disposition = trim(substr($loop, 21));
				$filename = explode(';', $disposition);
				$filename = array_pop($filename);
				$filename = explode('=', $filename);
				$filename = array_pop($filename);
				$filename = str_replace('"', '', $filename);
				$filename = explode('.', $filename);
				$ext = array_pop($filename);
				break;
			}
		}
		$dir = date("ymdH"); // 每个小时分一个目录
		$storename = date("is") . rand(10000, 99999) . "." . $ext;
		/**
		 * 写到alioss
		 */
		$newUrl = $this->writeFile($dir, $storename, $response);

		return array(true, $newUrl);
	}
	/**
	 * 存储base64的文件数据
	 */
	private function storeBase64Image($data) {
		$matches = [];
		$rst = preg_match('/data:image\/(.+?);base64\,/', $data, $matches);
		if (1 !== $rst) {
			return array(false, '图片数据格式错误' . $rst);
		}

		list($header, $ext) = $matches;
		$ext === 'jpeg' && $ext = 'jpg';

		$pic = base64_decode(str_replace($header, "", $data));

		$dir = date("ymdH"); // 每个小时分一个目录
		$storename = date("is") . rand(10000, 99999) . "." . $ext;
		/**
		 * 写到alioss
		 */
		$newUrl = $this->writeFile($dir, $storename, $pic);

		return [true, $newUrl];
	}
	/**
	 *
	 * $img
	 */
	public function storeImg($img) {
		if (empty($img->imgSrc) && !isset($img->serverId)) {
			return [false, '图片数据为空'];
		}
		if (isset($img->serverId)) {
			/**
			 * wx jssdk
			 */
			if (($snsConfig = TMS_APP::model('sns\wx')->bySite($this->siteId)) && $snsConfig->joined === 'Y') {
				$snsProxy = TMS_APP::model('sns\wx\proxy', $snsConfig);
			} else if (($snsConfig = TMS_APP::model('sns\wx')->bySite('platform')) && $snsConfig->joined === 'Y') {
				$snsProxy = TMS_APP::model('sns\wx\proxy', $snsConfig);
			} else if ($snsConfig = TMS_APP::model('sns\qy')->bySite($this->siteId)) {
				if ($snsConfig->joined === 'Y') {
					$snsProxy = TMS_APP::model('sns\qy\proxy', $snsConfig);
				}
			}
			$rst = $snsProxy->mediaGetUrl($img->serverId);
			if ($rst[0] !== false) {
				$rst = $this->storeUrl($rst[1]);
			}
		} else if (isset($img->imgSrc)) {
			if (0 === strpos($img->imgSrc, 'http')) {
				/**
				 * url
				 */
				$rst = $this->storeUrl($img->imgSrc);
			} else if (false !== strpos($img->imgSrc, TMS_UPLOAD_DIR)) {
				/**
				 * 已经上传本地的
				 */
				$rst = [true, $img->imgSrc];
			} else if (1 === preg_match('/data:image(.+?);base64/', $img->imgSrc)) {
				/**
				 * base64
				 */
				$rst = $this->storeBase64Image($img->imgSrc);
			}
		}

		if (isset($rst)) {
			return $rst;
		} else {
			return [false, '图片数据格式错误：' . json_encode($img)];
		}
	}
}