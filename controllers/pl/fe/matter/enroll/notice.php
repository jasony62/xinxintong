<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 *
 */
class notice extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 给登记活动的参与人发消息
	 *
	 * @param string $site site'id
	 * @param string $app app'id
	 * @param string $tmplmsg 模板消息id
	 *
	 */
	public function notify_action($site, $app, $tmplmsg, $rid = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRec = $this->model('matter\enroll\record');
		$site = $modelRec->escape($site);
		$app = $modelRec->escape($app);
		$posted = $this->getPostJson();
		$params = $posted->message;

		if (isset($posted->criteria)) {
			// 筛选条件
			$criteria = $posted->criteria;
			$options = [
				'rid' => $rid,
			];
			$participants = $modelRec->participants($site, $app, $options, $criteria);
		} else if (isset($posted->users)) {
			// 直接指定
			$participants = $posted->users;
		}

		if (count($participants)) {
			$rst = $this->notifyWithMatter($site, $app, $participants, $tmplmsg, $params);
			if ($rst[0] === false) {
				return new \ResponseError($rst[1]);
			}
		}

		return new \ResponseData($participants);
	}
	/**
	 * 给用户发送素材
	 */
	protected function notifyWithMatter($siteId, $appId, &$userIds, $tmplmsgId, &$params) {
		if (count($userIds)) {
			$user = $this->accountUser();
			$modelTmplBat = $this->model('matter\tmplmsg\batch');
			$creater = new \stdClass;
			$creater->uid = $user->id;
			$creater->name = $user->name;
			$creater->src = 'pl';
			$modelTmplBat->send($siteId, $tmplmsgId, $creater, $userIds, $params, ['send_from' => 'enroll:' . $appId]);
		}

		return array(true);
	}
}