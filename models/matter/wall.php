<?php
namespace matter;

require_once dirname(__FILE__) . '/enroll_base.php';
/**
 *
 */
class wall_model extends enroll_base {
	/**
	 * 审核状态
	 */
	const APPROVE_PENDING = 0;
	const APPROVE_PASS = 1;
	const APPROVE_REJECT = 2;
	/**
	 * 记录日志时需要的列
	 */
	const LOG_FIELDS = 'siteid,id,title,summary,pic,mission_id';
	/**
	 *
	 */
	protected function table() {
		return 'xxt_wall';
	}
	/**
	 *
	 */
	public function &byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_wall',
			['id' => $id],
		];
		if ($oWall = $this->query_obj_ss($q)) {
			$oWall->type = 'wall';
			if ($fields === '*' || false !== strpos($fields, 'data_schemas')) {
				if (!empty($oWall->data_schemas)) {
					$oWall->dataSchemas = json_decode($oWall->data_schemas);
				} else {
					$oWall->dataSchemas = [];
				}
			}
			if (!empty($oWall->matter_mg_tag)) {
				$oWall->matter_mg_tag = json_decode($oWall->matter_mg_tag);
			}
		}

		return $oWall;
	}
	/**
	 *
	 */
	public function messageById($wid, $mid, $fields = '*') {
		$q = array(
			$fields,
			'xxt_wall_log',
			"id=$mid and wid='$wid'",
		);
		$msg = $this->query_obj_ss($q);

		return $msg;
	}
	/**
	 * 用户加入信息墙
	 *
	 * $runningSiteId 用户所在的公众号，不一定是是信息墙所属的公众号
	 * $wid
	 * $user 用户信息
	 * $remark 加入信息墙时输入的事件数据
	 */
	public function join($runningSiteId, $wid, $user, $remark = '') {
		/**
		 *检查信息墙状态
		 */
		$wall = $this->byId($wid, ['fields' => 'title,join_reply,active,start_at,end_at']);
		if ($wall->active === 'N') {
			$reply = [false, '【' . $wall->title . '】已停用'];
			return $reply;
		}
		$current = time();
		if ($wall->start_at != 0 && $current < $wall->start_at) {
			$reply = [false, '【' . $wall->title . '】没有开始'];
			return $reply;
		} else if ($wall->end_at != 0 && $current > $wall->end_at) {
			$reply = [false, '【' . $wall->title . '】已经结束'];
			return $reply;
		}

		/**
		 * 加入一个信息墙需要从其他的墙退出
		 */
		if (isset($user->wx_openid)) {
			$where = " and wx_openid='{$user->wx_openid}'";
		} else if (isset($user->yx_openid)) {
			$where = " and yx_openid='{$user->yx_openid}'";
		} else if (isset($user->qy_openid)) {
			$where = " and qy_openid='{$user->qy_openid}'";
		} else {
			return [false, '不能获得公众号身份信息，无法加入信息墙'];
		}
		$this->update(
			'xxt_wall_enroll',
			['close_at' => time()],
			"siteid='$runningSiteId'" . $where
		);
		/**
		 * 加入一个组
		 */
		$q = [
			'count(*)',
			'xxt_wall_enroll',
			"siteid='$runningSiteId' and wid='$wid' " . $where,
		];
		if (1 === (int) $this->query_val_ss($q)) {
			/**
			 * 之前已经加入个这个组，重置状态
			 */
			$this->update(
				'xxt_wall_enroll',
				['close_at' => 0],
				$q[2]
			);
		} else {
			$i['siteid'] = $runningSiteId;
			$i['wid'] = $wid;
			$i['remark'] = $remark;
			$i['join_at'] = time();
			$i['nickname'] = isset($user->nickname) ? $user->nickname : '';
			$i['userid'] = isset($user->userid) ? $user->userid : '';
			$i['headimgurl'] = isset($user->headimgurl) ? $user->headimgurl : '';
			if (isset($user->wx_openid)) {
				$i['wx_openid'] = $user->wx_openid;
			}
			if (isset($user->yx_openid)) {
				$i['yx_openid'] = $user->yx_openid;
			}
			if (isset($user->qy_openid)) {
				$i['qy_openid'] = $user->qy_openid;
			}

			$this->insert('xxt_wall_enroll', $i, false);
		}
		/**
		 * 加入提示
		 */
		if (empty($wall->join_reply)) {
			$reply = [true, '欢迎进入，请输入您的发言。'];
		} else {
			$reply = [true, $wall->join_reply];
		}

		return $reply;
	}
	/**
	 * 判断当前用户是否已经参加了信息墙
	 *
	 * 一个用户在一个公众号中只能加入一个信息墙
	 *
	 * 返回当前用户所在的信息墙
	 */
	public function joined($runningSiteId, $openid, $fromSrc) {
		$q = array(
			'wid',
			'xxt_wall_enroll',
			"siteid='$runningSiteId' and close_at=0 and " . $fromSrc . "_openid='$openid'",
		);
		$wid = $this->query_val_ss($q);
		return $wid;
	}
	/**
	 * 判断当前用户是否已经参加了指定信息墙
	 */
	public function joinedWall($runningSiteId, $wid, $openid) {
		$q = array(
			'count(*)',
			'xxt_wall_enroll',
			"siteid='$runningSiteId' and wid='$wid' and (wx_openid='$openid' or yx_openid='$openid' or qy_openid='$openid') and close_at=0",
		);
		return 1 === (int) $this->query_val_ss($q);
	}
	/**
	 * 处理收到的上墙消息
	 *
	 * $wid
	 * $msg
	 * $ctrl 控制器
	 */
	public function handle($wid, $msg, $ctrl = null) {
		if (in_array($msg['type'], array('text', 'image'))) {
			return $this->add($wid, $msg, $ctrl);
		} else {
			return false;
		}
	}
	/**
	 * 获得消息列表
	 *
	 * $contain array [totle]
	 */
	public function messages($runningSiteId, $wid, $page = 1, $size = 30, $contain = null) {
		$q = array(
			'l.*,e.nickname,e.userid',
			'xxt_wall_log l,xxt_wall_enroll e',
			"l.siteid = '$runningSiteId' and l.wid = '$wid' and e.wid = l.wid and (e.wx_openid = l.openid or e.yx_openid = l.openid or e.qy_openid = l.openid)",
		);
		$q2['o'] = 'approve_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;

		if ($w = $this->query_objs_ss($q, $q2)) {
			$result[] = $w;
			if (in_array('total', $contain)) {
				$q[0] = 'count(*)';
				$total = (int) $this->query_val_ss($q);
				$result[] = $total;
			}
			return $result;
		}
		return array();
	}
	/**
	 * 获得所有消息列表
	 * 没有分页，主要用于导出Excel
	 *
	 */
	public function msgList($runningSiteId, $wid) {
		$q = array(
			'l.*,e.nickname,e.userid',
			'xxt_wall_log l,xxt_wall_enroll e',
			"l.siteid = '$runningSiteId' and l.wid = '$wid' and e.wid = l.wid and (e.wx_openid = l.openid or e.yx_openid = l.openid or e.qy_openid = l.openid)",
		);
		$q2['o'] = 'approve_at desc';

		$rst = $this->query_objs_ss($q, $q2);

		return $rst;
	}
	/**
	 * 获得墙内的所有用户
	 */
	public function joinedUsers($runningSiteId, $wid, $fields = 'openid') {
		$q = array(
			'wx_openid,yx_openid,qy_openid',
			'xxt_wall_enroll',
			"siteid='$runningSiteId' and wid='$wid' and close_at=0",
		);

		$users = $this->query_objs_ss($q);

		return $users;
	}
	/**
	 * 获得未处理消息列表
	 *
	 * $time 指定时间点之后的数据
	 */
	public function pendingMessages($runningSiteId, $wid, $time = 0) {
		$q = array(
			'l.*,e.nickname,e.userid',
			'xxt_wall_log l,xxt_wall_enroll e',
			"l.siteid = '$runningSiteId' and l.wid = '$wid' and e.wid = l.wid and (e.wx_openid = l.openid or e.yx_openid = l.openid or e.qy_openid = l.openid) and approved=" . self::APPROVE_PENDING,
		);
		$time > 0 && $q[2] .= " and publish_at>=$time";
		$q2['o'] = 'publish_at desc';
		return $this->query_objs_ss($q, $q2);
	}
	/**
	 * 获得消息列表
	 *
	 * $time 指定时间点之后的数据
	 */
	public function approvedMessages($runningSiteId, $wid, $time = 0) {
		$current = time();

		$q = array(
			'l.*,e.nickname,e.headimgurl',
			'xxt_wall_log l,xxt_wall_enroll e',
			"e.siteid = '{$runningSiteId}' and e.wid= '$wid' and l.wid='$wid' and (e.wx_openid = l.openid or e.yx_openid = l.openid or e.qy_openid = l.openid) and approved=" . self::APPROVE_PASS,
		);
		$time > 0 && $q[2] .= " and approve_at>=$time";
		$q2['o'] = 'approve_at desc';
		$messages = $this->query_objs_ss($q, $q2);
		return array($messages, $current);
	}
	/**
	 * 批准消息
	 *
	 * $runningSiteId
	 * $wid
	 * $mid 消息id
	 */
	public function approve($runningSiteId, $wid, $mid, $ctrl) {
		$u['approve_at'] = time();
		$u['approved'] = self::APPROVE_PASS;
		$this->update('xxt_wall_log', $u, "wid='$wid' and id=$mid");

		return self::APPROVE_PASS;
	}
	/**
	 * 拒绝消息
	 *
	 * $wid 信息墙ID
	 * $mid 消息ID
	 */
	public function reject($wid, $mid) {
		$u['approved'] = self::APPROVE_REJECT;
		$this->update('xxt_wall_log', $u, "wid='$wid' and id=$mid");

		return self::APPROVE_REJECT;
	}
	/**
	 * 退出信息墙
	 */
	public function unjoin($runningSiteId, $wid, $openid) {
		$wall = $this->byId($wid, 'quit_reply');

		$this->update(
			'xxt_wall_enroll',
			array('close_at' => time()),
			"siteid='$runningSiteId' and wid= '$wid' and (wx_openid='$openid' or yx_openid='$openid' or qy_openid='$openid')"
		);
		$reply = empty($wall->quit_reply) ? '您已退出信息墙' : $wall->quit_reply;

		return $reply;
	}
	/**
	 * 加入新消息
	 *
	 * 如果信息墙设置为将信息推送给所有成员，那么新消息要发送给所有成员，除了发送者
	 *
	 * 目前只支持文本和图片
	 *
	 * $wid
	 * $msg
	 * $ctrl 前端控制器
	 */
	private function add($wid, $msg, $ctrl = null) {
		$siteid = $msg['siteid'];
		$openid = $msg['from_user'];

		$wlog = array(); // 信息墙记录
		if ($msg['type'] === 'text') {
			$wlog['data_type'] = 'text';
			$wlog['data'] = $msg['data'];
		} else if ($msg['type'] === 'image') {
			$wlog['data'] = $msg['data'][1]; //image
			$wlog['data_type'] = 'image';
			$wlog['data_media_id'] = $msg['data'][0];
		} else {
			return true;
		}

		$current = time();
		$wlog['wid'] = $wid;
		$wlog['siteid'] = $siteid;
		$wlog['openid'] = $openid;
		$wlog['publish_at'] = $current;
		/**
		 * 若发出的是退出指令，用户退出当前信息墙
		 * 若允许跳过审核，自动审核
		 */
		$wall = $this->byId($wid, 'quit_cmd,skip_approve,push_others,quit_reply,user_url');
		if ($msg['type'] === 'text' && $msg['data'] === $wall->quit_cmd) {
			/**
			 * 退出信息墙
			 */
			$this->update(
				'xxt_wall_enroll',
				array('close_at' => time()),
				"wid= '$wid' and (wx_openid='$openid' or yx_openid='$openid' or qy_openid='$openid')"
			);
			$reply = empty($wall->quit_reply) ? '您已退出信息墙' : $wall->quit_reply;

			return $reply;
		}
		if ($ctrl && 'Y' === $wall->skip_approve) {
			$wlog['approve_at'] = $current;
			$wlog['approved'] = self::APPROVE_PASS;
			if ('Y' === $wall->push_others) {
				$this->push_others($siteid, $openid, $msg, $wall, $wid);
			}

		}
		/**
		 * 记录留言
		 */
		$wlog['data'] = $this->escape($wlog['data']);
		$this->insert('xxt_wall_log', $wlog, false);
		/**
		 * 更新用户状态
		 */
		$this->update("update xxt_wall_enroll set last_msg_at=$current,msg_num=msg_num+1 where siteid='$siteid' and (wx_openid='$openid' or yx_openid='$openid' or qy_openid='$openid') ");

		return true;
	}
	/**
	 * 将消息发送给信息墙中的用户
	 *
	 * $mpid
	 * $openid
	 * $msg
	 * $wall
	 */
	public function push_others($site, $openid, $msg, $wall, $wid) {
		if ($openid !== 'mocker') {
			if (!isset($msg['from_nickname'])) {
				//获取发送者的nickname
				switch ($msg['src']) {
				case 'wx':
					//获取nickname
					$from_nickname = \TMS_APP::M('sns\wx\fan')->byOpenid($site, $openid, 'nickname');
					break;
				case 'yx':
					$from_nickname = \TMS_APP::M('sns\yx\fan')->byOpenid($site, $openid, 'nickname');
					break;
				case 'qy':
					$from_nickname = \TMS_APP::M('sns\qy\fan')->byOpenid($site, $openid, 'nickname');
					break;
				}
				$msg['from_nickname'] = $from_nickname->nickname;
			}
		}

		//查询墙内所有的用户
		$users = $this->joinedUsers($site, $wid);
		$usersQy = array();

		/**
		 * 拼装推送消息
		 */
		switch ($msg['type']) {
		case 'text':
			if (!empty($wall->user_url)) {
				$url = $wall->user_url;
				$url .= strpos($wall->user_url, '?') === false ? '?' : '&';
				$url .= "openid=$openid";
				$txt = "<a href='$url'>" . $msg['from_nickname'] . "</a>";
				$txt .= '：' . $msg['data'];
			} else {
				$txt = $msg['from_nickname'] . '：' . $msg['data'];
			}

			$message = array(
				"msgtype" => "text",
				"text" => array(
					"content" => $txt,
				),
			);
			/**
			 * 通过客服接口发送给墙内所有用户
			 */
			foreach ($users as $user) {
				if (!empty($user->qy_openid)) {
					$usersQy[] = $user;
				}
				if (!empty($user->yx_openid) && $user->yx_openid !== $openid) {
					$this->sendByOpenid($site, $user->yx_openid, $message, 'yx');
				}
				if (!empty($user->wx_openid) && $user->wx_openid !== $openid) {
					$this->sendByOpenid($site, $user->wx_openid, $message, 'wx');
				}
			}
			break;
		case 'image':
			/**
			 * 易信的图片消息不支持MediaId
			 */
			//站点绑定的易信公众号信息
			$yxConfig = \TMS_APP::M('sns\yx')->bySite($site);
			if ($yxConfig && $yxConfig->joined === 'Y') {
				$mpproxy = \TMS_APP::M('sns\yx\proxy', $yxConfig);
				$rst = $mpproxy->mediaUpload($msg['data'][1]);
				if ($rst[0] === false) {
					$this->sendByOpenid($site, $openid, array(
						"msgtype" => "text",
						"text" => array(
							"content" => urlencode($rst[1]),
						)),
						$msg['src']
					);
					return $rst;
				}
				$mediaIdYx = $rst[1];
			}
			$mediaId = $msg['data'][0];
			$message = array(
				"msgtype" => "image",
				"image" => array(
					"media_id" => $mediaId,
				),
			);
			/**
			 * 通过客服接口发送给墙内所有用户
			 */
			foreach ($users as $user) {
				if (!empty($user->qy_openid)) {
					$usersQy[] = $user;
				}
				if (!empty($user->yx_openid)) {
					if (!isset($mediaIdYx)) {
						//站点绑定的易信公众号信息
						$yxConfig = \TMS_APP::M('sns\yx')->bySite($site);
						$mpproxy = \TMS_APP::M('sns\yx\proxy', $yxConfig);
						$rst = $mpproxy->mediaUpload($msg['data'][1]);
						if ($rst[0] === false) {
							$this->sendByOpenid($site, $openid, array(
								"msgtype" => "text",
								"text" => array(
									"content" => urlencode($rst[1]),
								)),
								$msg['src']
							);
							return $rst;
						}
						$mediaIdYx = $rst[1];
					}
					$message = array(
						"msgtype" => "image",
						"image" => array(
							"media_id" => $mediaIdYx,
						),
					);
				}

				if (!empty($user->yx_openid) && $user->yx_openid !== $openid) {
					$this->sendByOpenid($site, $user->yx_openid, $message, 'yx');
				}
				if (!empty($user->wx_openid) && $user->wx_openid !== $openid) {
					$this->sendByOpenid($site, $user->wx_openid, $message, 'wx');
				}
			}

		}
		/**
		 * 如果当前账号是企业号，且指定了参与的用户，那么发送给所有指定的用户；如果指定用户并未加入信息墙，应该提示他加入
		 * 如果当前账号是服务号，那么发送给已经加入信息墙的所有用户
		 */
		if (!empty($usersQy)) {

			$finished = false;
			/**
			 * 企业号，或者开通了点对点消息接口易信公众号支持预先定义好组成员
			 */
			$groupUsers = \TMS_APP::M('acl')->wallUsers($site, $wid);
			if (!empty($groupUsers)) {
				/**
				 * 不推送给发送人
				 */
				$pos = array_search($openid, $groupUsers);
				unset($groupUsers[$pos]);
				/**
				 * 推送给已经加入信息墙的用户
				 */
				$joinedGroupUsers = array();
				$ingroup = $this->joinedUsers($site, $wid);
				foreach ($ingroup as $ig) {
					if ($openid === $ig->openid) {
						continue;
					}

					$userid = $ig->openid;
					$joinedGroupUsers[] = $userid;
					/**
					 * 从所有成员用户中删除已进入信息墙的用户
					 */
					$pos = array_search($userid, $groupUsers);
					unset($groupUsers[$pos]);
				}
				if (!empty($joinedGroupUsers)) {
					$message['touser'] = implode('|', $joinedGroupUsers);
					$this->send2Qyuser($site, $message);
				}
				/**
				 * 推送给未加入信息墙的用户
				 */
				if (!empty($groupUsers)) {
					$message['touser'] = implode('|', $groupUsers);
					if ($msg['type'] === 'text') {
						$joinUrl = 'http://' . APP_HTTP_HOST . "/rest/app/wall?wid=$wid";
						$message['text']['content'] = $txt . "（<a href='$joinUrl'>参与讨论</a>）";
					}
					$this->send2Qyuser($site, $message);
				}
				$finished = true;
			}

			if (!$finished) {
				/**
				 * 通过客服接口发送给墙内所有用户
				 */
				foreach ($usersQy as $user) {
					if ($user->qy_openid === $openid) {
						continue;
					}
					if (!empty($user->qy_openid)) {
						$this->sendByOpenid($site, $user->qy_openid, $message, 'qy');
					}
				}
			}

		}
		return array(true);
	}
	/**
	 *
	 */
	public function getEntryUrl($runningSiteId, $id) {
		$url = "http://" . APP_HTTP_HOST;
		$url .= "/rest/site/fe/matter/wall";
		$url .= "?site=$runningSiteId&app=" . $id;

		return $url;
	}
	/**
	 * 尽最大可能向用户发送消息
	 *
	 * $mpid
	 * $openid
	 * $message
	 */
	private function sendByOpenid($mpid, $openid, $message, $openid_src = null) {
		if (empty($openid_src)) {
			$snsConfig = \TMS_APP::M('mp\mpaccount')->getApis($mpid);
			$snsProxy = \TMS_APP::M('mpproxy/' . $snsConfig->mpsrc, $mpid);
		} else {
			switch ($openid_src) {
			case 'yx':
				$snsConfig = \TMS_APP::M('sns\yx')->bySite($mpid);
				if ($snsConfig && $snsConfig->joined === 'Y') {
					$snsProxy = \TMS_APP::M('sns\yx\proxy', $snsConfig);
					$snsConfig->yx_p2p = $snsConfig->can_p2p;
					$snsConfig->mpsrc = 'yx';
				} else {
					$snsConfig->mpsrc = null;
				}
				break;

			case 'qy':
				$snsConfig = \TMS_APP::M('sns\qy')->bySite($mpid);
				if ($snsConfig && $snsConfig->joined === 'Y') {
					$snsProxy = \TMS_APP::M('sns\qy\proxy', $snsConfig);
					$snsConfig->qy_agentid = $snsConfig->agentid;
					$snsConfig->mpsrc = 'qy';
				} else {
					$snsConfig->mpsrc = null;
				}
				break;

			case 'wx':
				$snsConfig = \TMS_APP::M('sns\wx')->bySite($mpid);
				if ($snsConfig && $snsConfig->joined === 'Y') {
					$snsProxy = \TMS_APP::M('sns\wx\proxy', $snsConfig);
					$snsConfig->mpsrc = 'wx';
				} else {
					$snsConfig->mpsrc = null;
				}
				break;
			}
		}

		$rst = false;
		switch ($snsConfig->mpsrc) {
		case 'yx':
			if ($snsConfig->yx_p2p === 'Y') {
				$rst = $snsProxy->messageSend($message, array($openid));
			} else {
				$rst = $snsProxy->messageCustomSend($message, $openid);
			}
			break;
		case 'wx':
			$rst = $snsProxy->messageCustomSend($message, $openid);
			break;
		case 'qy':
			$message['touser'] = $openid;
			$message['agentid'] = $snsConfig->qy_agentid;
			$rst = $snsProxy->messageSend($message, $openid);
			break;
		}
		return $rst;
	}
	/**
	 * 向企业号用户发送消息
	 *
	 * $mpid
	 * $message
	 */
	private function send2Qyuser($mpid, $message, $encoded = false) {
		$mpproxy = \TMS_APP::M('mpproxy/qy', $mpid);

		$rst = $mpproxy->messageSend($message, $encoded);

		return $rst;
	}
}