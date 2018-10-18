<?php
namespace pl\fe\site\user;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 消息通知控制器
 */
class notice extends \pl\fe\base {
	/**
	 * 给团队用户发送消息
	 *
	 * @param string $site site's id
	 *
	 */
	public function send_action($site) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oSite = $this->model('site')->byId($site, ['fields' => 'id']);
		if (false === $oSite) {
			return new \ObjectNotFountError();
		}

		$oPosted = $this->getPostJson();
		if (empty($oPosted->tmplmsg) || empty($oPosted->users)) {
			return new \ResponseError('参数不完整');
		}

		/* 发送消息 */
		$aResult = $this->notifyWithMatter($oSite->id, $oPosted->users, $oPosted->tmplmsg, $oPosted->message);
		if ($aResult[0] === false) {
			return new \ResponseError($aResult[1]);
		}

		return new \ResponseData($aResult[1]);
	}
	/**
	 * 给用户发送素材
	 */
	protected function notifyWithMatter($siteid, &$users, $tmplmsgId, &$params) {
		$receivers = [];
		foreach ($users as $oUser) {
			if (empty($oUser->userid)) {
				continue;
			}
			$oReceiver = new \stdClass;
			$oReceiver->userid = $oUser->userid;
			$receivers[] = $oReceiver;
		}
		if (empty($receivers)) {
			return [false, '用户信息不完整'];
		}

		$oUser = $this->accountUser();
		$oCreator = new \stdClass;
		$oCreator->uid = $oUser->id;
		$oCreator->name = $oUser->name;
		$oCreator->src = 'pl';
		$modelTmplBat = $this->model('matter\tmplmsg\batch');
		$modelTmplBat->send($siteid, $tmplmsgId, $oCreator, $receivers, $params);

		return [true, $receivers];
	}
}