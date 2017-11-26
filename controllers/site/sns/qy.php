<?php
namespace site\sns;

require_once TMS_APP_DIR . '/lib/wxqy/WXBizMsgCrypt.php';
require_once dirname(__FILE__) . '/usercall.php';
require_once dirname(dirname(dirname(__FILE__))) . '/xxt_base.php';
/**
 * 接收微信企业号消息
 */
class qy extends \xxt_base {

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
		$modelLog = $this->model('log');
		$qyConfig = $this->model('sns\qy')->bySite($site);
		$qyProxy = $this->model('sns\qy\proxy', $qyConfig);

		$method = $_SERVER['REQUEST_METHOD'];
		switch ($method) {
		case 'GET':
			$modelLog->log($site, 'qy-join-0', 'step-0');
			/* 公众平台对接 */
			$rst = $qyProxy->join($_GET);
			header('Content-Type: text/html; charset=utf-8');
			if (false === $rst[0]) {
				$modelLog->log($site, 'qy-join-9', $rst[1]);
			}
			die($rst[1]);
		case 'POST':
			$data = file_get_contents("php://input");
			/* 企业号需要对数据进行解密处理 */
			$rst = $qyProxy->DecryptMsg($_GET, $data);
			if ($rst[0] === false) {
				$modelLog->log($site, 'qy-post-err', json_encode($rst));
				exit;
			}
			$data = $rst[1];
			$call = new UserCall($data, $site, 'qy');
			$this->handle($site, $call);
			break;
		}
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
		$modelLog = $this->model('log');
		$msg = $call->to_array();
		$msg['siteid'] = $siteid;
		/**
		 * 消息已经收到，不处理
		 */

		if (!empty($msg['msgid']) && $modelLog->hasReceived($msg)) {
			die('');
		}

