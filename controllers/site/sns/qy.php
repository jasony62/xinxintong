<?php
namespace site\sns;

require_once TMS_APP_DIR . '/lib/wxqy/WXBizMsgCrypt.php';
require_once dirname(__FILE__) . '/usercall.php';
require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';

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
			header('Content-Type: text/html; charset=utf-8');
			die($rst[1]);
			break;
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
	private function handle($mpid, $call) {
		/**
		 * 记录消息日志
		 */
		$msg = $call->to_array();
		$msg['mpid'] = $mpid;
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
				if ($reply = $this->model('reply')->other_call($mpid, 'location')) {
					$r = $this->model('reply\\' . $reply->matter_type, $msg, $reply->matter_id);
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
				$tr = $this->model('reply\text', $msg, $reply, false);
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
		$mpid = $msg['mpid'];
		$openid = $msg['from_user'];
		$wall = $this->model('app\wall');

		if ($wid = $wall->joined($mpid, $openid)) {
			return array($wid, $wall);
		} else {
			return false;
		}
	}
	/**
	 * 事件消息处理
	 */
	private function event_call($data) {
		//$this->model('log')->log($data['mpid'], 'event', json_encode($data));
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
		$mpid = $data['mpid'];
		$openid = $data['from_user'];
		$modelFan = $this->model('user/fans');
		if ($fan = $modelFan->byOpenid($mpid, $openid, '*')) {
			/**
			 * 粉丝重新关注
			 */
			$modelFan->update(
				'xxt_fans',
				array(
					'subscribe_at' => $current,
					'unsubscribe_at' => 0,
					'sync_at' => $current,
				),
				"mpid='$mpid' and openid='$openid'"
			);
		} else {
			/**
			 * 新粉丝关注
			 */
			$mpa = \TMS_APP::G('mp\mpaccount');
			if ($mpa->mpsrc === 'qy') {
				$result = $this->getFanInfo($mpid, $openid, false);
				if ($result[0] === false) {
					$tr = $this->model('reply\text', $data, $result[1], false);
					$tr->exec();
				}
				$user = $result[1];
				$rst = $this->createQyFan($mpid, $user);
				if (is_string($rst)) {
					$tr = $this->model('reply\text', $call, $rst, false);
					$tr->exec();
				}
			} else {
				/*创建站点用户*/
				$siteUser = $this->model('site\user\account')->blank($mpid, true);
				/*new fan*/
				$fan = array(
					'fid' => $modelFan->calcId($mpid, $openid),
					'mpid' => $mpid,
					'openid' => $openid,
					'subscribe_at' => $current,
					'sync_at' => $current,
					'userid' => $siteUser->uid,
				);
				$modelFan->insert('xxt_fans', $fan, false);
				// log
				$this->model('log')->writeSubscribe($mpid, $openid);
			}
		}
		/**
		 * 用户关注公众账号时是首次获得【touser】信息的机会
		 * 需要更新和mpid的匹配关系
		 * 如果开通了高级接口，获得粉丝信息
		 */
		$mpa = \TMS_APP::G('mp\mpaccount');
		if ($mpa->mpsrc !== 'qy') {
			$this->model()->update(
				'xxt_mpaccount',
				array($mpa->mpsrc . '_mpid' => $data['to_user']),
				"mpid='$mpid'"
			);
			$apis = $this->model('mp\mpaccount')->getApis($mpid);
			if ($apis && $apis->{$mpa->mpsrc . '_fans'} === 'Y') {
				/**
				 * 获取粉丝信息并更新
				 * todo 是否应该更新用户所属的分组？
				 */
				$fanInfo = $this->getFanInfo($mpid, $openid);
				if ($fanInfo[0]) {
					$nickname = trim($this->model()->escape($fanInfo[1]->nickname));
					$u = array(
						'nickname' => empty($nickname) ? '未知' : $nickname,
						'sex' => $fanInfo[1]->sex,
						'city' => $fanInfo[1]->city,
					);
					isset($fanInfo[1]->headimgurl) && $u['headimgurl'] = $fanInfo[1]->headimgurl;
					isset($fanInfo[1]->icon) && $u['headimgurl'] = $fanInfo[1]->icon; // 易信认证号接口
					isset($fanInfo[1]->province) && $u['province'] = $fanInfo[1]->province;
					isset($fanInfo[1]->country) && $u['country'] = $fanInfo[1]->country;
					$fanpk = "mpid='$mpid' and openid='$openid'";
					$this->model()->update('xxt_fans', $u, $fanpk);
				}
			}
		}
		if (!empty($scene_id)) {
			/**
			 * 通过扫描场景二维码关注
			 * 将关注事件转换为场景二维码事件
			 */
			$scene_id = substr($scene_id, strlen('qrscene_'));
			$scandata = $data;
			$scandata['data'] = json_encode(array('scan', $scene_id));
			if ($reply = $this->qrcode_call($scandata)) {
				is_object($reply) && $reply->exec();
			}
		}
		if ($reply = $this->model('reply')->other_call($mpid, 'subscribe')) {
			/**
			 * subscribe reply.
			 */
			$r = $this->model('reply\\' . $reply->matter_type, $data, $reply->matter_id);
			$r->exec();
		}
	}
	/**
	 * 取消关注
	 */
	private function unsubscribe_call($data) {
		$mpid = $data['mpid'];
		$openid = $data['from_user'];
		$unsubscribe_at = time();
		$rst = $this->model()->update(
			'xxt_fans',
			array('unsubscribe_at' => $unsubscribe_at),
			"mpid='$mpid' and openid='$openid'"
		);
		$rst = $this->model()->update(
			'xxt_member',
			array('forbidden' => 'Y'),
			"mpid='$mpid' and openid='$openid'"
		);

		return $rst;
	}
	/**
	 * 群发消息处理结果（仅限微信）
	 */
	private function massmsg_call($data) {
		$mpid = $data['mpid'];

		$e = json_decode($data['data']);
		$msgid = $e->MsgID;
		/**
		 * 更新数据状态
		 */
		$rst = $this->model()->update(
			'xxt_log_massmsg',
			array(
				'status' => $e->Status,
				'total_count' => $e->TotalCount,
				'filter_count' => $e->FilterCount,
				'sent_count' => $e->SentCount,
				'error_count' => $e->ErrorCount,
			),
			"mpid='$mpid' and msgid='$msgid'"
		);

		return $rst;
	}
	/**
	 * 模板消息处理结果
	 *
	 * 仅限微信
	 */
	private function template_call($data, $msgid, $status) {
		$mpid = $data['mpid'];
		$openid = $data['from_user'];
		/**
		 * 更新数据状态
		 */
		$rst = $this->model()->update(
			'xxt_log_tmplmsg',
			array('status' => $status),
			"mpid='$mpid' and openid='$openid' and msgid='$msgid'"
		);
		/**
		 * 处理事件响应，选择消息转发事件，通知模板消息处理结果
		 */
		if ($reply = $this->model('reply')->other_call($mpid, 'templatemsg')) {
			$r = $this->model('reply\\' . $reply->matter_type, $data, $reply->matter_id);
			$r->exec();
		}
	}
	/**
	 * 卡卷事件
	 */
	private function card_call($data) {
		$mpid = $data['mpid'];
		/**
		 * 处理事件响应，消息转发事件
		 */
		if ($reply = $this->model('reply')->other_call($mpid, 'cardevent')) {
			$r = $this->model('reply\\' . $reply->matter_type, $data, $reply->matter_id);
			$r->exec();
		}
	}
	/**
	 * 文本消息响应
	 * 如果没有定义如何响应，就调用缺省的响应内容
	 */
	private function text_call($call) {
		$mpid = $_GET['mpid'];
		$text = $call['data'];
		if ($reply = $this->model('reply')->text_call($mpid, $text)) {
			if ($reply->access_control === 'Y') {
				$this->accessControl4Call($call, 'Text', $reply->keyword, $reply->authapis);
			}
			$r = $this->model('reply\\' . $reply->matter_type, $call, $reply->matter_id, $reply->keyword);
			$r->exec();
		} else {
			$this->universal_call($call);
		}
	}
	/**
	 * 语音消息响应
	 */
	private function voice_call($call) {
		$mpid = $_GET['mpid'];
		$data = $call['data'];
		if (!empty($data[2])) {
			$this->model('reply\text', $call, $data[2], false);
			$tr->exec();
		} else {
			$this->model('reply\text', $call, '未开通语音识别接口', false);
			$tr->exec();
		}
	}
	/**
	 * menu call
	 */
	private function menu_call($call, $k) {
		$mpid = $_GET['mpid'];
		$openid = $call['from_user'];
		if ($reply = $this->model('reply')->menu_call($mpid, $k)) {
			if ($reply->access_control === 'Y') {
				$this->accessControl4Call($call, 'Menu', $k, $reply->authapis);
			}

			if (!empty($reply->matter_type)) {
				/**
				 * demo auto reply
				 * todo 临时代码
				 */
				if ($k === 'demoautoreply') {
					/**
					 * 原始消息
					 */
					$model = $this->model('matter\\' . $reply->matter_type);
					$message = $model->forCustomPush($mpid, $reply->matter_id);
					$this->sendByOpenid($mpid, $openid, $message);
					/**
					 * 附加消息
					 */
					$fan = $this->model('user/fans')->byOpenid($mpid, $openid, 'nickname');
					//$txt = $fan->nickname.'，送你100M[<a href="http://yxs.im/3etcE4">免费流量</a>]尽情听歌，[<a href="http://yxs.im/3etcE4">点此</a>]领取';
					$txt = '推荐阅读[<a href="http://yxs.im/dNZCh3">一季度18地区GDP增速跑赢全国释放啥信号</a>]';
					$message = array(
						"msgtype" => "text",
						"text" => array(
							"content" => $txt,
						),
					);
					$this->sendByOpenid($mpid, $openid, $message);
				} else {
					$r = $this->model('reply\\' . $reply->matter_type, $call, $reply->matter_id);
					$r->exec();
				}
			}
		} else {
			$this->universal_call($call);
		}
	}
	/**
	 * 缺省回复
	 */
	private function universal_call($data) {
		$mpid = $data['mpid'];
		if ($reply = $this->model('reply')->other_call($mpid, 'universal')) {
			$r = $this->model('reply\\' . $reply->matter_type, $data, $reply->matter_id);
			$r->exec();
		}
	}
	/**
	 * 扫描二维码事件
	 *
	 * 企业号目前不支持场景二维码
	 * 由于目前易信的场景二维码客户端无法收到回复信息，因此改为推动客户消息替代
	 */
	private function qrcode_call($call) {
		$mpa = \TMS_APP::G('mp\mpaccount');
		$mpid = $call['mpid'];
		$openid = $call['from_user'];
		$data = json_decode($call['data']);

		if ($reply = $this->model('reply')->qrcode_call($mpid, $data[1])) {
			if ($reply->expire_at > 0) {
				/* 一次性二维码，用完后就删除 */
				$this->model()->delete('xxt_call_qrcode', "id=$reply->id");
			}

			if ($mpa->mpsrc === 'wx') {
				$r = $this->model('reply\\' . $reply->matter_type, $call, $reply->matter_id);
				$r->exec();
			} else {
				$setting = $this->model('mp\mpaccount')->getFeature($mpid, 'yx_custom_push');
				if ($setting->yx_custom_push === 'N') {
					return;
				}
				switch ($reply->matter_type) {
				case 'enrollsignin': //登记活动签到
					$r = $this->model('reply\enrollsignin', $call, $reply->matter_id, false);
					$r2 = $r->exec();
					if ($r2['matter_type'] === 'enroll') {
						$message = $this->model('matter\\' . 'enroll')->forCustomPush($mpid, $r2['matter_id']);
					} else if ($r2['matter_type'] === 'joinwall') {
						$r = new $this->model('reply\joinwall', $call, $r2['matter_id']);
						$tip = $r->exec(false);
						if (!empty($tip)) {
							$message = array(
								"msgtype" => "text",
								"text" => array(
									"content" => $tip,
								),
							);
						}
					} else {
						$message = $this->model('matter\\' . $r2['matter_type'])->forCustomPush($mpid, $r2['matter_id']);
					}
					break;
				case 'joinwall': // 加入信息墙
					$r = $this->model('reply\joinwall', $call, $reply->matter_id);
					$tip = $r->exec(false);
					if (!empty($tip)) {
						$message = array(
							"msgtype" => "text",
							"text" => array(
								"content" => $tip,
							),
						);
					}
					break;
				default:
					$message = $this->model('matter\\' . $reply->matter_type)->forCustomPush($mpid, $reply->matter_id);
				}
				/**
				 * 发送消息
				 */
				if (isset($message)) {
					$rst = $this->sendByOpenid($mpid, $openid, $message);
					if (false === $rst[0]) {
						$err = is_array($rst[1]) ? implode(',', $rst[1]) : $rst[1];
						$tr = $this->model('reply\text', $call, $err, false);
						$tr->exec();
					}
				}
			}
		}
	}
	/**
	 * 访问控制设置
	 *
	 * 检查当前的粉丝用户是否为已经通过认证的注册用户
	 * 检查当前的粉丝用户是否在白名单中
	 *
	 * $call
	 * $call_type [Menu|Text]
	 * $keyword
	 * $authapis
	 */
	private function accessControl4Call($call, $call_type, $keyword, $authapis) {
		/**
		 * check bind data.
		 * 获得当前粉丝用户的身份信息
		 */
		$mpid = $call['mpid'];
		$openid = $call['from_user'];
		$members = $this->getUserMembers($mpid, $openid, $authapis);
		/**
		 * 无法确认用户的身份，要求进行身份认证
		 */
		empty($members) && $this->authReply($call, $authapis);
		/**
		 * 检查用户是否通过了邮箱验证
		 * 如果不需要进行邮箱验证，邮箱会被设置为已通过验证的状态
		 * 如果同时拥有多个认证身份，只要有一个通过验证，就认为当前用户通过验证
		 */
		$requireEmailVerified = true;
		foreach ($members as $member) {
			if ($member->email_verified === 'Y') {
				$requireEmailVerified = false;
				break;
			}
		}
		if ($requireEmailVerified) {
			/**
			 * 提醒用户进行邮箱验证
			 * 如果支持多个身份认证接口，应该允许用户自己选择用哪个身份认证
			 */
			$tip = array();
			foreach ($members as $member) {
				$tip[] = $this->model('user/authapi')->getNotpassStatement(
					$member->authapi_id, $mpid, $openid
				);
			}
			$tip = implode("\n", $tip);
			$tr = $this->model('reply\text', $call, $tip, false);
			$tr->exec();
		}
		/**
		 * 是否在白名单中，只要有一个身份匹配就允许访问
		 */
		$matched = false;
		foreach ($members as $member) {
			$matched = $this->model('acl')->canAccessCall($mpid, $call_type, $keyword, $member, $authapis);
		}

		/**
		 * 不y允许访问，通知用户
		 */
		if (!$matched) {
			$tip = array();
			foreach ($members as $member) {
				$tip[] = $this->model('user/authapi')->getAclStatement(
					$member->authapi_id, $mpid, $openid
				);
			}
			$tip = implode("\n", $tip);
			$tr = $this->model('reply\text', $call, $tip, false);
			$tr->exec();
		}
	}
	/**
	 * 提示用户进行身份认证
	 *
	 * $call 客户端发起的请求
	 * 由于请求是由客户端直接发起的，因此其中的openid和用户直接关联，是可以信赖的信息
	 *
	 */
	private function authReply($call, $authapis) {
		$aAuthapis = explode(',', $authapis);
		$tip = array();
		foreach ($aAuthapis as $authid) {
			$tip[] = $this->model('user/authapi')->getEntryStatement(
				$authid,
				$call['mpid'],
				$call['src'],
				$call['from_user']
			);
		}
		$tip = implode("\n", $tip);
		$tr = $this->model('reply\text', $call, $tip, false);
		$tr->exec();
	}
	/**
	 * 判断发起呼叫的用户是否为认证用户，如果是则返回用户的身份信息
	 *
	 * 一个粉丝用户可能有多个认证用户身份
	 * 所以要知道是哪个通过认证接口认证的用户身份
	 * 如果是企业号的用户可能就不再需要进行认证，因此有可能不指定authapis
	 *
	 * $mpid
	 * $openid
	 * $authapis
	 *
	 */
	private function getUserMembers($mpid, $openid, $authapis) {
		$q = array(
			'*',
			'xxt_member m',
			"m.mpid='$mpid' and m.forbidden='N' and m.openid='$openid'",
		);
		!empty($authapis) && $q[2] .= " and authapi_id in ($authapis)";

		$mids = $this->model()->query_objs_ss($q);

		return $mids;
	}
}