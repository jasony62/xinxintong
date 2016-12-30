<?php
namespace site\sns;

require_once TMS_APP_DIR . '/lib/wxqy/WXBizMsgCrypt.php';
require_once dirname(__FILE__) . '/usercall.php';
require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 接收微信企业号消息
 */
class qy extends \member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white'; //'black'黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'][] = 'api';
		$rule_action['actions'][] = 'hello';
		$rule_action['actions'][] = 'timer';

		return $rule_action;
	}
	/**
	 * 接收来源于公众平台的请求
	 *
	 */
	public function api_action($site) {
		$qyConfig = $this->model('sns\qy')->bySite($site);
		$qyProxy = $this->model('sns\qy\proxy', $qyConfig);

		$method = $_SERVER['REQUEST_METHOD'];
		switch ($method) {
		case 'GET':
			/* 公众平台对接 */
			$rst = $qyProxy->join($_GET);
			$this->model('log')->log($site, 'join', json_encode($rst));
			header('Content-Type: text/html; charset=utf-8');
			die($rst[1]);
		case 'POST':
			$data = file_get_contents("php://input");
			/* 企业号需要对数据进行解密处理 */
			$rst = $qyProxy->DecryptMsg($_GET, $data);
			if ($rst[0] === false) {
				exit;
			}
			$data = $rst[1];
			$call = new UserCall($data, $site, 'qy');
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
	private function handle($siteid, $call) {
		/**
		 * 记录消息日志
		 */
		$msg = $call->to_array();
		$msg['siteid'] = $siteid;
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
				$this->text_call($msg);
				break;
			case 'voice':
				$this->voice_call($msg);
				break;
			case 'event':
				$this->event_call($msg);
				break;
			case 'location':
				if ($reply = $this->model('sns\qy\event')->otherCall($siteid, 'location')) {
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
		$fromSrc = $msg['src'];
		$wall = $this->model('matter\wall');

		if ($wid = $wall->joined($siteId, $openid, $fromSrc)) {
			return array($wid, $wall);
		} else {
			return false;
		}
	}
	/**
	 * 事件消息处理
	 */
	private function event_call($data) {
		//$this->model('log')->log($data['siteid'], 'event', json_encode($data));
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
			$this->subscribe_call($data, $k);
			break;
		case 'unsubscribe':
			$this->unsubscribe_call($data);
			break;
		case 'MASSSENDJOBFINISH':
			$this->massmsg_call($data);
			break;
		case 'TEMPLATESENDJOBFINISH':
			$this->template_call($data, $k, $e[2]);
			break;
		case 'card_pass_check':
		case 'card_not_pass_check':
		case 'user_get_card':
		case 'user_del_card':
			$this->card_call($data);
			break;
		case 'scan':
		case 'SCAN':
			$this->qrcode_call($data, 'scan', $k);
			break;
		case 'click':
		case 'CLICK':
			$this->menu_call($data, $k);
			break;
		}
		die('');
	}
	/**
	 * 新关注用户
	 *
	 * $data
	 * $k 场景二维码的scene_id
	 */
	private function subscribe_call($data, $scene_id = null) {
		/**
		 * 记录粉丝关注信息
		 */
		$current = time();
		$siteid = $data['siteid'];
		$openid = $data['from_user'];
		$modelFan = $this->model('sns\qy\fan');
		if ($fan = $modelFan->byOpenid($siteid, $openid, '*')) {
			/**
			 * 粉丝重新关注
			 */
			$modelFan->update(
				'xxt_site_qyfan',
				array(
					'subscribe_at' => $current,
					'unsubscribe_at' => 0,
					'sync_at' => $current,
				),
				"siteid='$siteid' and openid='$openid'"
			);
		} else {
			/**
			 * 新粉丝关注
			 */
			$fan = $modelFan->blank($siteid, $openid, true, [
				'subscribe_at' => $current,
				'sync_at' => $current]
			);
			// log
			$this->model('log')->writeSubscribe($siteid, $openid);
		}

		if ($reply = $this->model('sns\qy\event')->otherCall($siteid, 'subscribe')) {
			/**
			 * subscribe reply.
			 */
			$r = $this->model('sns\reply\\' . $reply->matter_type, $data, $reply->matter_id);
			$r->exec();
		}
	}
	/**
	 * 取消关注
	 */
	private function unsubscribe_call($data) {
		$siteid = $data['siteid'];
		$openid = $data['from_user'];
		$unsubscribe_at = time();
		$rst = $this->model()->update(
			'xxt_site_qyfan',
			array('unsubscribe_at' => $unsubscribe_at),
			"siteid='$siteid' and openid='$openid'"
		);

		return $rst;
	}

	/**
	 * 卡卷事件
	 */
	private function card_call($data) {
		$siteId = $data['siteid'];
		/**
		 * 处理事件响应，消息转发事件
		 */
		if ($reply = $this->model('sns\qy\event')->otherCall($siteId, 'cardevent')) {
			$r = $this->model('sns\reply\\' . $reply->matter_type, $data, $reply->matter_id);
			$r->exec();
		}
	}
	/**
	 * 文本消息响应
	 * 如果没有定义如何响应，就调用缺省的响应内容
	 */
	private function text_call($call) {
		$siteId = $call['siteid'];
		$text = $call['data'];
		if ($reply = $this->model('sns\qy\event')->textCall($siteId, $text)) {
			$r = $this->model('sns\reply\\' . $reply->matter_type, $call, $reply->matter_id, $reply->keyword);
			$r->exec();
		} else {
			$this->_universalCall($call);
		}
	}
	/**
	 * 语音消息响应
	 */
	private function voice_call($call) {
		$siteId = $call['siteid'];
		$data = $call['data'];
		if (!empty($data[2])) {
			$this->model('sns\reply\text', $call, $data[2], false);
			$tr->exec();
		} else {
			$this->model('sns\reply\text', $call, '未开通语音识别接口', false);
			$tr->exec();
		}
	}
	/**
	 * menu call
	 */
	private function menu_call($call, $k) {
		$siteId = $call['siteid'];

		if ($reply = $this->model('sns\qy\event')->menuCall($siteId, $k)) {
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
	private function _universalcall($data) {
		$siteId = $_GET['site'];
		if ($reply = $this->model('sns\qy\event')->otherCall($siteId, 'universal')) {
			$r = $this->model('sns\reply\\' . $reply->matter_type, $data, $reply->matter_id);
			$r->exec();
		}
	}

}