<?php
require_once dirname(__FILE__) . '/alioss.php';

class local_fs {

	protected $mpid;

	protected $bucket;

	private $rootDir;

	public function __construct($mpid, $bucket = 'xinxintong') {
		$this->mpid = $mpid;

		$this->bucket = $bucket;

		$this->rootDir = TMS_UPLOAD_DIR . "$this->mpid/_user";
	}
	/**
	 * 将文件上传到alioss
	 */
	public function writeFile($dir, $filename, $content) {
		$fulldir = $this->rootDir . "/$dir";
		$storeAt = "$fulldir/$filename";

		!file_exists($fulldir) && mkdir($fulldir, 0755, true);
		/**
		 * 写到文件中
		 */
		$handle = fopen($storeAt, "w");
		fwrite($handle, $content);
		fclose($handle);
		false === chmod($storeAt, 0755) && die('chmod failed');

		return '/' . $storeAt;
	}
	/**
	 * $url
	 */
	public function remove($url) {
		die('not support.');
	}
	/**
	 *
	 */
	public function getFile($url) {
		die('not support.');
	}
}
/**
 *
 */
class user_model {

	private $mpid;

	private $service;

	public function __construct($mpid, $bucket = 'xinxintong') {
		$this->mpid = $mpid;
		if (defined('SAE_TMP_PATH')) {
			$this->service = new alioss_model($mpid, $bucket);
		} else {
			$this->service = new local_fs($mpid, $bucket);
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
	 * 将指定url的文件转存到oss
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
		$matches = array();
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

		return array(true, $newUrl);
	}
	/**
	 *
	 * $img
	 */
	public function storeImg($img) {
		if (empty($img->imgSrc) && !isset($img->serverId)) {
			return array(false, '图片数据为空');
		}

		if (isset($img->imgSrc) && 0 === strpos($img->imgSrc, 'http')) {
			$rst = $this->storeUrl($img->imgSrc);
		} else if (isset($img->imgSrc) && 1 === preg_match('/data:image(.+?);base64/', $img->imgSrc)) {
			/**
			 * base64
			 */
			$rst = $this->storeBase64Image($img->imgSrc);
		} else if (isset($img->serverId)) {
			/**
			 * wx jssdk
			 */
			$app = TMS_APP::model('mp\mpaccount')->byId($this->mpid);
			if ($app->mpsrc === 'wx') {
				$rst = TMS_APP::model('mpproxy/wx', $this->mpid)->mediaGetUrl($img->serverId);
			} else {
				$rst = TMS_APP::model('mpproxy/qy', $this->mpid)->mediaGetUrl($img->serverId);
			}
			if ($rst[0] === false) {
				return $rst;
			}
			$rst = $this->storeUrl($rst[1]);
		} else {
			return array(false, '图片数据格式错误');
		}

		return $rst;
	}
	/**
	 *
	 */
	public function storeBase64File($file) {
		$content = $file->content;

		$rst = preg_match('/data:(.+?);base64\,/', $content, $matches);
		if (1 !== $rst) {
			return array(false, '图片数据格式错误' . $rst);
		}

		$header = $matches[0];

		$content = base64_decode(str_replace($header, "", $content));

		$dir = date("ymdH"); // 每个小时分一个目录
		$storename = $file->name;
		/**
		 * 写到alioss
		 */
		$newUrl = $this->writeFile($dir, $storename, $content);

		return array(true, $newUrl);
	}
}