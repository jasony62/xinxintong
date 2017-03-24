<?php
namespace matter;

class log_model extends \TMS_MODEL {
	/**
	 * 记录访问素材日志
	 */
	public function addMatterRead($siteId, &$user, $matter, $client, $shareby, $search, $referer) {
		$current = time();
		$d = array();
		$d['siteid'] = $siteId;
		$d['userid'] = $user->userid;
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
		$operation = new \stdClass;
		$operation->name = 'read';
		$operation->at = $current;
		$this->addUserMatterOp($siteId, $user, $matter, $operation, $client, $referer);
		$this->writeUserAction($siteId, $user, $current, 'R', $logid);
		$this->writeMatterAction($siteId, $matter, $current, 'R', $logid);

		return $logid;
	}
	/**
	 * 文章打开的次数
	 * todo 应该用哪个openid，根据oauth是否开放来决定？
	 */
	public function &getMatterRead($type, $id, $page, $size) {
		$result = new \stdClass;
		$q = [
			'l.userid,l.nickname,l.read_at',
			'xxt_log_matter_read l',
			"l.matter_type='$type' and l.matter_id='$id'",
		];
		/**
		 * 分页数据
		 */
		$q2 = [
			'o' => 'l.read_at desc',
			'r' => [
				'o' => (($page - 1) * $size),
				'l' => $size,
			],
		];

		$result->logs = $this->query_objs_ss($q, $q2);

		$q[0] = 'count(*)';
		$result->total = $this->query_val_ss($q);

		return $result;
	}
	/**
	 * 记录分享动作
	 *
	 * $vid  访客ID
	 * $siteId 公众号ID，是当前用户
	 * $matter_id 分享的素材ID
	 * $matter_type 分享的素材类型
	 * $ooid  谁进行的分享
	 * $user_agent  谁进行的分享
	 * $client_ip  谁进行的分享
	 * $share_at 什么时间做的分享
	 * $share_to  分享给好友或朋友圈
	 * $mshareid 素材的分享ID
	 *
	 */
	public function addShareAction($siteId, $shareid, $shareto, $shareby, &$user, &$matter, &$client, $referer = '') {
		$mopenid = '';
		$mshareid = '';
		$current = time();

		$log = array();
		$log['siteid'] = $siteId;
		$log['shareid'] = $shareid;
		$log['share_at'] = $current;
		$log['share_to'] = $shareto;
		$log['userid'] = $user->userid;
		$log['nickname'] = $this->escape($user->nickname);
		$log['matter_id'] = $matter->id;
		$log['matter_type'] = $matter->type;
		$log['matter_title'] = $this->escape($matter->title);
		$log['matter_shareby'] = $shareby;
		$log['user_agent'] = $client->agent;
		$log['client_ip'] = $client->ip;

		$logid = $this->insert('xxt_log_matter_share', $log, true);

		// 日志汇总
		$operation = new \stdClass;
		$operation->name = 'share.' . ['F' => 'friend', 'T' => 'timeline'][$shareto];
		$operation->at = $current;
		$this->addUserMatterOp($siteId, $user, $matter, $operation, $client, $referer);

		$this->writeUserAction($siteId, $user, $current, 'S' . $shareto, $logid);
		$this->writeMatterAction($siteId, $matter, $current, 'S' . $shareto, $logid);

		return $logid;
	}
	/**
	 * 用户行为汇总日志
	 * 为了便于进行数据统计
	 */
	private function writeUserAction($siteId, &$user, $action_at, $action_name, $original_logid) {
		$d = array();
		$d['siteid'] = $siteId;
		$d['userid'] = $user->userid;
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
				$this->update("update xxt_fans set read_num=read_num+1 where mpid='$siteId' and openid='$user->openid'");
				break;
			case 'SF':
				$this->update("update xxt_fans set share_friend_num=share_friend_num+1 where mpid='$siteId' and openid='$user->openid'");
				break;
			case 'ST':
				$this->update("update xxt_fans set share_timeline_num=share_timeline_num+1 where mpid='$siteId' and openid='$user->openid'");
				break;
			}
		}

		return true;
	}
	/**
	 * 素材行为汇总日志
	 * 为了便于进行数据统计
	 */
	private function writeMatterAction($siteId, $matter, $action_at, $action_name, $original_logid) {
		$d = array();
		$d['siteid'] = $siteId;
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

		// if (!empty($siteId)) {
		// 	list($year, $month, $day) = explode('-', date('Y-n-j'));
		// 	$logid = $this->query_val_ss(array('id', 'xxt_log_mpa', "mpid='$siteId' and year='$year' and month='$month' and day='$day'"));
		// 	if (false === $logid) {
		// 		if ($last = $this->query_obj_ss(array('*', 'xxt_log_mpa', "mpid='$siteId' and islast='Y'"))) {
		// 			$this->update('xxt_log_mpa', array('islast' => 'N'), "mpid='$siteId' and islast='Y'");
		// 		}

		// 		$today = array(
		// 			'mpid' => $siteId,
		// 			'year' => $year,
		// 			'month' => $month,
		// 			'day' => $day,
		// 			'islast' => 'Y',
		// 			'read_sum' => $last ? $last->read_sum : 0,
		// 			'sf_sum' => $last ? $last->sf_sum : 0,
		// 			'st_sum' => $last ? $last->st_sum : 0,
		// 			'fans_sum' => $last ? $last->fans_sum : 0,
		// 			'member_sum' => $last ? $last->member_sum : 0,
		// 		);
		// 		switch ($action_name) {
		// 		case 'R':
		// 			$today['read_inc'] = 1;
		// 			$today['read_sum'] = (int) $today['read_sum'] + 1;
		// 			break;
		// 		case 'SF':
		// 			$today['sf_inc'] = 1;
		// 			$today['sf_sum'] = (int) $today['sf_sum'] + 1;
		// 			break;
		// 		case 'ST':
		// 			$today['st_inc'] = 1;
		// 			$today['st_sum'] = (int) $today['st_sum'] + 1;
		// 			break;
		// 		}
		// 		$this->insert('xxt_log_mpa', $today, false);
		// 	} else {
		// 		switch ($action_name) {
		// 		case 'R':
		// 			$this->update("update xxt_log_mpa set read_inc=read_inc+1,read_sum=read_sum+1 where id='$logid'");
		// 			break;
		// 		case 'SF':
		// 			$this->update("update xxt_log_mpa set sf_inc=sf_inc+1,sf_sum=sf_sum+1 where id='$logid'");
		// 			break;
		// 		case 'ST':
		// 			$this->update("update xxt_log_mpa set st_inc=st_inc+1,st_sum=st_sum+1 where id='$logid'");
		// 			break;
		// 		}
		// 	}
		// }

		return true;
	}
	/**
	 * 用户操作素材日志
	 */
	public function addUserMatterOp($siteId, &$user, &$matter, &$operation, &$client, $referer = '') {
		// 素材累积执行指定操作的次数
		$q = [
			'id,matter_op_num',
			'xxt_log_user_matter',
			"matter_id='$matter->id' and matter_type='$matter->type' and operation='{$operation->name}' and matter_last_op='Y'",
		];
		if ($matterOpNum = $this->query_obj_ss($q)) {
			$this->update('xxt_log_user_matter', ['matter_last_op' => 'N'], "id={$matterOpNum->id}");
			$matterOpNum = (int) $matterOpNum->matter_op_num + 1;
		} else {
			$matterOpNum = 1;
		}
		// 用户对指定素材累积执行指定操作的次数
		$q = [
			'id,user_op_num',
			'xxt_log_user_matter',
			"userid='{$user->userid}' and matter_id='$matter->id' and matter_type='$matter->type' and operation='{$operation->name}' and user_last_op='Y'",
		];
		if ($userOpNum = $this->query_obj_ss($q)) {
			$this->update('xxt_log_user_matter', ['user_last_op' => 'N'], "id={$userOpNum->id}");
			$userOpNum = (int) $userOpNum->user_op_num + 1;
		} else {
			$userOpNum = 1;
		}
		// 新建日志
		$log = array();
		$log['siteid'] = $siteId;
		$log['userid'] = $user->userid;
		$log['nickname'] = $this->escape($user->nickname);
		$log['matter_id'] = $matter->id;
		$log['matter_type'] = $matter->type;
		$log['matter_title'] = $this->escape($matter->title);
		if (!empty($matter->mission_id)) {
			$log['mission_id'] = $matter->mission_id;
			if (!empty($matter->mission_title)) {
				$log['mission_title'] = $this->escape($matter->mission_title);
			} else {
				$mission = $this->M('matter\mission')->byId($matter->mission_id, ['fields' => 'title']);
				$log['mission_title'] = $this->escape($mission->title);
			}
		}
		$log['user_agent'] = $client->agent;
		$log['client_ip'] = isset($client->ip) ? $client->ip : '';
		$log['referer'] = $referer;
		$log['operation'] = $operation->name;
		$log['operate_at'] = isset($operation->at) ? $operation->at : time();
		if (isset($operation->data)) {
			if (is_string($operation->data)) {
				$log['operate_data'] = $this->escape($operation->data);
			} else {
				$log['operate_data'] = $this->escape($this->toJson($operation->data));
			}
		}
		$log['matter_last_op'] = 'Y';
		$log['matter_op_num'] = $matterOpNum;
		$log['user_last_op'] = 'Y';
		$log['user_op_num'] = $userOpNum;

		$logid = $this->insert('xxt_log_user_matter', $log, true);

		return $logid;
	}
	/**
	 * 查询用户操作素材日志
	 */
	public function &listUserMatterOp($matterId, $matterType, $page, $size) {
		$result = new \stdClass;
		$q = [
			'l.userid,l.nickname,l.operation,l.operate_at,l.user_op_num,l.matter_op_num',
			'xxt_log_user_matter l',
			"l.matter_type='$matterType' and l.matter_id='$matterId'",
		];
		/**
		 * 分页数据
		 */
		$q2 = [
			'o' => 'l.operate_at desc',
			'r' => [
				'o' => (($page - 1) * $size),
				'l' => $size,
			],
		];

		$result->logs = $this->query_objs_ss($q, $q2);

		$q[0] = 'count(*)';
		$result->total = $this->query_val_ss($q);

		return $result;
	}
	/**
	 * 记录操作日志
	 *
	 * @param string $siteId
	 * @param object $user
	 * @param object $matter(type,id,title,summary,pic,scenario)
	 * @param string $op
	 * @param object|string $data
	 */
	public function matterOp($siteId, &$user, &$matter, $op, $data = null) {
		$q = [
			'*',
			'xxt_log_matter_op',
			"siteid='$siteId' and operator='{$user->id}' and matter_type='$matter->type' and matter_id='$matter->id' and user_last_op='Y'",
		];
		$userLastLog = $this->query_obj_ss($q);

		// 更新已有记录状态
		$current = time();
		if ($op === 'D' || $op === 'Recycle') {
			/* 如果是删除操作，将所有进行过操作的人的最后一次操作都修改为不是最后一次，实现素材对所有人都不可见 */
			$d = [
				'last_op' => 'N',
			];
			$this->update(
				'xxt_log_matter_op',
				$d,
				"matter_type='$matter->type' and matter_id='$matter->id' and last_op='Y'"
			);
		} else if ($op !== 'C') {
			/* 更新操作记录，需要将之前的操作设置为非最后操作 */
			$this->update(
				'xxt_log_matter_op',
				[
					'last_op' => 'N',
				],
				"siteid='$siteId' and matter_type='$matter->type' and matter_id='$matter->id' and last_op='Y'"
			);
		}
		/* 更新当前用户的最后一次操作记录 */
		$this->update(
			'xxt_log_matter_op',
			[
				'user_last_op' => 'N',
			],
			"siteid='$siteId' and operator='{$user->id}' and matter_type='$matter->type' and matter_id='$matter->id' and user_last_op='Y'"
		);
		// 记录新日志，或更新日志
		if ($userLastLog === false || $current > $userLastLog->operate_at + 600) {
			/* 两次更新操作的间隔超过10分钟，产生新日志 */
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
			!empty($matter->scenario) && $d['matter_scenario'] = $matter->scenario;
			$d['last_op'] = 'Y';
			$d['user_last_op'] = 'Y';
			if (!empty($data)) {
				if (is_object($data) || is_array($data)) {
					$d['data'] = $this->toJson($data);
				} else {
					$d['data'] = $data;
				}
			}

			$logid = $this->insert('xxt_log_matter_op', $d, true);
		} else {
			/* 更新之前的日志 */
			$d = array();
			$d['operator_name'] = $user->name;
			$d['operate_at'] = $current;
			$d['operation'] = $op;
			$d['matter_title'] = $this->escape($matter->title);
			!empty($matter->summary) && $d['matter_summary'] = $this->escape($matter->summary);
			!empty($matter->pic) && $d['matter_pic'] = $matter->pic;
			!empty($matter->scenario) && $d['matter_scenario'] = $matter->scenario;
			$d['last_op'] = 'Y';
			$d['user_last_op'] = 'Y';
			if (!empty($data)) {
				if (is_object($data) || is_array($data)) {
					$d['data'] = $this->toJson($data);
				} else {
					$d['data'] = $data;
				}
			}

			$logid = $userLastLog->id;
			$this->update('xxt_log_matter_op', $d, "id=$logid");
		}

		return $logid;
	}
	/**
	 * 团队内最近操作的素材
	 * 是团队创建的素材，且素材的最后一次操作不是删除或放入回收站
	 *
	 * @param string $siteId
	 */
	public function &recentMatters($siteId, $options = []) {
		$fields = empty($options['fields']) ? '*' : $options['fields'];
		if (empty($options['page'])) {
			$page = new \stdClass;
			$page->at = 1;
			$page->size = 30;
		} else {
			$page = $options['page'];
		}
		$q = [
			$fields,
			'xxt_log_matter_op',
			"siteid='$siteId' and last_op='Y' and operation<>'D' and operation<>'Recycle'",
		];
		$q2 = [
			'r' => ['o' => ($page->at - 1) * $page->size, 'l' => $page->size],
			'o' => ['operate_at desc'],
		];

		$matters = $this->query_objs_ss($q, $q2);
		$result = ['matters' => $matters];
		if (empty($matters)) {
			$result['total'] = 0;
		} else {
			$q[0] = 'count(*)';
			$result['total'] = $this->query_val_ss($q);
		}

		return $result;
	}
	/**
	 * 指定用户最近操作的素材
	 * 用户做过操作，且最后一次操作不是删除操作
	 *
	 * @param object $user
	 * @param array $options(fields,page)
	 */
	public function &recentMattersByUser(&$user, $options = []) {
		$fields = empty($options['fields']) ? '*' : $options['fields'];
		if (empty($options['page'])) {
			$page = new \stdClass;
			$page->at = 1;
			$page->size = 30;
		} else {
			$page = $options['page'];
		}
		$q = [
			$fields,
			'xxt_log_matter_op',
			"operator='{$user->id}' and user_last_op='Y' and (operation<>'D' and operation<>'Recycle' and operation<>'Quit')",
		];
		if (isset($options['matterType'])) {
			$q[2] .= " and matter_type='" . $options['matterType'] . "'";
		}
		if (isset($options['scenario'])) {
			$q[2] .= " and matter_scenario='" . $options['scenario'] . "'";
		}
		$q2 = [
			'r' => ['o' => ($page->at - 1) * $page->size, 'l' => $page->size],
			'o' => ['top desc','operate_at desc'],
		];

		$matters = $this->query_objs_ss($q, $q2);
		$result = ['matters' => $matters];
		if (empty($matters)) {
			$result['total'] = 0;
		} else {
			$q[0] = 'count(*)';
			$result['total'] = $this->query_val_ss($q);
		}

		return $result;
	}
	/**
	 * 站点内最近删除的素材
	 */
	public function &recycleMatters($siteId, $options = array()) {
		$fields = empty($options['fields']) ? '*' : $options['fields'];
		if (empty($options['page'])) {
			$page = new \stdClass;
			$page->at = 1;
			$page->size = 30;
		} else {
			$page = $options['page'];
		}
		$q = [
			$fields,
			'xxt_log_matter_op',
			"siteid='$siteId' and last_op='Y' and operation='Recycle'",
		];
		$q2 = [
			'r' => ['o' => ($page->at - 1) * $page->size, 'l' => $page->size],
			'o' => ['operate_at desc'],
		];

		$matters = $this->query_objs_ss($q, $q2);
		$result = ['matters' => $matters];
		if (empty($matters)) {
			$result['total'] = 0;
		} else {
			$q[0] = 'count(*)';
			$result['total'] = $this->query_val_ss($q);
		}

		return $result;
	}
}