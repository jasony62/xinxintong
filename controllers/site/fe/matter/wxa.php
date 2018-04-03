<?php
namespace site\fe\matter;
/**
 * 微信小程序
 */
class wxa extends \TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$ruleAction = [
			'rule_type' => 'black',
		];

		return $ruleAction;
	}
	/**
	 * 获得访问对象的简单描述
	 */
	public function matterGet_action($matter) {
		$oMatter = new \stdClass;
		list($oMatter->type, $oMatter->id) = explode(',', $matter);
		$modelMat = $this->model('matter\\' . $oMatter->type);
		$oMatter = $modelMat->byId($oMatter->id, ['cascaded' => 'N', 'fields' => 'id,state,siteid,title,summary,pic']);
		if (false === $oMatter) {
			return new \ObjectNotFoundError('（1）访问的对象不存在');
		}

		return new \ResponseData($oMatter);
	}
}