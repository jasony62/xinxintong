<?php
/**
 *
 */
class log_model extends TMS_MODEL {
	/**
	 *
	 */
	public function log($mpid, $method, $data, $agent = '', $referer = '') {
		if (empty($agent) && isset($_SERVER['HTTP_USER_AGENT'])) {
			$agent = $_SERVER['HTTP_USER_AGENT'];
		}
		if (empty($referer) && isset($_SERVER['HTTP_REFERER'])) {
			$referer = $_SERVER['HTTP_REFERER'];
		}

		$current = time();
		$log = [];
		$log['mpid'] = $mpid;
		$log['method'] = $this->escape($method);
		$log['create_at'] = $current;
		$log['data'] = $this->escape($data);
		$log['user_agent'] = $this->escape($agent);
		$log['referer'] = $this->escape($referer);

		$logid = $this->insert('xxt_log', $log, true);

		return $logid;
	}
	/**
	 * 接收消息日志
	 */
	public function receive($msg) {
		$openid = $msg['from_user'];
		if (isset($msg['siteid'])) {
			// should remove
			$mpid = $msg['siteid'];
			$src = $msg['src'];
			$fan = TMS_APP::model("sns\\" . $src . "\\fan")->byOpenid($mpid, $openid, 'nickname');
		} else {
			$mpid = $msg['mpid'];
			$fan = TMS_APP::model('user/fans')->byOpenid($mpid, $openid, 'nickname');
		}

		$createAt = $msg['create_at'];

		$r = array();
		$r['mpid'] = $mpid;
		!empty($msg['msgid']) && $r['msgid'] = $msg['msgid'];
		$r['to_user'] = $msg['to_user'];
		$r['openid'] = $openid;
		$r['nickname'] = !empty($fan) ? $this->escape($fan->nickname) : '';
		$r['create_at'] = $createAt;
		$r['type'] = $msg['type'];
		if (is_array($msg['data'])) {
			$data = array();
			foreach ($msg['data'] as $d) {
				$data[] = urlencode($d);
			}
			$r['data'] = $this->escape(urldecode(json_encode($data)));
		} else {
			$r['data'] = $this->escape($msg['data']);
		}

		$this->insert('xxt_log_mpreceive', $r, false);

		return true;
	}
	/**
	 * 是否已经接收过消息
	 *
	 * @param array $msg
	 * @param int $interval 两条消息的时间间隔
	 */
	public function hasReceived($msg, $interval = 60) {
		$mpid = isset($msg['mpid']) ? $msg['mpid'] : $msg['siteid'];
		$msgid = $msg['msgid'];
		/**
		 * 没有消息ID就认为没收到过
		 */
		if (empty($msgid)) {
			$current = time() - $interval;
			$openid = $msg['from_user'];
			if (is_array($msg['data'])) {
				$data = array();
				foreach ($msg['data'] as $d) {
					$data[] = urlencode($d);
				}
				$logData = $this->escape(urldecode(json_encode($data)));
			} else {
				$logData = $this->escape($msg['data']);
			}
			$q = [
				'count(*)',
				'xxt_log_mpreceive',
				"mpid='$mpid' and openid='$openid' and data='$logData' and create_at>$current",
			];
			$cnt = (int) $this->query_val_ss($q);
		} else {
			$q = [
				'count(*)',
				'xxt_log_mpreceive',
				"mpid='$mpid' and msgid='$msgid'",
			];
			$cnt = (int) $this->query_val_ss($q);
		}

		return $cnt !== 0;
	}
	/**
	 * 记录所有发送给用户的消息
	 */
	public function send($mpid, $openid, $groupid, $content, $matter) {
		$i['mpid'] = $mpid;
		$i['creater'] = TMS_CLIENT::get_client_uid();
		$i['create_at'] = time();
		!empty($openid) && $i['openid'] = $openid;
		!empty($groupid) && $i['groupid'] = $groupid;
		!empty($content) && $i['content'] = $this->escape($content);
		if (!empty($matter)) {
			$i['matter_id'] = $matter->id;
			$i['matter_type'] = $matter->type;
		}
		$this->insert('xxt_log_mpsend', $i, false);

		return true;
	}
	/**
	 *
	 */
	public function read() {
	}
	/**
	 * 用户是否可以接收t推送消息
	 */
	public function canReceivePush($mpid, $openid) {
		return true;
	}
	/**
	 * 汇总各类日志，形成用户完整的踪迹
	 */
	public function track($mpid, $openid, $page = 1, $size = 30) {
		$q = array(
			'creater,create_at,content,matter_id,matter_type',
			'xxt_log_mpsend',
			"mpid='$mpid' and openid='$openid'",
		);
		$q2 = array(
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
			'o' => 'create_at desc',
		);

		$sendlogs = $this->query_objs_ss($q, $q2);

		$q = array(
			'create_at,data content',
			'xxt_log_mpreceive',
			"mpid='$mpid' and openid='$openid' and type='text'",
		);
		$q2 = array(
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
			'o' => 'create_at desc',
		);

		$recelogs = $this->query_objs_ss($q, $q2);

		$logs = array_merge($sendlogs, $recelogs);

		/**
		 * order by create_at
		 */
		usort($logs, function ($a, $b) {
			return $b->create_at - $a->create_at;
		});

		return $logs;
	}
	/**
	 * 记录访问素材日志
	 */
	public function writeMatterRead($mpid, $user, $matter, $client, $shareby, $search, $referer) {
		$current = time();
		$d = array();
		$d['mpid'] = $mpid;
		$d['vid'] = $user->vid;
		$d['openid'] = $user->openid;
		$d['nickname'] = $this->escape($user->nickname);
		$d['read_at'] = $current;
		$d['matter_id'] = $matter->id;
		$d['matter_type'] = $matter->type;
		$d['matter_title'] = $this->escape($matter->title);
		$d['matter_shareby'] = $shareby;
		$d['user_agent'] = $client->agent;
		$d['client_ip'] = $client->ip;
		$d['search'] = $search;
		$d['referer'] = $referer;

		$logid = $this->insert('xxt_log_matter_read', $d, true);

		// 日志汇总
		$this->writeUserAction($mpid, $user, $current, 'R', $logid);
		$this->writeMatterAction($mpid, $matter, $current, 'R', $logid);
		$this->writeUserMatterAction($mpid, $user, $matter, $current, 'R');

		return $logid;
	}
	/**
	 * 文章打开的次数
	 * todo 应该用哪个openid，根据oauth是否开放来决定？
	 */
	public function getMatterRead($type, $id, $page, $size) {
		$q = array(
			'l.openid,l.nickname,l.read_at',
			'xxt_log_matter_read l',
			"l.matter_type='$type' and l.matter_id='$id'",
		);
		/**
		 * 分页数据
		 */
		$q2 = array(
			'o' => 'l.read_at desc',
			'r' => array(
				'o' => (($page - 1) * $size),
				'l' => $size,
			),
		);

		$log = $this->query_objs_ss($q, $q2);

		return $log;
	}
	/**
	 * 记录分享动作
	 *
	 * $vid  访客ID
	 * $mpid 公众号ID，是当前用户
	 * $matter_id 分享的素材ID
	 * $matter_type 分享的素材类型
	 * $ooid  谁进行的分享
	 * $user_agent  谁进行的分享
	 * $client_ip  谁进行的分享
	 * $share_at 什么时间做的分享
	 * $share_to  分享给好友或朋友圈
	 *
	 */
	public function writeShareAction($siteId, $shareid, $shareto, $shareby, $user, $matter, $client) {
		$current = time();

		$d = array();
		$d['siteid'] = $siteId;
		$d['shareid'] = $shareid;
		$d['share_at'] = $current;
		$d['share_to'] = $shareto;
		$d['userid'] = isset($user->userid) ? $user->userid : '';
		$d['nickname'] = $this->escape($user->nickname);
		$d['matter_id'] = $matter->id;
		$d['matter_type'] = $matter->type;
		$d['matter_title'] = $this->escape($matter->title);
		$d['matter_shareby'] = $shareby;
		$d['user_agent'] = $client->agent;
		$d['client_ip'] = $client->ip;

		$logid = $this->insert('xxt_log_matter_share', $d, true);

		// 日志汇总
		//$this->writeUserAction($siteId, $user, $current, 'S' . $shareto, $logid);

		//$this->writeMatterAction($siteId, $matter, $current, 'S' . $shareto, $logid);

		return $logid;
	}
	/**
	 * 用户行为汇总日志
	 * 为了便于进行数据统计
	 */
	private function writeUserAction($siteId, $user, $action_at, $action_name, $original_logid) {
		$d = array();
		$d['siteid'] = $siteId;
		$d['nickname'] = $this->escape($user->nickname);
		$d['action_at'] = $action_at;
		$d['original_logid'] = $original_logid;
		switch ($action_name) {
		case 'R':
			$d['act_read'] = 1;
			break;
		case 'SF':
			$d['act_share_friend'] = 1;
			break;
		case 'ST':
			$d['act_share_timeline'] = 1;
			break;
		default:
			die('invalid parameter!');
		}
		$this->insert('xxt_log_user_action', $d, false);

		if (!empty($user->openid)) {
			switch ($action_name) {
			case 'R':
				$this->update("update xxt_fans set read_num=read_num+1 where mpid='$siteId' and userid='$user->openid'");
				break;
			case 'SF':
				$this->update("update xxt_fans set share_friend_num=share_friend_num+1 where mpid='$siteId' and userid='$user->openid'");
				break;
			case 'ST':
				$this->update("update xxt_fans set share_timeline_num=share_timeline_num+1 where mpid='$siteId' and userid='$user->openid'");
				break;
			}
		}

		return true;
	}
	/**
	 * 素材行为汇总日志
	 * 为了便于进行数据统计
	 */
	private function writeMatterAction($mpid, $matter, $action_at, $action_name, $original_logid) {
		$d = array();
		$d['mpid'] = $mpid;
		$d['matter_type'] = $matter->type;
		$d['matter_id'] = $matter->id;
		$d['matter_title'] = $this->escape($matter->title);
		$d['action_at'] = $action_at;
		$d['original_logid'] = $original_logid;
		switch ($action_name) {
		case 'R':
			$d['act_read'] = 1;
			break;
		case 'SF':
			$d['act_share_friend'] = 1;
			break;
		case 'ST':
			$d['act_share_timeline'] = 1;
			break;
		default:
			die('invalid parameter!');
		}
		$this->insert('xxt_log_matter_action', $d, false);

		if (!empty($mpid)) {
			list($year, $month, $day) = explode('-', date('Y-n-j'));
			$logid = $this->query_val_ss(array('id', 'xxt_log_mpa', "mpid='$mpid' and year='$year' and month='$month' and day='$day'"));
			if (false === $logid) {
				if ($last = $this->query_obj_ss(array('*', 'xxt_log_mpa', "mpid='$mpid' and islast='Y'"))) {
					$this->update('xxt_log_mpa', array('islast' => 'N'), "mpid='$mpid' and islast='Y'");
				}

				$today = array(
					'mpid' => $mpid,
					'year' => $year,
					'month' => $month,
					'day' => $day,
					'islast' => 'Y',
					'read_sum' => $last ? $last->read_sum : 0,
					'sf_sum' => $last ? $last->sf_sum : 0,
					'st_sum' => $last ? $last->st_sum : 0,
					'fans_sum' => $last ? $last->fans_sum : 0,
					'member_sum' => $last ? $last->member_sum : 0,
				);
				switch ($action_name) {
				case 'R':
					$today['read_inc'] = 1;
					$today['read_sum'] = (int) $today['read_sum'] + 1;
					break;
				case 'SF':
					$today['sf_inc'] = 1;
					$today['sf_sum'] = (int) $today['sf_sum'] + 1;
					break;
				case 'ST':
					$today['st_inc'] = 1;
					$today['st_sum'] = (int) $today['st_sum'] + 1;
					break;
				}
				$this->insert('xxt_log_mpa', $today, false);
			} else {
				switch ($action_name) {
				case 'R':
					$this->update("update xxt_log_mpa set read_inc=read_inc+1,read_sum=read_sum+1 where id='$logid'");
					break;
				case 'SF':
					$this->update("update xxt_log_mpa set sf_inc=sf_inc+1,sf_sum=sf_sum+1 where id='$logid'");
					break;
				case 'ST':
					$this->update("update xxt_log_mpa set st_inc=st_inc+1,st_sum=st_sum+1 where id='$logid'");
					break;
				}
			}
		}

		return true;
	}
	/**
	 * 用户行为汇总日志
	 * 为了便于进行数据统计
	 */
	private function writeUserMatterAction($mpid, $user, $matter, $action_at, $action_name) {
		$q = array(
			'id',
			'xxt_log_user_matter',
			"mpid='$mpid' and openid='$user->openid' and matter_id='$matter->id' and matter_type='$matter->type'",
		);
		$lastid = $this->query_val_ss($q);
		if ($lastid) {
			switch ($action_name) {
			case 'R':
				$this->update("update xxt_log_user_matter set read_num=read_num+1 where id=$lastid");
				break;
			case 'SF':
				$this->update("update xxt_log_user_matter set share_friend_num=share_friend_num+1 where id=$lastid");
				break;
			case 'ST':
				$this->update("update xxt_log_user_matter set share_timeline_num=share_timeline_num+1 where id=$lastid");
				break;
			default:
				die('invalid parameter!');
			}
		} else {
			$log = array();
			$log['mpid'] = $mpid;
			$log['openid'] = $user->openid;
			$log['nickname'] = $this->escape($user->nickname);
			$log['matter_id'] = $matter->id;
			$log['matter_type'] = $matter->type;
			$log['matter_title'] = $this->escape($matter->title);
			$log['last_action_at'] = $action_at;
			switch ($action_name) {
			case 'R':
				$log['read_num'] = 1;
				break;
			case 'SF':
				$log['share_friend_num'] = 1;
				break;
			case 'ST':
				$log['share_timeline_num'] = 1;
				break;
			default:
				die('invalid parameter!');
			}
			$this->insert('xxt_log_user_matter', $log, false);
		}

		return true;
	}
	/**
	 *
	 */
	public function writeSubscribe($mpid, $openid) {
		// list($year, $month, $day) = explode('-', date('Y-n-j'));
		// $logid = $this->query_val_ss(array('id', 'xxt_log_mpa', "mpid='$mpid' and year='$year' and month='$month' and day='$day'"));
		// if (false === $logid) {
		// 	if ($last = $this->query_obj_ss(array('*', 'xxt_log_mpa', "mpid='$mpid' and islast='Y'"))) {
		// 		$this->update('xxt_log_mpa', array('islast' => 'N'), "mpid='$mpid' and islast='Y'");
		// 	}

		// 	$today = array(
		// 		'mpid' => $mpid,
		// 		'year' => $year,
		// 		'month' => $month,
		// 		'day' => $day,
		// 		'islast' => 'Y',
		// 		'read_sum' => $last ? $last->read_sum : 0,
		// 		'sf_sum' => $last ? $last->sf_sum : 0,
		// 		'st_sum' => $last ? $last->st_sum : 0,
		// 		'fans_inc' => 1,
		// 		'fans_sum' => $last ? ($last->fans_sum + 1) : 1,
		// 		'member_sum' => $last ? $last->member_sum : 0,
		// 	);
		// 	$this->insert('xxt_log_mpa', $today, false);
		// } else {
		// 	$this->update("update xxt_log_mpa set fans_inc=fans_inc+1,fans_sum=fans_sum+1 where id='$logid'");
		// }
	}
	/**
	 *
	 */
	public function writeMemberAuth($mpid, $openid, $mid) {
		list($year, $month, $day) = explode('-', date('Y-n-j'));
		$logid = $this->query_val_ss(array('id', 'xxt_log_mpa', "mpid='$mpid' and year='$year' and month='$month' and day='$day'"));
		if (false === $logid) {
			if ($last = $this->query_obj_ss(array('*', 'xxt_log_mpa', "mpid='$mpid' and islast='Y'"))) {
				$this->update('xxt_log_mpa', array('islast' => 'N'), "mpid='$mpid' and islast='Y'");
			}

			$today = array(
				'mpid' => $mpid,
				'year' => $year,
				'month' => $month,
				'day' => $day,
				'islast' => 'Y',
				'read_sum' => $last ? $last->read_sum : 0,
				'sf_sum' => $last ? $last->sf_sum : 0,
				'st_sum' => $last ? $last->st_sum : 0,
				'fans_sum' => $last ? $last->fans_sum : 0,
				'member_inc' => 1,
				'member_sum' => $last ? ($last->member_sum + 1) : 1,
			);
			$this->insert('xxt_log_mpa', $today, false);
		} else {
			$this->update("update xxt_log_mpa set member_inc=member_inc+1,member_sum=member_sum+1 where id='$logid'");
		}
	}
	/**
	 * 群发消息发送日志
	 */
	public function mass($sender, $mpid, $matterId, $matterType, $message, $msgid, $result) {
		$log = array(
			'mpid' => $mpid,
			'matter_type' => $matterType,
			'matter_id' => $matterId,
			'sender' => $sender,
			'send_at' => time(),
			'message' => $this->escape(json_encode($message)),
			'result' => $result,
			'msgid' => $msgid,
		);

		$this->insert('xxt_log_massmsg', $log, false);

		return true;
	}
}