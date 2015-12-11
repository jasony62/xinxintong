<?php
class saestore_model {

	protected $domain;

	public function __construct($mpid, $domain = 'attachment') {
		$this->mpid = $mpid;

		$this->domain = $domain;

		$this->storage = new SaeStorage();
	}
	/**
	 * 上传文件
	 */
	public function upload($destFile, $srcFile) {
		return $this->storage->upload($this->domain, $this->mpid . '/' . $destFile, $srcFile);
	}
	/**
	 *
	 */
	public function getListByPath($dir) {
		return $this->storage->getListByPath($this->domain, $this->mpid . '/' . $dir, 1000);
	}

	public function read($filename) {
		return $this->storage->read($this->domain, $this->mpid . '/' . $filename);
	}

	public function write($filename, $content) {
		return $this->storage->write($this->domain, $this->mpid . '/' . $filename, $content);
	}

	public function delete($filename) {
		return $this->storage->delete($this->domain, $this->mpid . '/' . $filename);
	}
}