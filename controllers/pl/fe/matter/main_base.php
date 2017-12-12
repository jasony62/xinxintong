<?php
namespace pl\fe\matter;

require_once dirname(__FILE__) . '/base.php';
/**
 * 素材控制器基类
 */
class main_base extends \pl\fe\matter\base {
	/**
	 * 恢复被删除的素材
	 */
	public function restore_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$matterType = $this->getMatterType();
		$modelMat = $this->model('matter\\' . $matterType);
		if (false === ($oMatter = $modelMat->byId($id, $modelMat::LOG_FIELDS))) {
			return new \ObjectNotFoundError('数据已经被彻底删除，无法恢复');
		}
		$rst = $modelMat->restore($oUser, $oMatter);

		return new \ResponseData($rst);
	}
}