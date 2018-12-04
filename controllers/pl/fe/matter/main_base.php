<?php
namespace pl\fe\matter;

require_once dirname(__FILE__) . '/base.php';
/**
 * 素材控制器基类
 */
class main_base extends \pl\fe\matter\base {
	/**
	 * 补充entryRule的数据
	 */
	protected function fillEntryRule(&$oEntryRule) {
		/* 关联记录活动 */
		if (empty($oEntryRule)) {
			return false;
		}
		if (isset($oEntryRule->member) && is_object($oEntryRule->member)) {
			$modelMs = $this->model('site\user\memberschema');
			foreach ($oEntryRule->member as $msid => $oRule) {
				$oMschema = $modelMs->byId($msid, ['fields' => 'title', 'cascaded' => 'N']);
				if ($oMschema) {
					$oRule->title = $oMschema->title;
				}
			}
		}

		return true;
	}
	/**
	 * 恢复被删除的素材
	 */
	public function restore_action($id) {
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