<?php
namespace site\fe\matter\mission;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 项目行为追踪
 */
class analysis extends \site\fe\matter\base {
	/**
	 *
	 */
	public function submit_action($mission, $page = '') {
		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission, ['cascaded' => 'N']);
		if ($oMission === false || $oMission->state !== '1') {
			return new \ObjectNotFoundError('指定的项目不存在，请检查参数是否正确');
		}
		$oPosted = $this->getPostJson();
		if (empty($oPosted)) {
			return new \ParameterError();
		}

		$oUser = $this->who;

		$oClient = new \stdClass;
		$oClient->agent = $_SERVER['HTTP_USER_AGENT'];
		$oClient->ip = $this->client_ip();

		$aResult = $this->model('matter\mission\analysis')->submit($oMission, $oUser, $oPosted, $page, $oClient);
		if ($aResult[0] === false) {
			return new \ResponseError($aResult[1]);
		}

		return new \ResponseData($aResult[1]);
	}
}