<?php
class local_model {

	// 当前公众平台
	protected $siteId;
	// 用于分割不同类型的存储资源
	protected $bucket;
	// 起始存储位置
	protected $rootDir;

	public function __construct($siteId, $bucket) {
		$this->siteId = $siteId;

		$this->bucket = $bucket;

		$this->rootDir = TMS_UPLOAD_DIR . "$this->siteId" . '/' . TMS_MODEL::toLocalEncoding($bucket);
		/* 检查根目录是否存在，不存在就创建 */
		!file_exists($this->rootDir) && mkdir($this->rootDir, 0777, true);
	}

	public function __get($attr) {
		if (isset($this->{$attr})) {
			return $this->{$attr};
		} else {
			return null;
		}
	}
	/**
	 * 返回指定文件名的文件的存储位置
	 */
	public function getPath($filename, $bEncoding = true) {
		$path = $this->rootDir;
		if (strpos($filename, '/') !== 0) {
			$path .= '/';
		}
		if ($bEncoding) {
			$path .= TMS_MODEL::toLocalEncoding($filename);
		} else {
			$path .= $filename;
		}

		return $path;
	}
	/**
	 * 将上传的文件文件保存在指定位置
	 *
	 * return bool
	 */
	public function upload($filename, $destName, $destDir) {
		//$absDir = $this->rootDir . '/' . TMS_MODEL::toLocalEncoding($destDir);
		$absDir = $this->rootDir . '/' . $destDir;
		// 目录是否存在
		!is_dir($absDir) && mkdir($absDir, 0777, true);
		// 文件的完整路径
		$filePath = $absDir . '/' . TMS_MODEL::toLocalEncoding($destName);
		// move the temporary file
		return move_uploaded_file($filename, $filePath);
	}
	/**
	 *
	 */
	public function getListByPath($dir) {
		//return $this->storage->getListByPath($this->domain, $this->siteId . '/' . $dir, 1000);
	}
	/**
	 *
	 */
	public function read($filename) {
		$absPath = $this->rootDir . '/' . TMS_MODEL::toLocalEncoding($filename);
		return file_get_contents($absPath);
	}
	/**
	 * 创建并打开文件
	 */
	public function createAndOpen($filename) {
		/* 文件的完整路径 */
		$absPath = $this->rootDir . (strpos($filename, '/') === 0 ? '' : '/') . $filename;
		/* 文件目录是否存在，不存在则创建 */
		$dirname = dirname($absPath);
		if (!file_exists($dirname)) {
			mkdir($dirname, 0777, true);
		}

		$fp = fopen($absPath, 'w');

		return $fp;
	}
	/**
	 *
	 */
	public function write($filename, $content) {
		/* 文件的完整路径 */
		//$absPath = $this->rootDir . '/' . TMS_MODEL::toLocalEncoding($filename);
		$absPath = $this->rootDir . '/' . $filename;

		/* 文件目录是否存在，不存在则创建 */
		$dirname = dirname($absPath);
		if (!file_exists($dirname)) {
			mkdir($dirname, 0777, true);
		}

		/* 将内容写入文件 */
		if (($fp = fopen($absPath, 'w')) !== false) {
			fwrite($fp, $content);
			fclose($fp);
			return $absPath;
		}

		return false;
	}
	/**
	 *
	 */
	public function delete($filename) {
		$abs = $this->rootDir . '/' . TMS_MODEL::toLocalEncoding($filename);
		if (file_exists($abs)) {
			return unlink($abs);
		} else {
			return false;
		}
	}
	/**
	 * 和alioss兼容
	 */
	public function writeFile($dir, $filename, $content) {
		$filename = $dir . '/' . $filename;

		return $this->write($filename, $content);
	}
	/**
	 * 和alioss兼容
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