<?php
class log_model extends TMS_MODEL {
	/**
	 *
	 */
	public function log($mpid, $method, $data) {
		$current = time();
		$i['mpid'] = $mpid;
		$i['method'] = $method;
		$i['create_at'] = $current;
		$i['data'] = $this->escape($data);

		$this->insert('xxt_log', $i, false);

		return true;
	}
	/**
	 * 接收消息日志
	 */
	public function receive($msg) {
		$mpid = isset($msg['mpid']) ? $msg['mpid'] : $msg['siteid'];
		$openid = $msg['from_user'];

		$fan = TMS_APP::model('user/fans')->byOpenid($mpid, $openid, 'nickname');

		$createAt = $msg['create_at'];

		$r = array();
		$r['mpid'] = $mpid;
		$r['msgid'] = $msg['msgid'];
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
		$d['userid'] = $user->userid;
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
				$this->update("update xxt_fans set read_num=read_num+1 where site='$siteId' and userid='$user->openid'");
				break;
			case 'SF':
				$this->update("update xxt_fans set share_friend_num=share_friend_num+1 where site='$siteId' and userid='$user->openid'");
				break;
			case 'ST':
				$this->update("update xxt_fans set share_timeline_num=share_timeline_num+1 where site='$siteId' and userid='$user->openid'");
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
				'fans_inc' => 1,
				'fans_sum' => $last ? ($last->fans_sum + 1) : 1,
				'member_sum' => $last ? $last->member_sum : 0,
			);
			$this->insert('xxt_log_mpa', $today, false);
		} else {
			$this->update("update xxt_log_mpa set fans_inc=fans_inc+1,fans_sum=fans_sum+1 where id='$logid'");
		}
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

	/**
	 * 群发消息日志查询
	 *
	 * $mpid 父账号或者子账号，父账号返回所有子账号的日志，子账号返回子账号的数据
	 * $offset 相对于当前日期的偏移日期，以天为单位
	 */
	public function massByMpid($mpid, $offset = 0) {
		$modelMp = \TMS_APP::M('mp\mpaccount');

		$mpa = $modelMp->byId($mpid, 'mpid,mpsrc,asparent');

		if ($mpa->asparent === 'Y') {
			$mpset = "exists(select 1 from xxt_mpaccount m where m.parent_mpid='$mpid' and l.mpid=m.mpid)";
		} else {
			$mpset = "mpid='$mpa->mpid'";
		}

		$begin = mktime(0, 0, 0, date("m"), date("d") + $offset, date("Y"));
		$end = $begin + 86400;

		$q = array(
			'l.id,l.mpid,l.matter_id,l.matter_type,result',
			'xxt_log_massmsg l',
			"l.send_at>=$begin and l.send_at<$end and $mpset",
		);
		$q2 = array(
			'o' => 'mpid,send_at desc',
		);

		$logs = $this->query_objs_ss($q, $q2);

		return $logs;
	}
	/**
	 * 记录访问素材日志
	 */
	public function matterOp($siteId, $user, $matter, $op) {
		$current = time();
		if ($op !== 'C') {
			/*更新操作，需要将之前的操作设置为非最后操作*/
			$d = array(
				'last_op' => 'N',
			);
			$this->update(
				'xxt_log_matter_op',
				$d,
				"siteid='$siteId' and matter_type='$matter->type' and matter_id='$matter->id' and last_op='Y'"
			);
		}
		$d = array();
		$d['siteid'] = $siteId;
		$d['operator'] = $user->id;
		$d['operator_name'] = $user->name;
		$d['operator_src'] = $user->src;
		$d['operate_at'] = $current;
		$d['operation'] = $op;
		$d['matter_id'] = $matter->id;
		$d['matter_type'] = $matter->type;
		$d['matter_title'] = $this->escape($matter->title);
		!empty($matter->summary) && $d['matter_summary'] = $this->escape($matter->summary);
		!empty($matter->pic) && $d['matter_pic'] = $matter->pic;

		$logid = $this->insert('xxt_log_matter_op', $d, true);

		return $logid;
	}
	/**
	 * 最近操作的素材
	 */
	public function &recentMatters($siteId, $options = array()) {
		$fields = empty($options['fields']) ? '*' : $options['fields'];
		if (empty($options['page'])) {
			$page = new \stdClass;
			$page->at = 1;
			$page->size = 30;
		} else {
			$page = $options['page'];
		}
		$q = array(
			$fields,
			'xxt_log_matter_op',
			"siteid='$siteId' and last_op='Y' and operation<>'D'",
		);
		/* 不包含的类型 */
		if (isset($options['exclude'])) {
			$sql = '(';
			foreach ($options['exclude'] as $excludeMatter) {
				$sql .= "matter_type<>'$excludeMatter'";
			}
			$sql .= ')';
			$q[2] .= ' and ' . $sql;
		}
		/* 分页和排序 */
		$q2 = array(
			'r' => array('o' => ($page->at - 1) * $page->size, 'l' => $page->size),
			'o' => array('operate_at desc'),
		);

		$matters = $this->query_objs_ss($q, $q2);
		$result = array('matters' => $matters);
		if (empty($matters)) {
			$result['total'] = 0;
		} else {
			$q[0] = 'count(*)';
			$result['total'] = $this->query_val_ss($q);
		}

		return $result;
	}
}