<?php
namespace pl\fe\matter\custom;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 文章控制器
 */
class tag extends \pl\fe\matter\base {
	/**
	 * 添加图文的标签
	 */
	public function add_action($site, $id) {
		$tags = $this->getPostJson();

		$this->model('tag')->save($site, $id, 'custom', 0, $tags, null);

		return new \ResponseData('ok');
	}
	/**
	 * 删除图文的标签
	 */
	public function remove_action($site, $id) {
		$tags = $this->getPostJson();

		$this->model('tag')->save($site, $id, 'custom', 0, null, $tags);

		return new \ResponseData('ok');
	}
}