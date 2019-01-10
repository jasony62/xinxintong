<?php
namespace matter\enroll;
/**
 * 搜索
 */
class search_model extends \TMS_MODEL {
	/*
	 *
	 */
	public function listUserSearch($oApp, $oUser, $options= []) {
		$q = [
			'us.*,s.keyword',
			'xxt_enroll_user_search us,xxt_enroll_search s',
			"us.aid = '{$oApp->id}' and us.userid = '{$oUser->uid}' and us.state = 1 and us.search_id = s.id and s.state = 1"
		];

		$orderBy = 'us.last_use_at desc';
		if (!empty($options['orderBy'])) {
			switch ($options['orderBy']) {
					case 'useNum':
						$orderBy = 'us.used_num desc,us.last_use_at desc';
						break;
				}	
		}
		$p = ['o' => $orderBy];
		if (!empty($options['page']) && !empty($options['size'])) {
			$page = $options['page'];
			$size = $options['size'];
			$p['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		$userSearchs = $this->query_objs_ss($q, $p);

		// 获取被推荐的关键词
		$q2 = [
			'keyword',
			'xxt_enroll_search',
			['aid' => $oApp->id, 'agreed' => 'Y', 'state' => 1]
		];
		$p2 = ['o' => 'user_num desc,used_num desc'];
		if (!empty($options['page']) && !empty($options['size'])) {
			$page = $options['page'];
			$size = $options['size'];
			$p2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		$agreedSearchs = $this->query_objs_ss($q2, $p2);

		$searchs = new \stdClass;
		$searchs->userSearch = $userSearchs;
		$searchs->agreedSearch = $agreedSearchs;
		return $searchs;
	}
	/**
	 *
	 */
	public function addUserSearch($oApp, $oUser, $keyword) {
		$search = $this->byKeyword($oApp->id, $keyword, ['cascaded' => false]);
		if ($search === false) {
			$search = $this->addSearch($oApp, $keyword);
			$userSearch = false; // 是否有用户使用
		}
		// 用户是否已经使用了该关键词
		if (!isset($userSearch)) {
			$q = [
				'id',
				'xxt_enroll_user_search',
				['userid' => $oUser->uid, 'search_id' => $search->id, 'state' => 1]
			];
			$userSearch = $this->query_obj_ss($q);
		}
		$current = time();
		if ($userSearch === false) {
			$userSearch = new \stdClass;
			$userSearch->siteid = $oApp->siteid;
			$userSearch->aid = $oApp->id;
			$userSearch->userid = $oUser->uid;
			$userSearch->nickname = $this->escape($oUser->nickname);
			$userSearch->create_at = $current;
			$userSearch->last_use_at = $current;
			$userSearch->search_id = $search->id;
			$userSearch->used_num = 1;
			$userSearch->id = $this->insert('xxt_enroll_user_search', $userSearch, false);
			// 修改关键词的使用数据
			$this->update('xxt_enroll_search', 
				['user_num' => (object) ['op' => '+=', 'pat' => 1], 'used_num' => (object) ['op' => '+=', 'pat' => 1]], 
				['id' => $search->id]
			);
		} else {
			// 更新用户使用数据
			$this->update('xxt_enroll_user_search', 
				['last_use_at' => $current, 'used_num' => (object) ['op' => '+=', 'pat' => 1]], 
				['id' => $userSearch->id]
			);
			$this->update('xxt_enroll_search', ['used_num' => (object) ['op' => '+=', 'pat' => 1]], ['id' => $search->id]);
		}

		return ['userSearch' => $userSearch, 'search' => $search];
	}
	/*
	 *
	 */
	public function addSearch($oApp, $keyword) {
		$search = new \stdClass;
		$search->siteid = $oApp->siteid;
		$search->aid = $oApp->id;
		$search->keyword = $this->escape($keyword);
		$search->id = $this->insert('xxt_enroll_search', $search, true);

		return $search;
	}
	/*
	 *
	 */
	public function byKeyword($appId, $keyword, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : true;
		
		$q = [
			$fields,
			'xxt_enroll_search',
			["aid" => $appId, "keyword" => $keyword, "state" => 1]
		];
		$search = $this->query_obj_ss($q);
		if ($search && $cascaded === true && isset($search->id)) {
			$q2= [
				'*',
				'xxt_enroll_user_search',
				['search_id' => $search->id, 'state' => 1]
			];
			$p2 = ['o' => 'last_use_at desc'];
			$users = $this->query_objs_ss($q2, $p2);
			$search->users = $users;
		}

		return $search;
	}
	/*
	 *
	 */
	 public function bySearchId($searchId, $options = []) {
	 	$fields = isset($options['fields']) ? $options['fields'] : '*';
		
		$q = [
			$fields,
			'xxt_enroll_search',
			["id" => $searchId]
		];
		$search = $this->query_obj_ss($q);

		return $search;
	 }
}