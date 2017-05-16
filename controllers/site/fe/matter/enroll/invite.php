<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动用户邀请
 */
class invite extends base {
	/**
	 * 发出邀请
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $invitee
	 * @param string $page
	 */
	public function inviteSend_action($site, $app, $ek, $invitee, $page = '') {
		/*获得被邀请人的信息*/
		$options = array('fields' => 'openid');
		$members = $this->model('user/member')->search($site, $invitee, $options);
		if (empty($members)) {
			return new \ResponseError("指定的用户不存在");
		}
		$openid = $members[0]->openid;

		/*给邀请人发消息*/
		$message = \TMS_APP::M('matter\enroll')->forCustomPush($site, $app);
		$url = $message['news']['articles'][0]['url'];
		$url .= "&ek=$ek";
		!empty($page) && $url .= "&page=$page";
		$message['news']['articles'][0]['url'] = $url;
		$rst = $this->sendByOpenid($site, $openid, $message);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		return new \ResponseData($members);
	}
	/**
	 * 记录参加登记活动的用户之间的邀请关系
	 * 邀请必须依赖于某条已经存在的登记记录
	 *
	 * $param inviter enroll_key
	 */
	public function acceptInvite_action($site, $app, $inviter, $state = '1') {
		$model = $this->model('app\enroll');
		if (false === ($oApp = $model->byId($app))) {
			return new \ParameterError("指定的活动（$app）不存在");
		}
		/* 当前访问用户的基本信息 */
		$user = $this->getUser($site,
			array(
				'authapis' => $oApp->authapis,
				'matter' => $oApp,
				'verbose' => array('member' => 'Y', 'fan' => 'Y'),
			)
		);
		/* 如果已经有登记记录则不登记 */
		$modelRec = $this->model('matter\enroll\record');
		if ($state === '1') {
			$ek = $modelRec->lastKeyByUser($oApp, $user);
			if (!empty($ek)) {
				$rsp = new \stdClass;
				$rsp->ek = $ek;
				return new \ResponseData($rsp);
			}
		} else {
			$ek = $modelRec->hasAcceptedInvite($oApp, $user->openid, $inviter);
		}
		if (false === $ek) {
			/* 创建登记记录*/
			$ek = $modelRec->add($site, $oApp, $user, 'ek:' . $inviter);
			if ($state !== '1') {
				/*不作为独立的记录，只是接收邀请的日志*/
				$modelRec->modify($ek, array('state' => 2));
			}
			/** 处理提交数据 */
			$data = $_GET;
			unset($data['site']);
			unset($data['app']);
			if (!empty($data)) {
				$data = (object) $data;
				$rst = $modelRec->setData($user, $oApp, $ek, $data);
				if (false === $rst[0]) {
					return new ResponseError($rst[1]);
				}
			}
			/*记录邀请数*/
			$modelRec->update("update xxt_enroll_record set follower_num=follower_num+1 where enroll_key='$inviter'");
			/*邀请成功的积分奖励*/
			$inviteRecord = $modelRec->byId($inviter, array('cascaded' => 'N', 'fields' => 'openid'));
			$modelCoin = $this->model('coin\log');
			$action = 'app.enroll,' . $oApp->id . '.invite.success';
			$modelCoin->income($site, $action, $oApp, 'sys', $inviteRecord->openid);
		}
		$rsp = new \stdClass;
		$rsp->ek = $ek;

		return new \ResponseData($rsp);
	}
}