<?php
namespace site\sns;

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/lib/wxqy/WXBizMsgCrypt.php';
require_once dirname(__FILE__) . '/usercall.php';
require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';

class wx extends \member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white'; //'black'黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'][] = 'api';
		$rule_action['actions'][] = 'hello';
		$rule_action['actions'][] = 'timer';

		return $rule_action;
	}
	/**
	 * 接收来源于微信公众平台的请求
	 */
	public function api_action($site) {

		$method = $_SERVER['REQUEST_METHOD'];

		switch ($method) {
		case 'GET':
			/* 公众平台对接 */
			$wxConfig = $this->model('sns\wx')->bySite($site);
			$wxProxy = $this->model('sns\wx\proxy', $wxConfig);
			$rst = $wxProxy->join($_GET);
			header('Content-Type: text/html; charset=utf-8');
			die($rst[1]);
			break;
		case 'POST':
			/* 公众平台事件 */
			$data = file_get_contents("php://input");
			$call = new UserCall($data, $site, 'wx');
			$this->handle($site, $call);
			break;
		}
	}
	/**
	 * 执行定时任务
	 */
	public function timer_action() {
		/**
		 * 查找匹配的定时任务
		 */
		$tasks = $this->model('mp\timer')->tasksByTime();
		/**
		 * 记录日志
		 */
		foreach ($tasks as $task) {
			$rsp = $task->exec();
			$log = array(
				'mpid' => $task->mpid,
				'task_id' => $task->id,
				'occur_at' => time(),
				'result' => json_encode($rsp),
			);
			$this->model()->insert('xxt_log_timer', $log, true);
		}

		return new \ResponseData(count($tasks));
	}
	/**
	 * 处理收到的消息
	 *
	 * 当普通易信用户向公众帐号发消息时，易信服务器将POST该消息到填写的URL上。
	 * XML编码格式为UTF-8
	 */
	private function handle($site, $call) {
		/**
		 * 记录消息日志
		 */
		$msg = $call->to_array();
		$msg['siteid'] = $site;
		$this->model('log')->receive($msg);
		/**
		 * 消息分流处理
		 * 【信息墙】需要从现有信息处理流程中形成分支，分支中进行处理就可以了。
		 * 如果分支进行了处理，可以通过返回值告知是否还需要进行处理
		 */
		if ($this->fork($msg)) {
			/**
			 * 分支活动负责处理
			 */
			die('');
		} else {
			/**
			 * 处理消息
			 */
			switch ($msg['type']) {
			case 'text':
				$this->_textCall($msg);
				break;
			case 'voice':
				$this->_voiceCall($msg);
				break;
			case 'event':
				$this->_eventCall($msg);
				break;
			case 'location':
				if ($reply = $this->model('sns\event')->otherCall($site, 'location')) {
					$r = $this->model('sns\reply\\' . $reply->matter_type, $msg, $reply->matter_id);
					$r->exec();
				}
			}
			die('');
		}
	}
	/**
	 * 消息分流处理
	 */
	private function fork($msg) {
		if ($fa = $this->currentForkActivity($msg)) {
			/**
			 * 由分支活动负责处理消息
			 */
			$reply = $fa[1]->handle($fa[0], $msg, $this);
			if (is_string($reply)) {
				/**
				 * 返回分支活动的回复
				 */
				$tr = $this->model('sns\reply\text', $msg, $reply, false);
				$tr->exec();
			} else {
				/**
				 * 只允许在一个活动中进行处理
				 */
				return $reply;
			}
		} else {
			/**
			 * 没有进行处理
			 */
			return false;
		}
	}
	/**
	 * 获得有效的分支活动
	 *
	 * 目前只有信息墙一种活动
	 * 判断当前用户是否已经加入了活动，且仍然处于活动状态
	 *
	 * return array 0：活动的ID，1：活动实例
	 *
	 */
	private function currentForkActivity($msg) {
		$siteId = $msg['siteid'];
		$openid = $msg['from_user'];
		$wall = $this->model('app\wall');

		if ($wid = $wall->joined($siteId, $openid)) {
			return array($wid, $wall);
		} else {
			return false;
		}
	}
	/**
	 * 事件消息处理
	 */
	private function _eventCall($data) {
		$e = json_decode($data['data']);
		if (is_array($e)) {
			$t = $e[0];
			$k = isset($e[1]) ? $e[1] : null;
		} else {
			$t = $e->Event;
			$k = null;
		}
		switch ($t) {
		case 'subscribe':
			$this->_subscribeCall($data, $k);
			break;
		case 'unsubscribe':
			$this->_unsubscribeCall($data);
			break;
		case 'MASSSENDJOBFINISH':
			$this->_massmsgCall($data);
			break;
		case 'TEMPLATESENDJOBFINISH':
			$this->_templateCall($data, $k, $e[2]);
			break;
		case 'card_pass_check':
		case 'card_not_pass_check':
		case 'user_get_card':
		case 'user_del_card':
			$this->_cardCall($data);
			break;
		case 'scan':
		case 'SCAN':
			$this->_qrcodeCall($data, 'scan', $k);
			break;
		case 'click':
		case 'CLICK':
			$this->_menuCall($data, $k);
			break;
		}
		die('');
	}
	/**
	 * 新关注用户
	 *
	 * $call
	 * $k 场景二维码的scene_id
	 */
	private function _subscribeCall($call, $scene_id = null) {
		/**
		 * 记录粉丝关注信息
		 */
		$current = time();
		$siteId = $call['siteid'];
		$openid = $call['from_user'];
		$wxConfig = $this->model('sns\wx')->bySite($siteId);
		$modelFan = $this->model('sns\wx\fan');
		if ($fan = $modelFan->byOpenid($siteId, $openid, '*')) {
			/**
			 * 粉丝重新关注
			 */
			$modelFan->update(
				'xxt_site_wxfan',
				array(
					'subscribe_at' => $current,
					'unsubscribe_at' => 0,
					'sync_at' => $current,
				),
				"siteid='$siteId' and openid='$openid'"
			);
		} else {
			/**
			 * 新粉丝关注
			 */
			/* 创建站点用户 */
			$siteUser = $this->model('site\user\account')->blank($siteId, true, array('ufrom' => 'wx'));
			$fan = $modelFan->blank($siteId, $openid, true, array(
				'userid' => $siteUser->uid,
				'subscribe_at' => $current,
				'sync_at' => $current)
			);
			// log
			$this->model('log')->writeSubscribe($siteId, $openid);
		}
		if ($wxConfig->can_fans === 'Y') {
			/**
			 * 获取粉丝信息并更新
			 * todo 是否应该更新用户所属的分组？
			 */
			$wxProxy = $this->model('sns\wx\proxy', $wxConfig);
			$fanInfo = $wxProxy->userInfo($openid, false);
			if ($fanInfo[0]) {
				/*更新粉丝用户信息*/
				$nickname = trim($this->model()->escape($fanInfo[1]->nickname));
				$u = array(
					'nickname' => empty($nickname) ? '未知' : $nickname,
					'sex' => $fanInfo[1]->sex,
					'city' => $fanInfo[1]->city,
				);
				isset($fanInfo[1]->headimgurl) && $u['headimgurl'] = $fanInfo[1]->headimgurl;
				isset($fanInfo[1]->province) && $u['province'] = $fanInfo[1]->province;
				isset($fanInfo[1]->country) && $u['country'] = $fanInfo[1]->country;
				$this->model()->update('xxt_site_wxfan', $u, "siteid='$siteId' and openid='$openid'");
				/*更新站点用户信息 @todo 总是要更新吗？*/
				$this->model()->update(
					'xxt_site_account',
					array('nickname' => $u['nickname'], 'headimgurl' => $u['headimgurl']),
					"uid='$fan->userid'"
				);
			}
		}
		if (!empty($scene_id)) {
			/**
			 * 通过扫描场景二维码关注
			 * 将关注事件转换为场景二维码事件
			 */
			$scene_id = substr($scene_id, strlen('qrscene_'));
			$scandata = $call;
			$scandata['data'] = json_encode(array('scan', $scene_id));
			if ($reply = $this->_qrcodeCall($scandata)) {
				is_object($reply) && $reply->exec();
			}
		}
		if ($reply = $this->model('sns\wx\event')->otherCall($siteId, 'subscribe')) {
			$r = $this->model('sns\reply\\' . $reply->matter_type, $call, $reply->matter_id);
			$r->exec();
		}
	}
	/**
	 * 取消关注
	 */
	private function _unsubscribeCall($call) {
		$siteId = $call['siteid'];
		$openid = $call['from_user'];
		$unsubscribeAt = time();
		$rst = $this->model()->update(
			'xxt_site_wxfan',
			array('unsubscribe_at' => $unsubscribeAt),
			"siteid='$siteId' and openid='$openid'"
		);

		return $rst;
	}
	/**
	 * 群发消息处理结果（仅限微信）
	 */
	private function _massmsgCall($call) {
		$siteId = $call['siteid'];

		$data = json_decode($call['data']);
		$msgid = $data->MsgID;
		/**
		 * 更新数据状态
		 */
		$rst = $this->model()->update(
			'xxt_log_massmsg',
			array(
				'status' => $data->Status,
				'total_count' => $data->TotalCount,
				'filter_count' => $data->FilterCount,
				'sent_count' => $data->SentCount,
				'error_count' => $data->ErrorCount,
			),
			"siteid='$siteId' and msgid='$msgid'"
		);

		return $rst;
	}
	/**
	 * 模板消息处理结果
	 *
	 * 仅限微信
	 */
	private function _templateCall($call, $msgid, $status) {
		$siteId = $call['siteid'];
		$openid = $call['from_user'];
		/**
		 * 更新数据状态
		 */
		$rst = $this->model()->update(
			'xxt_log_tmplmsg',
			array('status' => $status),
			"siteid='$siteId' and openid='$openid' and msgid='$msgid'"
		);
		/**
		 * 处理事件响应，选择消息转发事件，通知模板消息处理结果
		 */
		if ($reply = $this->model('sns\wx\event')->otherCall($siteId, 'templatemsg')) {
			$r = $this->model('sns\reply\\' . $reply->matter_type, $call, $reply->matter_id);
			$r->exec();
		}
	}
	/**
	 * 卡卷事件
	 */
	private function _cardCall($call) {
		$siteId = $call['siteid'];
		if ($reply = $this->model('sns\wx\event')->otherCall($siteId, 'cardevent')) {
			$r = $this->model('sns\reply\\' . $reply->matter_type, $data, $reply->matter_id);
			$r->exec();
		}
	}
	/**
	 * 文本消息响应
	 * 如果没有定义如何响应，就调用缺省的响应内容
	 */
	private function _textCall($call) {
		$siteId = $call['siteid'];
		$text = $call['data'];
		if ($reply = $this->model('sns\wx\event')->textCall($siteId, $text)) {
			$r = $this->model('sns\reply\\' . $reply->matter_type, $call, $reply->matter_id, $reply->keyword);
			$r->exec();
		} else {
			$this->_universalCall($call);
		}
	}
	/**
	 * 语音消息响应
	 */
	private function _voiceCall($call) {
		$data = $call['data'];
		if (!empty($data[2])) {
			$this->model('sns\reply\text', $call, $data[2], false);
		} else {
			$this->model('sns\reply\text', $call, '未开通语音识别接口', false);
		}
		$tr->exec();
	}
	/**
	 * menu call
	 */
	private function _menuCall($call, $k) {
		$siteId = $call['siteid'];
		if ($reply = $this->model('sns\wx\event')->menuCall($siteId, $k)) {
			if (!empty($reply->matter_type)) {
				$r = $this->model('sns\reply\\' . $reply->matter_type, $call, $reply->matter_id);
				$r->exec();
			}
		} else {
			$this->_universalCall($call);
		}
	}
	/**
	 * 缺省回复
	 */
	private function _universalCall($call) {
		$siteId = $call['siteid'];
		if ($reply = $this->model('sns\wx\event')->otherCall($siteId, 'universal')) {
			$r = $this->model('sns\reply\\' . $reply->matter_type, $call, $reply->matter_id);
			$r->exec();
		}
	}
	/**
	 * 扫描二维码事件
	 *
	 * 企业号目前不支持场景二维码
	 * 由于目前易信的场景二维码客户端无法收到回复信息，因此改为推动客户消息替代
	 */
	private function _qrcodeCall($call) {
		$siteId = $call['siteid'];
		$data = json_decode($call['data']);
		if ($reply = $this->model('sns\wx\event')->qrcodeCall($siteId, $data[1])) {
			if ($reply->expire_at > 0) {
				/* 一次性二维码，用完后就删除 */
				$this->model()->delete('xxt_call_qrcode_wx', "id=$reply->id");
			}
			$r = $this->model('sns\reply\\' . $reply->matter_type, $call, $reply->matter_id);
			$r->exec();
		}
	}
}