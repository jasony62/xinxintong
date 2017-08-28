<?php
namespace pl\fe\matter\signin;

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

		$modelRev = $this->model('matter\signin\receiver');

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

		$modelRev = $this->model('matter\signin\receiver');

		$receivers = $modelRev->afterJoin($site, $app, $timestamp);

		return new \ResponseData($receivers);
	}
	/**
	 * 删除接收消息的人
	 *
	 * @param string site
	 * @param string $app
	 * @param string $receiver
	 */
	public function remove_action($site, $app, $receiver) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('matter\signin');
		$signin = $model->byId($app, array('cascaded' => 'Y'));
		$rst = $model->delete(
			'xxt_signin_receiver',
			"siteid='$site' and aid='$app' and userid='$receiver'"
		);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $user, $signin, 'D');

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

		$modelApp = $this->model('matter\signin');
		$oApp = $modelApp->byId($app, ['cascaded' => 'Y']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$users = $this->getPostJson();
		if (0 === count($users)) {
			return new \ParameterError();
		}

		$modelRev = $this->model('matter\signin\receiver');
		$modelAct = $this->model('site\user\account');

		foreach ($users as $user) {
			$uid = $user->uid;
			$nickname = $user->nickname;

			if (empty($modelRev->query_obj_ss(['*', 'xxt_signin_receiver', "siteid='$site' and aid='$app' and userid='$uid'"]))) {
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
					'xxt_signin_receiver',
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
	/**
	 * 获取企业号关注用户
	 */
	public function qyMem_action($site, $page = 1, $size = 20) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$data = $this->getPostJson();
		$keyword = isset($data) ? $data->keyword : '';

		$rst = $this->model("sns\\qy\\fan")->getMem($site, $keyword, $page, $size);

		return new \ResponseData($rst);
	}
}