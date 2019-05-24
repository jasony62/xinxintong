<?php
namespace matter;

class log_model extends \TMS_MODEL {
	/**
	 * 记录访问素材日志
	 */
	public function addMatterRead($siteId, &$user, $matter, $client, $shareby, $search, $referer, $options = []) {
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
		if (isset($options['rid'])) {
			$operation->data = new \stdClass;
			$operation->data->rid = $options['rid'];
		}
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
			['l.matter_type' => $type, 'l.matter_id' => $id],
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
	public function addShareAction($siteId, $shareid, $shareto, $shareby, &$user, &$matter, &$client, $referer = '', $shareUrl = '') {
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
		$log['share_url'] = !empty($shareUrl) ? urldecode($shareUrl) : $referer;

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
	private function writeUserAction($siteId, $oUser, $actionAt, $actionName, $original_logid) {
		$d = [];
		$d['siteid'] = $siteId;
		$d['userid'] = $oUser->userid;
		$d['nickname'] = $this->escape($oUser->nickname);
		$d['action_at'] = $actionAt;
		$d['original_logid'] = $original_logid;
		switch ($actionName) {
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

		switch ($actionName) {
		case 'R':
			$this->update("update xxt_site_account set read_num=read_num+1,last_active=$actionAt where siteid='$siteId' and uid='$oUser->userid'");
			break;
		case 'SF':
			$this->update("update xxt_site_account set share_friend_num=share_friend_num+1,last_active=$actionAt where siteid='$siteId' and uid='$oUser->userid'");
			break;
		case 'ST':
			$this->update("update xxt_site_account set share_timeline_num=share_timeline_num+1,last_active=$actionAt where siteid='$siteId' and uid='$oUser->userid'");
			break;
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
	public function addUserMatterOp($siteId, $oUser, $oMatter, $oOperation, $client, $referer = '') {
		// 避免数据库双机同步延迟问题
		$this->setOnlyWriteDbConn(true);
		// 素材累积执行指定操作的次数
		$q = [
			'id,matter_op_num',
			'xxt_log_user_matter',
			['matter_id' => $oMatter->id, 'matter_type' => $oMatter->type, 'operation' => $oOperation->name, 'matter_last_op' => 'Y'],
		];
		$matterOpNum = $this->query_objs_ss($q);
		/* 并发情况下有可能产生多条数据 */
		if (count($matterOpNum)) {
			$lastOpNum = 0;
			foreach ($matterOpNum as $mon) {
				$this->update('xxt_log_user_matter', ['matter_last_op' => 'N'], ['id' => $mon->id]);
				$mon->matter_op_num > $lastOpNum && $lastOpNum = $mon->matter_op_num;
			}
			$matterOpNum = (int) $lastOpNum + count($matterOpNum);
		} else {
			$matterOpNum = 1;
		}
		// 用户对指定素材累积执行指定操作的次数
		$q = [
			'id,user_op_num',
			'xxt_log_user_matter',
			['userid' => $oUser->userid, 'matter_id' => $oMatter->id, 'matter_type' => $oMatter->type, 'operation' => $oOperation->name, 'user_last_op' => 'Y'],
		];
		if ($userOpNum = $this->query_obj_ss($q)) {
			$this->update('xxt_log_user_matter', ['user_last_op' => 'N'], ['id' => $userOpNum->id]);
			$userOpNum = (int) $userOpNum->user_op_num + 1;
		} else {
			$userOpNum = 1;
		}
		// 新建日志
		$aNewLog = [];
		$aNewLog['siteid'] = $siteId;
		$aNewLog['userid'] = $oUser->userid;
		$aNewLog['nickname'] = $this->escape($oUser->nickname);
		$aNewLog['matter_id'] = $oMatter->id;
		$aNewLog['matter_type'] = $oMatter->type;
		$aNewLog['matter_title'] = $this->escape($oMatter->title);
		if (!empty($oMatter->mission_id)) {
			$aNewLog['mission_id'] = $oMatter->mission_id;
			if (!empty($oMatter->mission_title)) {
				$aNewLog['mission_title'] = $this->escape($oMatter->mission_title);
			} else {
				$mission = $this->M('matter\mission')->byId($oMatter->mission_id, ['fields' => 'title']);
				$aNewLog['mission_title'] = $this->escape($mission->title);
			}
		}
		$aNewLog['user_agent'] = $client->agent;
		$aNewLog['client_ip'] = isset($client->ip) ? $client->ip : '';
		$aNewLog['referer'] = $referer;
		$aNewLog['operation'] = $oOperation->name;
		$aNewLog['operate_at'] = isset($oOperation->at) ? $oOperation->at : time();
		if (isset($oOperation->data)) {
			if (is_string($oOperation->data)) {
				$aNewLog['operate_data'] = $this->escape($oOperation->data);
			} else {
				$aNewLog['operate_data'] = $this->escape($this->toJson($oOperation->data));
			}
		}
		$aNewLog['matter_last_op'] = 'Y';
		$aNewLog['matter_op_num'] = $matterOpNum;
		$aNewLog['user_last_op'] = 'Y';
		$aNewLog['user_op_num'] = $userOpNum;

		$logid = $this->insert('xxt_log_user_matter', $aNewLog, true);

		return $logid;
	}
	/**
	 * 查询用户操作素材日志
	 */
	public function &listUserMatterOp($matterId, $matterType, $options = [], $page, $size) {
		$result = new \stdClass;
		$q = [
			'l.userid,l.nickname,l.operation,l.operate_at,l.user_op_num,l.matter_op_num',
			'xxt_log_user_matter l',
			"l.matter_type='" . $matterType . "' and l.matter_id='" . $matterId . "'",
		];

		if ($matterType === 'enroll') {
			$q[0] = 'l.userid,u.nickname,l.operation,l.operate_at,l.user_op_num,l.matter_op_num';
			$q[1] .= ',xxt_enroll_user u';
			$q[2] .= " and u.userid = l.userid and u.rid = 'ALL' and u.aid = l.matter_id";
		}

		if (!empty($options['byUser'])) {
			if ($matterType === 'enroll') {
				$q[2] .= " and u.nickname like '%" . $options['byUser'] . "%'";
			} else {
				$q[2] .= " and l.nickname like '%" . $options['byUser'] . "%'";
			}
		}
		if (!empty($options['byOp']) && strcasecmp($options['byOp'], 'all') !== 0) {
			$q[2] .= " and l.operation = '" . $options['byOp'] . "'";
		}
		if (!empty($options['byRid'])) {
			$q[2] .= " and l.operate_data like '%" . '"rid":"' . $options['byRid'] . '"' . "%'";
		}

		/**
		 * 分页数据
		 */
		$q2 = [
			'o' => 'l.operate_at desc',
		];
		if (!empty($page) && !empty($size)) {
			$q2['r'] = [
				'o' => (($page - 1) * $size),
				'l' => $size,
			];
		}

		$result->logs = $this->query_objs_ss($q, $q2);

		$q[0] = 'count(*)';
		$result->total = $this->query_val_ss($q);

		return $result;
	}
	/**
	 * 查询用户操作素材日志
	 */
	public function &listMatterOp($matterId, $matterType, $options = [], $page, $size) {
		$result = new \stdClass;
		$q = [
			'id,l.operator userid,l.operator_name nickname,l.operation,l.operate_at',
			'xxt_log_matter_op l',
			"l.matter_type='" . $matterType . "' and l.matter_id='" . $matterId . "'",
		];

		if (!empty($options['byUser'])) {
			$q[2] .= " and l.operator_name like '%" . $options['byUser'] . "%'";
		}
		if (!empty($options['byOp'])) {
			$q[2] .= " and l.operation = '" . $options['byOp'] . "'";
		}
		if (!empty($options['byRid'])) {
			$q[2] .= " and l.data like '%" . '"rid":"' . $options['byRid'] . '"' . "%'";
		}
		if (!empty($options['startAt'])) {
			$q[2] .= " and l.operate_at > {$options['startAt']}";
		}
		if (!empty($options['endAt'])) {
			$q[2] .= " and l.operate_at < {$options['endAt']}";
		}

		/**
		 * 分页数据
		 */
		$q2 = [
			'o' => 'l.operate_at desc',
		];
		if (!empty($page) && !empty($size)) {
			$q2['r'] = [
				'o' => (($page - 1) * $size),
				'l' => $size,
			];
		}

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
	public function matterOp($siteId, $user, $matter, $op, $data = null) {
		// 避免数据库双机同步延迟问题
		$this->setOnlyWriteDbConn(true);

		$q = [
			'*',
			'xxt_log_matter_op',
			['siteid' => $siteId, 'operator' => $user->id, 'matter_type' => $matter->type, 'matter_id' => $matter->id, 'user_last_op' => 'Y'],
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
				['matter_type' => $matter->type, 'matter_id' => $matter->id, 'last_op' => 'Y']
			);
		} else if ($op !== 'C') {
			/* 更新操作记录，需要将之前的操作设置为非最后操作 */
			$this->update(
				'xxt_log_matter_op',
				[
					'last_op' => 'N',
				],
				['siteid' => $siteId, 'matter_type' => $matter->type, 'matter_id' => $matter->id, 'last_op' => 'Y']
			);
		}
		/* 更新当前用户的最后一次操作记录 */
		$this->update(
			'xxt_log_matter_op',
			[
				'user_last_op' => 'N',
			],
			['siteid' => $siteId, 'operator' => $user->id, 'matter_type' => $matter->type, 'matter_id' => $matter->id, 'user_last_op' => 'Y']
		);
		// 记录新日志，或更新日志
		$filterOp = ['C', 'transfer', 'updateData', 'updateTask', 'add', 'removeData', 'restoreData', 'verify.all', 'verify.batch'];
		if ($userLastLog === false || in_array($userLastLog->operation, $filterOp) || $current > $userLastLog->operate_at + 600) {
			/* 两次更新操作的间隔超过10分钟，产生新日志 */
			$d = array();
			$d['siteid'] = $siteId;
			$d['operator'] = $user->id;
			$d['operator_name'] = $this->escape($user->name);
			$d['operator_src'] = $user->src;
			$d['operate_at'] = $current;
			$d['operation'] = $op;
			$d['matter_id'] = $matter->id;
			$d['matter_type'] = $matter->type;
			$d['matter_title'] = $this->escape($matter->title);
			!empty($matter->summary) && $d['matter_summary'] = $this->escape($matter->summary);
			!empty($matter->pic) && $d['matter_pic'] = $this->escape($matter->pic);
			!empty($matter->scenario) && $d['matter_scenario'] = $matter->scenario;
			$d['last_op'] = 'Y';
			$d['user_last_op'] = 'Y';
			if (!empty($data)) {
				if (is_object($data) || is_array($data)) {
					$d['data'] = $this->escape($this->toJson($data));
				} else {
					$d['data'] = $this->escape($data);
				}
			}
			$logid = $this->insert('xxt_log_matter_op', $d, true);
		} else {
			/* 更新之前的日志 */
			$d = array();
			$d['operator_name'] = $this->escape($user->name);
			$d['operate_at'] = $current;
			$d['operation'] = $op;
			$d['matter_title'] = $this->escape($matter->title);
			!empty($matter->summary) && $d['matter_summary'] = $this->escape($matter->summary);
			!empty($matter->pic) && $d['matter_pic'] = $this->escape($matter->pic);
			!empty($matter->scenario) && $d['matter_scenario'] = $matter->scenario;
			$d['last_op'] = 'Y';
			$d['user_last_op'] = 'Y';
			if (!empty($data)) {
				if (is_object($data) || is_array($data)) {
					$d['data'] = $this->escape($this->toJson($data));
				} else {
					$d['data'] = $this->escape($data);
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
			"siteid='" . $this->escape($siteId) . "' and last_op='Y' and operation<>'D' and operation<>'Recycle' and matter_type <> 'site'",
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
		if (isset($options['byType'])) {
			$q[2] .= " and matter_type='" . $options['byType'] . "'";
		}
		if (isset($options['scenario'])) {
			$q[2] .= " and matter_scenario='" . $options['scenario'] . "'";
		}
		if (isset($options['byTitle'])) {
			$q[2] .= " and matter_title like '%" . $options['byTitle'] . "%'";
		}

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
	 * 站点内最近删除的素材
	 */
	public function &recycleMatters($siteId, $user, $options = array()) {
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
			"last_op='Y' and operation='Recycle'",
		];
		!empty($options['byType']) && $q[2] .= " and matter_type = '" . $this->escape($options['byType']) . "'";
		!empty($options['byTitle']) && $q[2] .= " and matter_title like '%" . $this->escape($options['byTitle']) . "%'";
		if (!empty($siteId)) {
			$q[2] .= " and siteid = '" . $this->escape($siteId) . "'";
		} else {
			if ($mySites = $this->model('site')->byUser($user->id, ['fields' => 'id'])) {
				$siteArr = [];
				foreach ($mySites as $site) {
					$siteArr[] = $site->id;
				}
				$site = "('";
				$site .= implode("','", $siteArr);
				$site .= "')";
				$q[2] .= " and siteid in $site";
			}
		}

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
	 * 汇总各类日志，形成用户完整的踪迹用于展示用户详情的发送消息列表记录
	 * $total 用以分页的总数
	 * $sum 实际上的总记录数
	 */
	public function track($site, $openid, $page = 1, $size = 30) {
		$q = array(
			'creater,create_at,content,matter_id,matter_type',
			'xxt_log_mpsend',
			"mpid='$site' and openid='$openid'",
		);
		$q2 = array(
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
			'o' => 'create_at desc',
		);

		$sendlogs = $this->query_objs_ss($q, $q2);

		$q[0] = 'count(*)';
		$total_s = $this->query_val_ss($q);

		$q = array(
			'create_at,data content',
			'xxt_log_mpreceive',
			"mpid='$site' and openid='$openid' and type='text'",
		);
		$q2 = array(
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
			'o' => 'create_at desc',
		);

		$recelogs = $this->query_objs_ss($q, $q2);

		$q[0] = 'count(*)';
		$total_r = $this->query_val_ss($q);
		//确定分页的总数以记录多的表的总数为准
		$total = ($total_s >= $total_r) ? $total_s : $total_r;
		//实际的总数
		$sum = $total_s + $total_r;
		$logs = array_merge($sendlogs, $recelogs);
		/**
		 * order by create_at
		 */
		usort($logs, function ($a, $b) {
			return $b->create_at - $a->create_at;
		});

		$result = new \stdClass;
		$result->total = $total;
		$result->sum = $sum;
		$result->data = $logs;

		return $result;
	}
	/**
	 * 记录所有发送给用户的消息
	 */
	public function send($site, $openid, $groupid, $content, $matter) {
		$i['mpid'] = $site;
		$i['siteid'] = $site;
		$i['creater'] = \TMS_CLIENT::get_client_uid();
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
	 * 群发消息发送日志
	 */
	public function mass($sender, $site, $matterId, $matterType, $message, $msgid, $result) {
		$log = array(
			'mpid' => $site,
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
	 * 获取用户分享过的素材
	 */
	public function listUserShare($site = '', $users, $page = null, $size = null) {
		$user = "'" . implode("','", $users) . "'";
		$q = [
			'siteid,max(share_at) share_at,matter_id,matter_type,matter_title,userid,nickname',
			'xxt_log_matter_share',
			"userid in ($user)",
		];
		if (!empty($site) && $site !== 'platform') {
			$site = $this->escape($site);
			$q[2] .= " and siteid = '$site'";
		}

		$q2['g'] = "userid,matter_id,matter_type";
		$q2['o'] = "max(share_at) desc";
		if (!empty($page) && !empty($size)) {
			$q2['r']['o'] = ($page - 1) * $size;
			$q2['r']['l'] = $size;
		}

		$matters = $this->query_objs_ss($q, $q2);
		$q[0] = "count(distinct userid,matter_id,matter_type)";
		$total = (int) $this->query_val_ss($q);

		$data = new \stdClass;
		$data->matters = $matters;
		$data->total = $total;

		return $data;
	}
	/**
	 * 获取我的分享信息
	 */
	public function getMyShareLog($oUserid, $matterType, $matterId, $orderBy = 'read', $page = null, $size = null) {
		$q = [];
		$q2 = [];
		switch ($orderBy) {
		case 'shareF':
			$q[0] = 's.userid,count(*) num,a.nickname,a.headimgurl';
			$q[1] = 'xxt_log_matter_share s,xxt_site_account a';
			$q[2] = "s.matter_id = '{$matterId}' and s.matter_type = '{$matterType}' and s.matter_shareby like '" . $oUserid . "_%' and s.share_to ='F' and s.userid = a.uid";

			$q2['g'] = 's.userid';
			$q2['o'] = 'num desc,s.share_at desc';

			if (!empty($page) && !empty($size)) {
				$q2['r']['o'] = ($page - 1) * $size;
				$q2['r']['l'] = $size;
			}

			$users = $this->query_objs_ss($q, $q2);
			$q[0] = "count(distinct s.userid)";
			$total = (int) $this->query_val_ss($q);

			break;
		case 'shareT':
			$q[0] = 's.userid,count(*) num,a.nickname,a.headimgurl';
			$q[1] = 'xxt_log_matter_share s,xxt_site_account a';
			$q[2] = "s.matter_id = '{$matterId}' and s.matter_type = '{$matterType}' and s.matter_shareby like '" . $oUserid . "_%' and s.share_to ='T' and s.userid = a.uid";

			$q2['g'] = 's.userid';
			$q2['o'] = 'num desc,s.share_at desc';

			if (!empty($page) && !empty($size)) {
				$q2['r']['o'] = ($page - 1) * $size;
				$q2['r']['l'] = $size;
			}

			$users = $this->query_objs_ss($q, $q2);
			$q[0] = "count(distinct s.userid)";
			$total = (int) $this->query_val_ss($q);

			break;
		case 'attractRead':
			$q = "select r.userid,(select count(*) from xxt_log_matter_read r1 where r1.matter_id='" . $this->escape($matterId) . "' and r1.matter_type='" . $this->escape($matterType) . "' and r1.matter_shareby like CONCAT(r.userid,'_%')) as num,a.nickname,a.headimgurl from xxt_log_matter_read r,xxt_site_account a where r.matter_id = '{$matterId}' and r.matter_type = '{$matterType}' and r.matter_shareby like '" . $oUserid . "_%' and r.userid = a.uid group by r.userid order by num desc,r.read_at desc";

			if (!empty($page) && !empty($size)) {
				$q .= " limit " . ($page - 1) * $size . "," . $size;
			}

			$users = $this->query_objs($q);
			$q = "select count(distinct r.userid) from xxt_log_matter_read r,xxt_site_account a where r.matter_id = '{$matterId}' and r.matter_type = '{$matterType}' and r.matter_shareby like '" . $oUserid . "_%' and r.userid = a.uid";
			$total = (int) $this->query_value($q);

			break;
		default:
			$q[0] = 'r.userid,count(*) as num,a.nickname,a.headimgurl';
			$q[1] = 'xxt_log_matter_read r,xxt_site_account a';
			$q[2] = "r.matter_id = '{$matterId}' and r.matter_type = '{$matterType}' and r.matter_shareby like '" . $oUserid . "_%' and r.userid = a.uid";

			$q2['g'] = 'r.userid';
			$q2['o'] = 'num desc,r.read_at desc';

			if (!empty($page) && !empty($size)) {
				$q2['r']['o'] = ($page - 1) * $size;
				$q2['r']['l'] = $size;
			}

			$users = $this->query_objs_ss($q, $q2);
			$q[0] = "count(distinct r.userid)";
			$total = (int) $this->query_val_ss($q);

			break;
		}

		// if ($users) {
		// 	foreach ($users as $user) {
		// 		if (!isset($user->read_sum)) {
		// 			$q = [
		// 				'count(*) read_sum',
		// 				'xxt_log_matter_read',
		// 				[],
		// 			];
		// 			$q[2]["matter_id"] = $matterId;
		// 			$q[2]["matter_type"] = $matterType;
		// 			$q[2]["matter_shareby"] = (object) ['op' => 'like', 'pat' => $oUserid . '_%'];
		// 			$q[2]["userid"] = $user->userid;

		// 			$rst = $this->query_obj_ss($q);
		// 			if ($rst) {
		// 				$user->read_sum = $rst->read_sum;
		// 			} else {
		// 				$user->read_sum = 0;
		// 			}
		// 		}
		// 		if (!isset($user->shareF_sum)) {
		// 			$q = [
		// 				'count(*) shareF_sum',
		// 				'xxt_log_matter_share',
		// 				[],
		// 			];
		// 			$q[2]["matter_id"] = $matterId;
		// 			$q[2]["matter_type"] = $matterType;
		// 			$q[2]["matter_shareby"] = (object) ['op' => 'like', 'pat' => $oUserid . '_%'];
		// 			$q[2]["userid"] = $user->userid;
		// 			$q[2]["share_to"] = 'F';

		// 			$rst = $this->query_obj_ss($q);
		// 			if ($rst) {
		// 				$user->shareF_sum = $rst->shareF_sum;
		// 			} else {
		// 				$user->shareF_sum = 0;
		// 			}
		// 		}
		// 		if (!isset($user->shareT_sum)) {
		// 			$q = [
		// 				'count(*) shareT_sum',
		// 				'xxt_log_matter_share',
		// 				[],
		// 			];
		// 			$q[2]["matter_id"] = $matterId;
		// 			$q[2]["matter_type"] = $matterType;
		// 			$q[2]["matter_shareby"] = (object) ['op' => 'like', 'pat' => $oUserid . '_%'];
		// 			$q[2]["userid"] = $user->userid;
		// 			$q[2]["share_to"] = 'T';

		// 			$rst = $this->query_obj_ss($q);
		// 			if ($rst) {
		// 				$user->shareT_sum = $rst->shareT_sum;
		// 			} else {
		// 				$user->shareT_sum = 0;
		// 			}
		// 		}
		// 		/*ta带来的阅读数是否加上ta自己的阅读数？？？*/
		// 		if (!isset($user->attractRead_sum)) {
		// 			$q = [
		// 				'count(*) attractRead_sum',
		// 				'xxt_log_matter_read',
		// 				[],
		// 			];
		// 			$q[2]["matter_id"] = $matterId;
		// 			$q[2]["matter_type"] = $matterType;
		// 			$q[2]["matter_shareby"] = (object) ['op' => 'like', 'pat' => $user->userid . '_%'];

		// 			$rst = $this->query_obj_ss($q);
		// 			if ($rst) {
		// 				$user->attractRead_sum = $rst->attractRead_sum;
		// 			} else {
		// 				$user->attractRead_sum = 0;
		// 			}
		// 		}
		// 	}
		// }

		$data = new \stdClass;
		$data->users = $users;
		$data->total = $total;

		return $data;
	}
	/*
		 * 查询用户最后一条行为记录
	*/
	public function lastByUser($matterId, $matterType, $userId, $options = []) {
		$fields = empty($options['fields']) ? '*' : $options['fields'];
		$q = [
			$fields,
			'xxt_log_user_matter',
			['userid' => $userId, 'matter_id' => $matterId, 'matter_type' => $matterType, 'user_last_op' => 'Y'],
		];
		if (!empty($options['byOp'])) {
			$q[2]['operation'] = $options['byOp'];
		}

		$logs = $this->query_objs_ss($q);

		return $logs;
	}
	/**
	 * 素材运营数据追踪
	 */
	public function operateStat($site, $matterId = '', $matterType = '', $options = []) {
		$fields = 'r.id,r.userid,r.nickname,r.matter_shareby,r.openid,r.matter_id,r.matter_type,max(r.read_at) readAt';
		$table = 'xxt_log_matter_read r';

		// 查询用户转发数和分享数
		$countRead = "select count(r2.id) from xxt_log_matter_read r2 where r2.matter_id = r.matter_id and r2.matter_type = r.matter_type and r2.siteid = '{$site}' and r2.userid = r.userid";
		$countShareF = "select count(s1.id) from xxt_log_matter_share s1 where s1.matter_id = r.matter_id and s1.matter_type = r.matter_type and s1.siteid = '{$site}' and s1.userid = r.userid and s1.share_to = 'F'";
		$countShareT = "select count(s2.id) from xxt_log_matter_share s2 where s2.matter_id = r.matter_id and s2.matter_type = r.matter_type and s2.siteid = '{$site}' and s2.userid = r.userid and s2.share_to = 'T'";

		$where = "r.siteid = '{$site}'";
		if (!empty($matterType) && !empty($matterId)) {
			$where .= " and r.matter_type = '{$matterType}' and r.matter_id = '{$matterId}'";
		} else if (!empty($matterType)) {
			$where .= " and r.matter_type = '{$matterType}'";
		}
		if (!empty($options['shareby'])) {
			if ($options['shareby'] !== 'N') {
				$where .= " and r.userid <> '{$options['shareby']}' and r.matter_shareby like '" . $options['shareby'] . "_%'";
				// $countRead .= " and r2.matter_shareby like '" . $options['shareby'] . "_%'";
				// $countShareF .= " and s1.matter_shareby like '" . $options['shareby'] . "_%'";
				// $countShareT .= " and s2.matter_shareby like '" . $options['shareby'] . "_%'";
			}
		} else {
			$where .= " and r.matter_shareby in ('','undefined')";
			// $countRead .= " and s1.matter_shareby in ('','undefined')";
			// $countShareF .= " and s1.matter_shareby in ('','undefined')";
			// $countShareT .= " and s2.matter_shareby in ('','undefined')";
		}

		if (!empty($options['start'])) {
			$where .= " and r.read_at > {$options['start']}";
			$countRead .= " and r2.read_at > {$options['start']}";
			$countShareF .= " and s1.share_at > {$options['start']}";
			$countShareT .= " and s2.share_at > {$options['start']}";
		}
		if (!empty($options['end'])) {
			$where .= " and r.read_at < {$options['end']}";
			$countRead .= " and r2.read_at < {$options['end']}";
			$countShareF .= " and s1.share_at < {$options['end']}";
			$countShareT .= " and s2.share_at < {$options['end']}";
		}
		if (!empty($options['byUser'])) {
			$where .= " and r.nickname like '%" . $options['byUser'] . "%'";
		}
		if (!empty($options['byUserId'])) {
			$where .= " and r.userid = '" . $options['byUserId'] . "'";
		}

		// 分组，排序
		if (empty($options['groupby']) || $options['groupby'] === 'N') {
			$groupby = " ";
		} else {
			$groupby = " group by " . $options['groupby'];
		}

		if (isset($options['orderby'])) {
			$orderby = " order by " . $options['orderby'];
		} else {
			$orderby = " order by shareFNum desc,shareTNum desc,r.id desc";
		}

		// 拼装sql
		$fields .= ",(" . $countRead . ") as readNum";
		$fields .= ",(" . $countShareF . ") as shareFNum";
		$fields .= ",(" . $countShareT . ") as shareTNum";
		$sql = 'select ' . $fields;
		$sql .= " from " . $table;
		$sql .= " where " . $where;
		$sql .= $groupby;
		$sql .= $orderby;

		if (isset($options['paging'])) {
			$sql .= " limit " . ($options['paging']['page'] - 1) * $options['paging']['size'];
			$sql .= " , " . $options['paging']['size'];
		}

		$q = [$sql, ''];
		$logs = $this->query_objs_ss($q);
		foreach ($logs as $log) {
			// 带来的阅读数和阅读人数
			$ttractReads = $this->userTtractRead($site, $log->userid, $log->matter_id, $log->matter_type, $options);
			$log->attractReaderNum = count($ttractReads);
			$attractReadNum = 0;
			foreach ($ttractReads as $re) {
				$attractReadNum += $re->num;
			}
			$log->attractReadNum = $attractReadNum;
		}

		$data = new \stdClass;
		$data->logs = $logs;
		$q1 = [
			"id",
			'xxt_log_matter_read r',
			$where,
		];
		if (!empty($options['groupby']) && $options['groupby'] !== 'N') {
			$p1 = ['g' => $options['groupby']];
		}
		$total = $this->query_objs_ss($q1, $p1);
		$data->total = count($total);

		return $data;
	}
	/**
	 * 查询用户带来的下一级阅读数和阅读人数
	 */
	private function userTtractRead($site, $logUid, $matterId, $matterType, $options) {
		$q = [
			'count(id) as num',
			'xxt_log_matter_read',
			"matter_id = '{$matterId}' and matter_type = '{$matterType}' and userid <> '{$logUid}' and siteid = '{$site}' and matter_shareby like '" . $logUid . "_%'",
		];
		if (!empty($options['start'])) {
			$q[2] .= " and read_at > {$options['start']}";
		}
		if (!empty($options['end'])) {
			$q[2] .= " and read_at < {$options['end']}";
		}

		$p = ['g' => 'userid'];
		$res = $this->query_objs_ss($q, $p);

		return $res;
	}
	/**
	 *
	 */
	public function userMatterAction($matterId, $matterType, $options, $page = '', $size = '') {
		if ($options['byOp'] === 'read') {
			$q = [
				'id,userid,nickname,read_at time,matter_shareby',
				'xxt_log_matter_read',
				"matter_type='" . $matterType . "' and matter_id='" . $matterId . "'",
			];

			if (!empty($options['start'])) {
				$q[2] .= " and read_at > {$options['start']}";
			}
			if (!empty($options['end'])) {
				$q[2] .= " and read_at < {$options['end']}";
			}

			$p = ['o' => 'read_at desc'];
		} else {
			$q = [
				'id,userid,nickname,share_at time,share_to,matter_shareby',
				'xxt_log_matter_share',
				"matter_type='" . $matterType . "' and matter_id='" . $matterId . "'",
			];

			if ($options['byOp'] === 'share.friend') {
				$q[2] .= " and share_to = 'F'";
			} else {
				$q[2] .= " and share_to = 'T'";
			}
			if (!empty($options['start'])) {
				$q[2] .= " and share_at > {$options['start']}";
			}
			if (!empty($options['end'])) {
				$q[2] .= " and share_at < {$options['end']}";
			}

			$p = ['o' => 'share_at desc'];
		}

		if (!empty($options['byUserId'])) {
			$q[2] .= " and userid = '" . $options['byUserId'] . "'";
		}
		// if (!empty($options['shareby'])) {
		// 	if ($key = strpos($options['shareby'], '_')) {
		// 		$options['shareby'] = substr($options['shareby'], 0, $key);
		// 	}
		// 	$q[2] .= " and matter_shareby like '" . $options['shareby'] . "_%'";
		// } else {
		// 	$q[2] .= " and matter_shareby in ('','undefined')";
		// }

		if (!empty($page) && !empty($size)) {
			$p['r'] = [
				'o' => (($page - 1) * $size),
				'l' => $size,
			];
		}

		$logs = $this->query_objs_ss($q, $p);
		foreach ($logs as $log) {
			$q1 = [
				'nickname',
				'xxt_log_matter_share',
				['shareid' => $log->matter_shareby],
			];
			if (!empty($log->matter_shareby)) {
				if ($res = $this->query_obj_ss($q1)) {
					$log->origin = $res->nickname;
				}
			}
			if (!isset($log->origin)) {
				$log->origin = '';
			}
		}

		$result = new \stdClass;
		$result->logs = $logs;
		$q[0] = 'count(*)';
		$result->total = $this->query_val_ss($q);

		return $result;
	}
	/**
	 * 操作素材行为列表
	 */
	public function listMatterAction($site = '', $matterType, $matterId, $options = []) {
		$fields = !empty($options['fields']) ? $options['fields'] : 'id,action_at,act_read,act_share_timeline,act_share_friend,original_logid';

		if (!empty($options['byEvent'])) {
			$result = $this->listMatterActionByEvent($site, $matterType, $matterId, $options['byEvent'], $options);
			return $result;
		}

		$q = [
			$fields,
			'xxt_log_matter_action',
			"matter_id = '{$matterId}' and matter_type = '{$matterType}'",
		];

		if (!empty($options['startAt'])) {
			$q[2] .= " and action_at > {$options['startAt']}";
		}
		if (!empty($options['endAt'])) {
			$q[2] .= " and action_at < {$options['endAt']}";
		}

		$p = ['o' => 'action_at desc'];
		if (!empty($options['paging'])) {
			$page = $options['paging']['page'];
			$size = $options['paging']['size'];
			$p['r'] = [
				'o' => (($page - 1) * $size),
				'l' => $size,
			];
		}

		$logs = $this->query_objs_ss($q, $p);
		foreach ($logs as $log) {
			if ($log->act_share_timeline > 0) {
				$log->event = 'shareT';
			} else if ($log->act_share_friend > 0) {
				$log->event = 'shareF';
			} else if ($log->act_read > 0) {
				$log->event = 'read';
			} else {
				$log->event = '未知';
			}

			$table = 'xxt_log_matter_read';
			if ($log->act_share_timeline > 0 || $log->act_share_friend > 0) {
				$table = 'xxt_log_matter_share';
			}
			// 查询记录详细信息
			$q2 = [
				'userid,nickname,matter_shareby',
				$table,
				['id' => $log->original_logid],
			];
			$logInfo = $this->query_obj_ss($q2);
			$log->userid = $logInfo->userid;
			$log->nickname = $logInfo->nickname;
			$log->matter_shareby = $logInfo->matter_shareby;
			// 查询来源用户
			if (strpos($logInfo->matter_shareby, '_') !== false) {
				$shareby = explode('_', $logInfo->matter_shareby);
				$originUserid = $shareby[0];
				$q3 = [
					'nickname',
					'xxt_site_account',
					['uid' => $originUserid],
				];
				$originUser = $this->query_obj_ss($q3);
				if ($originUser) {
					$log->origin_userid = $originUserid;
					$log->origin_nickname = $originUser->nickname;
				} else {
					$log->origin_userid = '';
					$log->origin_nickname = '未获取';
				}
			} else {
				$log->origin_userid = '';
				$log->origin_nickname = '';
			}
		}

		$result = new \stdClass;
		$result->logs = $logs;
		$q[0] = 'count(*)';
		$result->total = $this->query_val_ss($q);

		return $result;
	}
	/**
	 * 素材页面行为日志
	 */
	public function listMatterActionByEvent($site = '', $matterType, $matterId, $event, $options = []) {
		$fields = 'ma.matter_id,ma.matter_type,ma.id,ma.action_at,ma.act_read,ma.act_share_timeline,ma.act_share_friend,ma.original_logid,x.userid,x.nickname,x.matter_shareby,x.matter_shareby';

		$q = [
			$fields,
			'xxt_log_matter_action ma',
			"ma.matter_type = '{$matterType}' and ma.original_logid = x.id",
		];

		switch ($event) {
		case 'shareT':
			$q[1] .= ",xxt_log_matter_share x";
			$q[2] .= " and ma.act_share_timeline > 0";
			break;
		case 'shareF':
			$q[1] .= ",xxt_log_matter_share x";
			$q[2] .= " and ma.act_share_friend > 0";
			break;
		default:
			$q[1] .= ",xxt_log_matter_read x";
			$q[2] .= " and ma.act_read > 0";
			break;
		}

		// 如果是查询登记活动中的某个页面，并且是这个页面在整个活动下的所有记录
		if (!empty($options['assocMatter'])) {
			switch ($matterType) {
				case 'enroll.topic':
					$q[1] .= ",xxt_enroll_topic et";
					$q[2] .= " and et.aid = '{$options['assocMatter']}' and et.id = ma.matter_id";
					break;
				case 'enroll.cowork':
					$q[1] .= ",xxt_enroll_record er";
					$q[2] .= " and er.aid = '{$options['assocMatter']}' and er.id = ma.matter_id";
					break;
				default:
					$q[2] .= " and ma.matter_id = '{$options['assocMatter']}'";
					break;
			}
		} else {
			$q[2] .= " and ma.matter_id = '{$matterId}'";
		}
		if (!empty($options['startAt'])) {
			$q[2] .= " and ma.action_at > {$options['startAt']}";
		}
		if (!empty($options['endAt'])) {
			$q[2] .= " and ma.action_at < {$options['endAt']}";
		}

		$p = ['o' => 'ma.action_at desc'];
		if (!empty($options['paging'])) {
			$page = $options['paging']['page'];
			$size = $options['paging']['size'];
			$p['r'] = [
				'o' => (($page - 1) * $size),
				'l' => $size,
			];
		}

		$logs = $this->query_objs_ss($q, $p);
		foreach ($logs as $log) {
			if ($log->act_share_timeline > 0) {
				$log->event = 'shareT';
			} else if ($log->act_share_friend > 0) {
				$log->event = 'shareF';
			} else if ($log->act_read > 0) {
				$log->event = 'read';
			} else {
				$log->event = '未知';
			}

			// 查询来源用户
			if (strpos($log->matter_shareby, '_') !== false) {
				$shareby = explode('_', $log->matter_shareby);
				$originUserid = $shareby[0];
				$q3 = [
					'nickname',
					'xxt_site_account',
					['uid' => $originUserid],
				];
				$originUser = $this->query_obj_ss($q3);
				if ($originUser) {
					$log->origin_userid = $originUserid;
					$log->origin_nickname = $originUser->nickname;
				} else {
					$log->origin_userid = '';
					$log->origin_nickname = '未获取';
				}
			} else {
				$log->origin_userid = '';
				$log->origin_nickname = '';
			}
		}

		$result = new \stdClass;
		$result->logs = $logs;
		$q[0] = 'count(*)';
		$result->total = $this->query_val_ss($q);

		return $result;
	}
}