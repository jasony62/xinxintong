<?php
namespace site\fe\matter;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 素材标签
 */
class tag extends \site\fe\base {
	/**
	 * 根据资源类型获得已有的标签
	 *
	 * @param string $resType
	 * @param int 标签的分类
	 */
	public function list_action($resType, $subType = 0) {
		$tags = $this->model('tag')->get_tags($this->siteId, $resType, $subType);

		return new \ResponseData($tags);
	}
}