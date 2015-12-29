<?php
namespace app;

require_once dirname(dirname(__FILE__)) . '/matter/wall.php';
/**
 * 【信息墙】活动
 *
 *  信息墙活动将用户发送的信息汇总显示
 *
 */
class wall_model extends \matter\wall_model {
	/**
	 * 审核状态
	 */
	const APPROVE_PENDING = 0;
	const APPROVE_PASS = 1;
	const APPROVE_REJECT = 2;
	/**
	 *
	 * $wid string
	 * $cascaded array []
	 */
	public function &byId($id, $fields = '*') {
		$q = array(
			$fields,
			'xxt_wall',
			"id='$id'",
		);
		$w = $this->query_obj_ss($q);

		return $w;
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
	 * $runningMpid 用户所在的公众号，不一定是是信息墙所属的公众号
	 * $wid
	 * $openid
	 * $remark 加入信息墙时输入的事件数据
	 */
	public function join($runningMpid, $wid, $openid, $remark = '') {
		/**
		 * 加入一个信息墙需要从其他的墙退出
		 */
		$this->update(
			'xxt_wall_enroll',
			array('close_at' => time()),
			"mpid='$runningMpid' and openid='$openid'"
		);
		/**
		 * 加入一个组
		 */
		$q = array(
			'count(*)',
			'xxt_wall_enroll',
			"mpid='$runningMpid' and wid='$wid' and openid='$openid'",
		);
		if (1 === (int) $this->query_val_ss($q)) {
			/**
			 * 之前已经加入个这个组，重置状态
			 */
			$this->update(
				'xxt_wall_enroll',
				array('close_at' => 0),
				$q[2]
			);
		} else {
			$i['mpid'] = $runningMpid;
			$i['wid'] = $wid;
			$i['openid'] = $openid;
			$i['remark'] = $remark;
			$i['join_at'] = time();

			$this->insert('xxt_wall_enroll', $i, false);
		}
		/**
		 * 加入提示
		 */
		$wall = $this->byId($wid, 'join_reply');
		if (empty($wall->join_reply)) {
			$reply = '欢迎进入，请输入您的发言。';
		} else {
			$reply = $wall->join_reply;
		}

		return $reply;
	}
	/**
	 * 判断当前用户是否已经参加了讨论组
	 *
	 * 一个用户在一个公众号中只能加入一个信息墙
	 *
	 * 返回当前用户所在的信息墙
	 */
	public function joined($runningMpid, $openid) {
		$q = array(
			'wid',
			'xxt_wall_enroll',
			"mpid='$runningMpid' and openid='$openid' and close_at=0",
		);
		$wid = $this->query_val_ss($q);

		return $wid;
	}
	/**
	 * 判断当前用户是否已经参加了指定讨论组
	 */
	public function joinedWall($runningMpid, $wid, $openid) {
		$q = array(
			'count(*)',
			'xxt_wall_enroll',
			"mpid='$runningMpid' and wid='$wid' and openid='$openid' and close_at=0",
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
	public function messages($runningMpid, $wid, $page = 1, $size = 30, $contain = null) {
		$q = array(
			'l.*,f.nickname',
			'xxt_wall w,xxt_wall_log l,xxt_fans f',
			"w.id=l.wid and l.wid= '$wid' and f.mpid='$runningMpid' and l.openid=f.openid",
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
	 * 获得墙内的所有用户
	 */
	public function joinedUsers($runningMpid, $wid, $fields = 'openid') {
		$q = array(
			$fields,
			'xxt_wall_enroll',
			"mpid='$runningMpid' and wid='$wid' and close_at=0",
		);

		$users = $this->query_objs_ss($q);

		return $users;
	}
	/**
	 * 获得未处理消息列表
	 *
	 * $time 指定时间点之后的数据
	 */
	public function pendingMessages($runningMpid, $wid, $time = 0) {
		$q = array(
			'l.*,f.nickname',
			'xxt_wall w,xxt_wall_log l,xxt_fans f',
		);
		$w = "w.id=l.wid and f.mpid='$runningMpid' and l.openid=f.openid";
		$w .= " and l.wid= '$wid' and approved=" . self::APPROVE_PENDING;
		$time > 0 && $w .= " and publish_at>=$time";
		$q[] = $w;
		$q2['o'] = 'publish_at desc';

		return $this->query_objs_ss($q, $q2);
	}
	/**
	 * 获得消息列表
	 *
	 * $time 指定时间点之后的数据
	 */
	public function approvedMessages($runningMpid, $wid, $time = 0) {
		$current = time();

		$q = array(
			'l.*,f.nickname,f.headimgurl',
			'xxt_wall w,xxt_wall_log l,xxt_fans f',
		);
		$w = "w.id=l.wid and f.mpid='$runningMpid' and l.openid=f.openid";
		$w .= " and l.wid= '$wid' and approved=" . self::APPROVE_PASS;
		$time > 0 && $w .= " and approve_at>=$time";
		$q[] = $w;
		$q2['o'] = 'approve_at desc';

		$messages = $this->query_objs_ss($q, $q2);

		return array($messages, $current);
	}
	/**
	 * 批准消息
	 *
	 * $runningMpid
	 * $wid
	 * $mid 消息id
	 */
	public function approve($runningMpid, $wid, $mid, $ctrl) {
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
	public function unjoin($runningMpid, $wid, $openid) {
		$wall = $this->byId($wid, 'quit_reply');

		$this->update(
			'xxt_wall_enroll',
			array('close_at' => time()),
			"mpid='$runningMpid' and wid= '$wid' and openid='$openid'"
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
		$mpid = $msg['mpid'];
		$openid = $msg['from_user'];

		$wlog = array(); // 讨论组记录
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
		$wlog['mpid'] = $mpid;
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
				"wid= '$wid' and openid='$openid'"
			);
			$reply = empty($wall->quit_reply) ? '您已退出信息墙' : $wall->quit_reply;

			return $reply;
		}
		if ($ctrl && 'Y' === $wall->skip_approve) {
			$wlog['approve_at'] = $current;
			$wlog['approved'] = self::APPROVE_PASS;
			if ('Y' === $wall->push_others) {
				$this->push_others($mpid, $openid, $msg, $wall, $wid, $ctrl);
			}

		}
		/**
		 * 记录留言
		 */
		$this->insert('xxt_wall_log', $wlog, false);
		/**
		 * 更新用户状态
		 */
		$this->update(
			'xxt_wall_enroll',
			array('last_msg_at' => $current),
			"mpid='$mpid' and openid='$openid'"
		);

		return true;
	}
	/**
	 * 将消息发送给讨论组中的用户
	 *
	 * $mpid
	 * $openid
	 * $msg
	 * $wall
	 */
	public function push_others($mpid, $openid, $msg, $wall, $wid, $ctrl) {
		$mpa = \TMS_APP::M('mp\mpaccount')->byId($mpid);
		/**
		 * 获得当前用户的信息
		 */
		$member = \TMS_APP::M('user/fans')->byOpenid($mpid, $openid, 'nickname');
		/**
		 * 拼装推送消息
		 */
		switch ($msg['type']) {
		case 'text':
			if (!empty($wall->user_url)) {
				$url = $wall->user_url;
				$url .= strpos($wall->user_url, '?') === false ? '?' : '&';
				$url .= "openid=$openid";
				$txt = "<a href='$url'>$member->nickname</a>";
				$txt .= '：' . $msg['data'];
			} else {
				$txt = $member->nickname . '：' . $msg['data'];
			}

			$message = array(
				"msgtype" => "text",
				"text" => array(
					"content" => $txt,
				),
			);
			break;
		case 'image':
			if ($mpa->mpsrc === 'yx' && empty($msg['data'][0])) {
				/**
				 * 易信的图片消息不支持MediaId
				 */
				$mpproxy = \TMS_APP::M('mpproxy\yx', $mpid);
				$rst = $mpproxy->mediaUpload($msg['data'][1]);
				if ($rst[0] === false) {
					$ctrl->sendByOpenid($mpid, $openid, array(
						"msgtype" => "text",
						"text" => array(
							"content" => urlencode($rst[1]),
						))
					);
					return $rst;
				}
				$mediaId = $rst[1];
			} else {
				$mediaId = $msg['data'][0];
			}

			$message = array(
				"msgtype" => "image",
				"image" => array(
					"media_id" => $mediaId,
				),
			);
		}
		/**
		 * 如果当前账号是企业号，且指定了参与的用户，那么发送给所有指定的用户；如果指定用户并未加入讨论组，应该提示他加入
		 * 如果当前账号是服务号，那么发送给已经加入讨论组的所有用户
		 */
		$finished = false;
		if ($mpa->mpsrc === 'qy') {
			/**
			 * 企业号，或者开通了点对点消息接口易信公众号支持预先定义好组成员
			 */
			$groupUsers = \TMS_APP::M('acl')->wallUsers($mpid, $wid);
			if (!empty($groupUsers)) {
				/**
				 * 不推送给发送人
				 */
				$pos = array_search($openid, $groupUsers);
				unset($groupUsers[$pos]);
				/**
				 * 推送给已经加入讨论组的用户
				 */
				$joinedGroupUsers = array();
				$ingroup = $this->joinedUsers($mpid, $wid);
				foreach ($ingroup as $ig) {
					if ($openid === $ig->openid) {
						continue;
					}

					$userid = $ig->openid;
					$joinedGroupUsers[] = $userid;
					/**
					 * 从所有成员用户中删除已进入讨论组的用户
					 */
					$pos = array_search($userid, $groupUsers);
					unset($groupUsers[$pos]);
				}
				if (!empty($joinedGroupUsers)) {
					$message['touser'] = implode('|', $joinedGroupUsers);
					$ctrl->send2Qyuser($mpid, $message);
				}
				/**
				 * 推送给未加入讨论组的用户
				 */
				if (!empty($groupUsers)) {
					$message['touser'] = implode('|', $groupUsers);
					if ($msg['type'] === 'text') {
						$joinUrl = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/wall?wid=$wid";
						$message['text']['content'] = $txt . "（<a href='$joinUrl'>参与讨论</a>）";
					}
					$ctrl->send2Qyuser($mpid, $message);
				}
				$finished = true;
			}
		}
		if (!$finished) {
			/**
			 * 通过客服接口发送给墙内所有用户
			 */
			$users = $this->joinedUsers($mpid, $wid);
			foreach ($users as $user) {
				if ($openid === $user->openid) {
					continue;
				}

				$ctrl->sendByOpenid($mpid, $user->openid, $message);
			}
		}

		return array(true);
	}
}