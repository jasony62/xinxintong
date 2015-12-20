<?php
class sae_fs {

	protected $domain;

	public function __construct($mpid, $domain = 'attachment') {
		$this->domain = $domain;

		$this->storage = new SaeStorage();
	}
	/**
	 * 上传文件
	 */
	public function upload($destFile, $srcFile) {
		return $this->storage->upload($this->domain, $destFile, $srcFile);
	}
	/**
	 *
	 */
	public function getListByPath($dir) {
		return $this->storage->getListByPath($this->domain, $dir, 1000);
	}

	public function read($filename) {
		return $this->storage->read($this->domain, $filename);
	}

	public function write($filename, $content) {
		return $this->storage->write($this->domain, $filename, $content);
	}

	public function delete($filename) {
		return $this->storage->delete($this->domain, $filename);
	}
}
/**
 *
 */
class attachment_model {

	private $mpid;

	private $service;

	public function __construct($mpid, $bucket = 'attachment') {
		$this->mpid = $mpid;
		$this->service = new sae_fs($mpid, $bucket);
	}

	public function upload($destFile, $srcFile) {
		return $this->service->upload($this->mpid . '/' . $destFile, $srcFile);
	}

	public function getListByPath($dir) {
		return $this->service->getListByPath($this->mpid . '/' . $dir);
	}

	public function read($filename) {
		return $this->service->read($this->mpid . '/' . $filename);
	}

	public function write($filename, $content) {
		return $this->service->write($this->mpid . '/' . $filename, $content);
	}

	public function delete($filename) {
		return $this->service->delete($this->mpid . '/' . $filename);
	}
}