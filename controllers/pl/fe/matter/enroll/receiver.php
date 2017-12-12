<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动控制器
 */
class receiver extends \pl\fe\matter\base {
	/**
	 * 事件通知接收人
	 *
	 * @param string site
	 * @param string $app
	 */
	public function list_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRev = $this->model('matter\enroll\receiver');

		$receivers = $modelRev->byApp($site, $app);

		return new \ResponseData($receivers);
	}
	/**
	 * 检查加入的接收人
	 */
	public function afterJoin_action($site, $app, $timestamp = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		if (empty($timestamp)) {
			return new \ResponseData([]);
		}

		$modelRev = $this->model('matter\enroll\receiver');

		$receivers = $modelRev->afterJoin($site, $app, $timestamp);

		return new \ResponseData($receivers);
	}
	/**
	 * 删除接收消息的人
	 *
	 * @param string $app
	 * @param string $receiver
	 */
	public function remove_action($app, $receiver) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/* 记录操作日志 */
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'Y']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$rst = $modelEnl->delete(
			'xxt_enroll_receiver',
			['id' => $receiver]
		);

		return new \ResponseData($rst);
	}
	/**
	 * 添加自定义用户作为登记活动事件接收人
	 *
	 * @param string $site
	 * @param string $app
	 *
	 */
	public function add_action($site, $app) {
		if (false === ($u = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'Y']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$users = $this->getPostJson();
		if (0 === count($users)) {
			return new \ParameterError();
		}

		$modelRev = $this->model('matter\enroll\receiver');
		$modelAct = $this->model('site\user\account');

		foreach ($users as $user) {
			$uid = $user->uid;
			$nickname = $user->nickname;

			if (empty($modelRev->query_obj_ss(['*', 'xxt_enroll_receiver', "siteid='$site' and aid='$app' and userid='$uid'"]))) {
				$account = $modelAct->byId($uid);
				$arr = new \stdClass;
				if (!empty($account->wx_openid)) {
					$arr->openid = $account->wx_openid;
					$arr->src = 'wx';
				}
				if (!empty($account->yx_openid)) {
					$arr->openid = $account->yx_openid;
					$arr->src = 'yx';
				}
				if (!empty($account->qy_openid)) {
					$arr->openid = $account->qy_openid;
					$arr->src = 'qy';
				}
				$rst[] = $modelRev->insert(
					'xxt_enroll_receiver',
					[
						'siteid' => $site,
						'aid' => $oApp->id,
						'join_at' => time(),
						'userid' => $uid,
						'nickname' => empty($nickname) ? '未知姓名' : $modelRev->escape($nickname),
						'sns_user' => json_encode($arr),
					],
					false
				);
			} else {
				$rst[] = true;
			}
		}
		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $u, $oApp, 'C');

		return new \ResponseData($rst);
	}
}