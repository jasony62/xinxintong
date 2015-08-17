<?php
class local_model {

	// 当前公众平台
	protected $mpid;
	// 用于分割不同类型的存储资源
	protected $bucket;
	// 起始存储位置
	protected $rootDir;

	public function __construct($mpid, $bucket) {
		$this->mpid = $mpid;

		$this->bucket = $bucket;

		$this->rootDir = TMS_UPLOAD_DIR . "$this->mpid" . '/' . $bucket;
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
	 * 将上传的文件文件保存在指定位置
	 *
	 * return bool
	 */
	public function upload($filename, $destName, $destDir) {
		$absDir = $this->rootDir . '/' . $destDir;
		// 目录是否存在
		!is_dir($absDir) && mkdir($absDir, 0777, true);
		// 文件的完整路径
		$filePath = $absDir . '/' . $destName;
		// move the temporary file
		return move_uploaded_file($filename, $filePath);
	}
	/**
	 *
	 */
	public function getListByPath($dir) {
		//return $this->storage->getListByPath($this->domain, $this->mpid . '/' . $dir, 1000);
	}
	/**
	 *
	 */
	public function read($filename) {
		$absPath = $this->rootDir . '/' . $filename;
		return file_get_contents($absPath);
	}

	public function write($filename, $content) {
		//return $this->storage->write($this->domain, $this->mpid . '/' . $filename, $content);
	}

	public function delete($filename) {
		return unlink($this->rootDir . '/' . $filename);
	}
}