		$modelLog->receive($msg);
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
		$qyConfig = $this->model('sns\qy')->bySite($siteid);
		$qyproxy = $this->model('sns\qy\proxy', $qyConfig);
		$modelFan = $this->model('sns\qy\fan');
		if ($fan = $modelFan->byOpenid($siteid, $openid, '*')) {
			/**
			 * 粉丝重新关注
			 */
			$result = $qyproxy->userGet($openid);
			if ($result[0] === false) {
				$tr = $this->model('sns\reply\text', $data, $result[1], false);
				$tr->exec();
			}
			$user = $result[1];
			$q = [
				'id',
				'xxt_site_member_schema',
				"siteid='$siteid' and valid='Y'",
			];
			$schema = $this->model()->query_vals_ss($q);
			$rst = $this->updateQyFan2($siteid, $fan, $user, $schema);
			if (is_string($rst)) {
				$tr = $this->model('sns\reply\text', $data, $rst, false);
				$tr->exec();
			}
		} else {
			/**
			 * 新粉丝关注
			 */
			$result = $qyproxy->userGet($openid);
			if ($result[0] === false) {
				$tr = $this->model('sns\reply\text', $data, $result[1], false);
				$tr->exec();
			}
			$user = $result[1];
			$q = [
				'id',
				'xxt_site_member_schema',
				"siteid='$siteid' and valid='Y'",
			];
			$schema = $this->model()->query_vals_ss($q);
			$rst = $this->createQyFan2($siteid, $user, $schema);
			if (is_string($rst)) {
				$tr = $this->model('sns\reply\text', $data, $rst, false);
				$tr->exec();
			}
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
	/**
	 * 创建一个企业号的粉丝用户
	 * 同步的创建会员用户
	 *
	 * $user 企业号用户的详细信息
	 * 因为企业号关注事件需要调用此方法，但此方法是在site/fe/base中。目前处理企业号关注信息类，并不继承
	 * sit/fe/base，所以为了不影响老版本，临时将此方法加在此处
	 */
	private function createQyFan2($site, $user, $authid, $timestamp = null, $mapDeptR2L = null) {

		$create_at = time();
		empty($timestamp) && $timestamp = $create_at;

		$fan = array();
		$fan['siteid'] = $site;
		$fan['openid'] = $user->userid;
		$fan['nickname'] = $user->name;
		// $fan['verified'] = 'Y';
		//$fan['create_at'] = $create_at;
		$fan['sync_at'] = $timestamp;
		isset($user->mobile) && $fan['mobile'] = $user->mobile;
		isset($user->email) && $fan['email'] = $user->email;
		isset($user->weixinid) && $fan['weixinid'] = $user->weixinid;
		$extattr = array();
		if (isset($user->extattr) && !empty($user->extattr->attrs)) {
			foreach ($user->extattr->attrs as $ea) {
				$extattr[urlencode($ea->name)] = urlencode($ea->value);
			}

		}
		/**
		 * 处理岗位信息
		 */
		if (!empty($user->position)) {
			$extattr['position'] = urlencode($user->position);
		}

		$fan['extattr'] = urldecode(json_encode($extattr));
		/**
		 * 建立成员和部门之间的关系
		 */
		$udepts = array();
		foreach ($user->department as $ud) {
			if (empty($mapDeptR2L)) {
				$q = array(
					'fullpath',
					'xxt_site_member_department',
					"siteid='$site' and extattr like '%\"id\":$ud,%'",
				);
				$fullpath = $this->model()->query_val_ss($q);
				$udepts[] = explode(',', $fullpath);
			} else {
				isset($mapDeptR2L[$ud]) && $udepts[] = explode(',', $mapDeptR2L[$ud]['path']);
			}

		}

		$fan['depts'] = json_encode($udepts);

		$model = $this->model();

		/*
			 * 新增加的企业号通信录成员关联到信信通的账户
		*/
		$openid = $fan['openid'];
		$uid = $this->model()->query_val_ss([
			'uid',
			'xxt_site_account',
			" qy_openid ='$openid' ",
		]);

		if ($uid) {
			$fan['userid'] = $uid;
		} else {
			$option = array(
				'ufrom' => 'qy',
				'qy_openid' => $openid,
				'nickname' => $fan['nickname'],
				'headimgurl' => isset($user->avatar) ? $user->avatar : '',
			);

			$account = $this->model("site\\user\\account")->blank($site, true, $option);

			$fan['userid'] = $account->uid;
		}

		/**
		 * 为了兼容服务号和订阅号的操作，生成和成员用户对应的粉丝用户
		 */
		if ($old = $this->model('sns\qy\fan')->byOpenid($site, $user->userid)) {
			isset($user->avatar) && $fan['headimgurl'] = $user->avatar;
			if ($user->status == 1 && $old->subscribe_at == 0) {
				$fan['subscribe_at'] = $timestamp;
			} else if ($user->status == 1 && $old->unsubscribe_at != 0) {
				$fan['unsubscribe_at'] = 0;
			} else if ($user->status == 4 && $old->unsubscribe_at == 0) {
				$fan['unsubscribe_at'] = $timestamp;
			}
			$model->update(
				'xxt_site_qyfan',
				$fan,
				"siteid='$site' and openid='{$user->userid}'"
			);
			$sync_id = $old->id;
		} else {
			isset($user->avatar) && $fan['headimgurl'] = $user->avatar;
			$user->status == 1 && $fan['subscribe_at'] = $timestamp;
			$sync_id = $model->insert('xxt_site_qyfan', $fan, true);
		}

		return true;
	}
	/**
	 * 更新企业号用户信息
	 * 因为企业号关注事件需要调用此方法，但此方法是在site/fe/base中。目前处理企业号关注信息类，并不继承
	 * sit/fe/base，所以为了不影响老版本，临时将此方法加在此处
	 */
	private function updateQyFan2($site, $luser, $user, $authid, $timestamp = null, $mapDeptR2L = null) {
		$model = $this->model();
		empty($timestamp) && $timestamp = time();

		$fan = array();
		$fan['sync_at'] = $timestamp;
		isset($user->mobile) && $fan['mobile'] = $user->mobile;
		isset($user->email) && $fan['email'] = $user->email;
		$extattr = array();
		if (isset($user->extattr) && !empty($user->extattr->attrs)) {
			foreach ($user->extattr->attrs as $ea) {
				$extattr[urlencode($ea->name)] = urlencode($ea->value);
			}
		}
		$fan['tags'] = ''; // 先将成员的标签清空，标签同步的阶段会重新更新
		/**
		 * 处理岗位信息
		 */
		if (!empty($user->position)) {
			$extattr['position'] = urlencode($user->position);
		}
		$fan['extattr'] = urldecode(json_encode($extattr));
		/**
		 * 建立成员和部门之间的关系
		 */
		$udepts = array();
		foreach ($user->department as $ud) {
			if (empty($mapDeptR2L)) {
				$q = array(
					'fullpath',
					'xxt_site_member_department',
					"siteid='$site' and extattr like '%\"id\":$ud,%'",
				);
				$fullpath = $model->query_val_ss($q);
				$udepts[] = explode(',', $fullpath);
			} else {
				isset($mapDeptR2L[$ud]) && $udepts[] = explode(',', $mapDeptR2L[$ud]['path']);
			}
		}
		$fan['depts'] = json_encode($udepts);

		/*
			 * 建立企业号通信录成员关联到信信通的账户
		*/
		$openid = $user->userid;
		$uid = $this->model()->query_val_ss([
			'uid',
			'xxt_site_account',
			" qy_openid ='$openid' ",
		]);

		if ($uid) {
			$fan['userid'] = $uid;
		} else {
			$option = array(
				'ufrom' => 'qy',
				'qy_openid' => $openid,
				'nickname' => $user->name,
				'headimgurl' => isset($user->avatar) ? $user->avatar : '',
			);

			$account = $this->model("site\\user\\account")->blank($site, true, $option);

			$fan['userid'] = $account->uid;
		}
		/**
		 * 成员用户对应的粉丝用户
		 */
		if ($old = $this->model('sns\qy\fan')->byOpenid($site, $user->userid)) {
			$fan['nickname'] = $user->name;
			isset($user->avatar) && $fan['headimgurl'] = $user->avatar;
			if ($user->status == 1 && $old->subscribe_at == 0) {
				$fan['subscribe_at'] = $timestamp;
			} else if ($user->status == 1 && $old->unsubscribe_at != 0) {
				$fan['unsubscribe_at'] = 0;
			} else if ($user->status == 4 && $old->unsubscribe_at == 0) {
				$fan['unsubscribe_at'] = $timestamp;
			}
			$model->update(
				'xxt_site_qyfan',
				$fan,
				"siteid='$site' and openid='{$user->userid}'"
			);
			$sync_id = $old->id;
		} else {
			$fan['siteid'] = $site;
			$fan['openid'] = $user->userid;
			$fan['nickname'] = $user->name;
			isset($user->avatar) && $fan['headimgurl'] = $user->avatar;
			$user->status == 1 && $fan['subscribe_at'] = $timestamp;
			$sync_id = $model->insert('xxt_site_qyfan', $fan, true);
		}

		return true;
	}

}