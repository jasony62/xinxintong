<?php
namespace pl\fe\site\member;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 自定义用户控制器
 */
class tag extends \pl\fe\base {
	/**
	 * 获得所有标签
	 *
	 * $authid 每个认证接口下可以定义标签
	 *
	 * todo 如何排序？
	 */
	public function list_action($site, $schema) {
		return new \ResponseData(array());
	}
}