<?php
namespace pl\fe\matter;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台主页素材
 */
class home extends \pl\fe\base {
	/**
	 *
	 *
	 * @param string $resType
	 * @param int 标签的分类
	 */
	public function apply_action($site, $type, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelHome = $this->model('matter\home');

		$matter = $this->model('matter\\' . $type)->byId($id, ['cascaded' => 'N']);
		$matter->type = $type;

		$reply = $modelHome->putMatter($site, $user, $matter);

		return new \ResponseData($reply);
	}
}