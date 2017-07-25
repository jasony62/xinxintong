<?php
namespace pl\fe\matter;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 素材标签
 */
class tag extends \pl\fe\base {
	/**
	 * 根据资源类型获得已有的标签
	 *
	 * @param string $resType
	 * @param int 标签的分类
	 */
	public function list_action($site, $resType, $subType = 0) {
		$tags = $this->model('tag')->get_tags($site, $resType, $subType);

		return new \ResponseData($tags);
	}
	/**
	 * 添加内容的标签
	 *  @param string $resType 素材类型
	 *  @param string $resId 素材id
	 *  @param string $subType 标签类型
	 */
	public function add_action($site, $resId, $resType, $subType = 1) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$tags = $this->getPostJson();

		$model = $this->model('tag');
		$model->setOnlyWriteDbConn(true);
		$model->save2($site, $user, $resId, $resType, $subType, $tags);

		return new \ResponseData('ok');
	}
}