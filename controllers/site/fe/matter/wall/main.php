<?php
namespace site\fe\matter\wall;

include_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 进入信息墙
 */
class main extends \site\fe\base {
	/**
	 *
	 */
	public function index_action($site, $app) {
		if (!$this->afterSnsOAuth()) {
			/* 检查是否需要第三方社交帐号OAuth */
			$this->_requireSnsOAuth($site);
		}
		$oWall = $this->model('matter\wall')->byId($app);
		if (false === $oWall) {
			$this->outputError('指定的信息墙不存在');
			exit;
		}
		/* 检查活动状态 */
		$current = time();
		if ($oWall->start_at != 0 && $current < $oWall->start_at) {
			$this->outputError('【' . $oWall->title . '】没有开始');
			exit;
		} else if ($oWall->end_at != 0 && $current > $oWall->end_at) {
			$this->outputError('【' . $oWall->title . '】已经结束');
			exit;
		}

		\TPL::assign('title', $oWall->title);
		\TPL::output('/site/fe/matter/wall/main');
		exit;
	}
	/**
	 * 用户参与的信息墙
	 */
	public function user_action($site) {
		\TPL::output('/site/fe/matter/wall/user');
		exit;
	}
	/**
	 * 用户加入信息墙
	 */
	public function join_action($site, $app) {
		$user = $this->who;
		if (!isset($user->sns)) {
			return new \ResponseData(false);
		}
		$user2 = new \stdClass;
		if (isset($user->sns->wx)) {
			//获取nickname
			$snsUser = $this->model('sns\wx\fan')->byOpenid($site, $user->sns->wx->openid, 'nickname,headimgurl');
			if ($snsUser) {
				$user2->wx_openid = $user->sns->wx->openid;
				$user2->nickname = $snsUser->nickname;
				$user2->headimgurl = $snsUser->headimgurl;
			}
		}
		if (isset($user->sns->yx)) {
			$snsUser = $this->model('sns\yx\fan')->byOpenid($site, $user->sns->yx->openid, 'nickname,headimgurl');
			if ($snsUser) {
				$user2->yx_openid = $user->sns->yx->openid;
				$user2->nickname = $snsUser->nickname;
				$user2->headimgurl = $snsUser->headimgurl;
			}
		}
		if (isset($user->sns->qy)) {
			$snsUser = $this->model('sns\qy\fan')->byOpenid($site, $user->sns->qy->openid, 'nickname,headimgurl');
			if ($snsUser) {
				$user2->qy_openid = $user->sns->qy->openid;
				$user2->nickname = $snsUser->nickname;
				$user2->headimgurl = $snsUser->headimgurl;
			}
		}
		$user2->userid = $user->uid;

		//加入信息墙
		$reply = $this->model('matter\wall')->join($site, $app, $user2, 'click');
		if (false === $reply[0]) {
			return new \ResponseError($reply[1]);
		}
		/*发送消息通知*/
		$message = array(
			"msgtype" => "text",
			"text" => array(
				"content" => $reply[1],
			),
		);
		if (isset($user2->yx_openid)) {
			$yxConfig = $this->model('sns\yx')->bySite($site);
			if ($yxConfig && $yxConfig->joined === 'Y') {
				$yxProxy = $this->model('sns\yx\proxy', $yxConfig);
				if ($yxConfig->can_p2p === 'Y') {
					$rst = $yxProxy->messageSend($message, array($user2->yx_openid));
				} else {
					$rst = $yxProxy->messageCustomSend($message, $user2->yx_openid);
				}
			}
		}
		if (isset($user2->wx_openid)) {
			$wxConfig = $this->model('sns\wx')->bySite($site);
			if ($wxConfig && $wxConfig->joined === 'Y') {
				$wxProxy = $this->model('sns\wx\proxy', $wxConfig);
				$rst = $wxProxy->messageCustomSend($message, $user2->wx_openid);
			}
		}
		if (isset($user2->qy_openid)) {
			$qyConfig = $this->model('sns\qy')->bySite($site);
			if ($qyConfig && $qyConfig->joined === 'Y') {
				$qyProxy = $this->model('sns\qy\proxy', $qyConfig);
				$message['touser'] = $user2->qy_openid;
				$rst = $qyProxy->messageSend($message, $user2->qy_openid);
			}
		}

		return new \ResponseData($reply[1]);
	}
	/**
	 * 用户退出信息墙
	 */
	public function quit_action($site, $app) {
		$user = $this->who;
		if (isset($user->sns->wx)) {
			$openid = $user->sns->wx->openid;
			$where = " and wx_openid = '{$openid}'";
		} elseif (isset($user->sns->yx)) {
			$openid = $user->sns->yx->openid;
			$where = " and yx_openid = '{$openid}'";
		} elseif (isset($user->sns->qy)) {
			$openid = $user->sns->qy->openid;
			$where = " and qy_openid = '{$openid}'";
		}

		$this->model()->update(
			'xxt_wall_enroll',
			array('close_at' => time()),
			"wid = '{$app}' " . $where
		);

		$wall = $this->model('matter\wall')->byId($app, 'quit_reply');
		$reply = empty($wall->quit_reply) ? '您已退出信息墙' : $wall->quit_reply;
		return new \ResponseData($reply);
	}
	/**
	 *详细页信息
	 */
	public function get_action($site, $app) {
		$user = $this->who;
		$p = array(
			'id,title,summary,active',
			'xxt_wall',
			"id = '{$app}'",
		);
		$wall = $this->model()->query_obj_ss($p);
		if ($wall) {
			if ($wall->active === 'N') {
				$this->outputError('信息墙已停用');
				exit;
			}
		}

		$data = array();
		if (isset($user->sns)) {
			$q = array(
				'join_at,close_at',
				'xxt_wall_enroll',
			);
			if (isset($user->sns->wx)) {
				$openid = $user->sns->wx->openid;
				$q[2] = "wid = '{$app}' and wx_openid = '{$openid}'";
			} elseif (isset($user->sns->yx)) {
				$openid = $user->sns->yx->openid;
				$q[2] = "wid = '{$app}' and yx_openid = '{$openid}'";
			} elseif (isset($user->sns->qy)) {
				$openid = $user->sns->qy->openid;
				$q[2] = "wid = '{$app}' and qy_openid = '{$openid}'";
			}
			$wallUser = $this->model()->query_obj_ss($q);
			if ($wallUser) {
				if ($wallUser->close_at === '0') {
					$wallUser->state = 'Y';
				} else {
					$wallUser->state = 'N';
				}
			} else {
				$wallUser = new \stdClass;
				$wallUser->state = 'N';
			}

			$data['wallUser'] = $wallUser;
		}

		$data['data'] = $wall;
		$data['user'] = $user;
		return new \ResponseData($data);

	}
	/**
	 * 当前用户参与的信息强
	 */
	public function byUser_action($site) {
		$user = $this->who;

		$p = [
			'w.id,w.title,e.join_at,e.close_at,w.active',
			'xxt_wall_enroll e,xxt_wall w',
		];
		if (isset($user->sns->wx)) {
			$openid = $user->sns->wx->openid;
			$p[2] = "e.siteid = '{$site}' and e.wx_openid = '{$openid}' and e.wid = w.id";
		} elseif (isset($user->sns->yx)) {
			$openid = $user->sns->yx->openid;
			$p[2] = "e.siteid = '{$site}' and e.yx_openid = '{$openid}' and e.wid = w.id";
		} elseif (isset($user->sns->qy)) {
			$openid = $user->sns->qy->openid;
			$p[2] = "e.siteid = '{$site}' and e.qy_openid = '{$openid}' and e.wid = w.id";
		} else {
			return new \ResponseData(false);
		}

		$walls = $this->model()->query_objs_ss($p);

		return new \ResponseData($walls);
	}
	/**
	 * 检查是否需要第三方社交帐号认证
	 * 检查条件：
	 * 1、站点是否绑定了第三方社交帐号认证
	 * 2、平台是否绑定了第三方社交帐号认证
	 * 3、用户客户端是否可以发起认证
	 *
	 * @param string $site
	 */
	private function _requireSnsOAuth($siteid) {
		if ($this->userAgent() === 'wx') {
			if (!isset($this->who->sns->wx)) {
				$modelWx = $this->model('sns\wx');
				if (($wxConfig = $modelWx->bySite($siteid)) && $wxConfig->joined === 'Y') {
					$this->snsOAuth($wxConfig, 'wx');
				} else if (($wxConfig = $modelWx->bySite('platform')) && $wxConfig->joined === 'Y') {
					$this->snsOAuth($wxConfig, 'wx');
				}
			}
			if (!isset($this->who->sns->qy)) {
				if ($qyConfig = $this->model('sns\qy')->bySite($siteid)) {
					if ($qyConfig->joined === 'Y') {
						$this->snsOAuth($qyConfig, 'qy');
					}
				}
			}
		} elseif ($this->userAgent() === 'yx') {
			if (!isset($this->who->sns->yx)) {
				if ($yxConfig = $this->model('sns\yx')->bySite($siteid)) {
					if ($yxConfig->joined === 'Y') {
						$this->snsOAuth($yxConfig, 'yx');
					}
				}
			}
		}

		return false;
	}
}