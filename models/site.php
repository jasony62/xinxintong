<?php
/**
 *
 */
class site_model extends \TMS_MODEL {
	/**
	 * 创建团队
	 */
	public function create($data) {
		$account = \TMS_CLIENT::account();
		$siteid = $this->uuid($account->uid);
		$data['id'] = $siteid;
		$data['creater'] = $account->uid;
		$data['creater_name'] = $account->nickname;
		$data['create_at'] = time();
		$this->insert('xxt_site', $data, false);

		return $siteid;
	}
	/**
	 * 获得指定团队的信息
	 */
	public function &byId($siteId, $options = array()) {
		$fields = empty($options['fields']) ? '*' : $options['fields'];
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : '';
		$q = [
			$fields,
			'xxt_site',
			["id" => $siteId],
		];
		if (($site = $this->query_obj_ss($q)) && !empty($cascaded)) {
			$cascaded = explode(',', $cascaded);
			if (count($cascaded)) {
				$modelCode = \TMS_APP::M('code\page');
				foreach ($cascaded as $field) {
					if ($field === 'home_page_name') {
						$site->home_page = $modelCode->lastPublishedByName($siteId, $site->home_page_name, ['fields' => 'id,html,css,js']);
					} else if ($field === 'header_page_name' && $site->header_page_name) {
						$site->header_page = $modelCode->lastPublishedByName($siteId, $site->header_page_name, ['fields' => 'id,html,css,js']);
					} else if ($field === 'footer_page_name' && $site->footer_page_name) {
						$site->footer_page = $modelCode->lastPublishedByName($siteId, $site->footer_page_name, ['fields' => 'id,html,css,js']);
					} else if ($field === 'shift2pc_page_name') {
						$site->shift2pc_page = $modelCode->lastPublishedByName($siteId, $site->shift2pc_page_name, ['fields' => 'id,html,css,js']);
					}
				}
			}
		}

		return $site;
	}
	/**
	 * 指定用户参与管理的团队
	 */
	public function &byUser($userId) {
		/* 当前用户管理的团队 */
		$q = [
			'id,creater_name,create_at,name',
			'xxt_site s',
			"(creater='{$userId}' or exists(select 1 from xxt_site_admin sa where sa.siteid=s.id and uid='{$userId}')) and state=1",
		];
		$q2 = ['o' => 'create_at desc'];

		$sites = $this->query_objs_ss($q, $q2);

		return $sites;
	}
	/**
	 * 团队是否已经被指定用户关注
	 */
	public function isSubscribed($userid, $siteid) {
		$q = [
			'*',
			'xxt_site_subscriber',
			["siteid" => $siteid, "userid" => $userid],
		];
		$rel = $this->query_obj_ss($q);

		return $rel;
	}
	/**
	 * 访客用户关注团队
	 */
	public function subscribe(&$user, &$site) {
		if (false === ($rel = $this->isSubscribed($user->uid, $site->id))) {
			$newRel = new \stdClass;
			$newRel->siteid = $site->id;
			$newRel->site_name = $site->name;
			$newRel->userid = $user->uid;
			$newRel->nickname = $user->nickname;
			$newRel->subscribe_at = time();
			$newRel->unsubscribe_at = 0;

			$newRel->id = $this->insert('xxt_site_subscriber', $newRel, true);
		} else {
			$newRel = new \stdClass;
			$newRel->site_name = $site->name;
			$newRel->nickname = $user->nickname;
			$newRel->subscribe_at = time();
			$newRel->unsubscribe_at = 0;
			$this->update('xxt_site_subscriber', $newRel, ['id' => $rel->id]);
			$newRel->siteid = $rel->id;
			$newRel->site_name = $rel->site_name;
		}

		return $newRel;
	}
	/**
	 * 访客用户关注团队
	 */
	public function unsubscribe(&$user, &$site) {
		if ($rel = $this->isSubscribed($user->uid, $site->id)) {
			$rst = $this->update('xxt_site_subscriber', ['subscribe_at' => 0, 'unsubscribe_at' => time()], ['id' => $rel->id]);
		}

		return $rel;
	}
	/**
	 * 获得指定团队的站点关注用户
	 */
	public function subscriber($siteId) {
		$q = [
			'*',
			'xxt_site_subscriber',
			["siteid" => $siteId],
		];
		$q2 = ['o' => 'subscribe_at desc'];

		$subscribers = $this->query_objs_ss($q, $q2);

		return $subscribers;
	}
	/**
	 * 推送素材给关注团队的站点用户
	 *
	 * @param object $site
	 * @param object $matter
	 */
	public function pushToSubscriber($site, &$matter) {
		$subscribers = $this->subscriber($site->id);
		$current = time();
		foreach ($subscribers as $subscriber) {
			$q = [
				'id',
				'xxt_site_subscription',
				['matter_id' => $matter->id, 'matter_type' => $matter->type, 'userid' => $subscriber->userid],
			];
			if ($rel = $this->query_obj_ss($q)) {
				$subscription = new \stdClass;
				$subscription->site_name = $site->name;
				$subscription->put_at = $current;
				$subscription->nickname = $subscriber->nickname;
				$subscription->matter_title = $matter->title;
				$subscription->matter_pic = $matter->pic;
				$subscription->matter_summary = $matter->summary;

				$this->update('xxt_site_subscription', $subscription, ['id' => $rel->id]);
			} else {
				$subscription = new \stdClass;
				$subscription->siteid = $site->id;
				$subscription->site_name = $site->name;
				$subscription->put_at = $current;
				$subscription->userid = $subscriber->userid;
				$subscription->nickname = $subscriber->nickname;
				$subscription->matter_id = $matter->id;
				$subscription->matter_type = $matter->type;
				$subscription->matter_title = $matter->title;
				$subscription->matter_pic = $matter->pic;
				$subscription->matter_summary = $matter->summary;

				$this->insert('xxt_site_subscription', $subscription, false);
			}
		}

		return count($subscribers);
	}
	/**
	 * 返回建立了关注关系的团队
	 *
	 * @param string|array $friendIds
	 */
	public function byFriend($friendIds, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		is_string($friendIds) && $friendIds = explose(',', $friendIds);
		$where = 'subscribe_at<>0 and from_siteid in ("';
		$where .= implode('","', $friendIds);
		$where .= '")';

		$q = [
			$fields,
			'xxt_site_friend',
			$where,
		];
		$q2 = ['o' => 'subscribe_at desc'];
		$sites = $this->query_objs_ss($q, $q2);

		return $sites;
	}
	/**
	 * 获得指定团队的站点关注用户
	 */
	public function friendBySite($siteId) {
		$siteId = $this->escape($siteId);
		$q = [
			'*',
			'xxt_site_friend',
			"siteid='$siteId' and subscribe_at<>0",
		];
		$q2 = ['o' => 'subscribe_at desc'];

		$friends = $this->query_objs_ss($q, $q2);

		return $friends;
	}
	/**
	 * 团队是否已经被团队关注
	 */
	public function isFriend($targetId, $mySiteId) {
		$q = [
			'*',
			'xxt_site_friend',
			["siteid" => $targetId, "from_siteid" => $mySiteId],
		];

		$rel = $this->query_obj_ss($q);

		return $rel;
	}
	/**
	 * 团队关注团队
	 */
	public function subscribeBySite(&$user, &$target, &$subscriber) {
		if (false === ($rel = $this->isFriend($target->id, $subscriber->id))) {
			$newRel = new \stdClass;
			$newRel->siteid = $target->id;
			$newRel->site_name = $target->name;
			$newRel->from_siteid = $subscriber->id;
			$newRel->from_site_name = $subscriber->name;
			$newRel->creater = $user->id;
			$newRel->creater_name = $user->name;
			$newRel->subscribe_at = time();
			$newRel->unsubscribe_at = 0;

			$newRel->id = $this->insert('xxt_site_friend', $newRel, true);
		} else {
			$newRel = new \stdClass;
			$newRel->from_site_name = $subscriber->name;
			$newRel->creater = $user->id;
			$newRel->creater_name = $user->name;
			$newRel->subscribe_at = time();
			$newRel->unsubscribe_at = 0;
			$this->update('xxt_site_friend', $newRel, ['id' => $rel->id]);
			$newRel->siteid = $rel->id;
			$newRel->site_name = $rel->site_name;
		}

		return $newRel;
	}
	/**
	 * 取消团队关注团队
	 */
	public function unsubscribeBySite($siteId, $friendId) {
		if ($rel = $this->isFriend($siteId, $friendId)) {
			$rst = $this->update('xxt_site_friend', ['subscribe_at' => 0, 'unsubscribe_at' => time()], ['id' => $rel->id]);
		}

		return $rel;
	}
	/**
	 * 推送素材给关注团队的团队
	 *
	 * @param object $site
	 * @param object $matter
	 */
	public function pushToFriend($site, &$matter) {
		$friends = $this->friendBySite($site->id);
		$current = time();
		foreach ($friends as $friend) {
			$q = [
				'id',
				'xxt_site_friend_subscription',
				['matter_id' => $matter->id, 'matter_type' => $matter->type, 'from_siteid' => $friend->from_siteid],
			];
			if ($rel = $this->query_obj_ss($q)) {
				$subscription = new \stdClass;
				$subscription->site_name = $site->name;
				$subscription->put_at = $current;
				$subscription->from_site_name = $friend->from_site_name;
				$subscription->matter_title = $matter->title;
				$subscription->matter_pic = $matter->pic;
				$subscription->matter_summary = $matter->summary;

				$this->update('xxt_site_friend_subscription', $subscription, ['id' => $rel->id]);
			} else {
				$subscription = new \stdClass;
				$subscription->siteid = $site->id;
				$subscription->site_name = $site->name;
				$subscription->put_at = $current;
				$subscription->from_siteid = $friend->from_siteid;
				$subscription->from_site_name = $friend->from_site_name;
				$subscription->matter_id = $matter->id;
				$subscription->matter_type = $matter->type;
				$subscription->matter_title = $matter->title;
				$subscription->matter_pic = $matter->pic;
				$subscription->matter_summary = $matter->summary;

				$this->insert('xxt_site_friend_subscription', $subscription, false);
			}
		}

		return count($friends);
	}
	/**
	 * 返回建立了关注关系的团队可以看到的素材
	 *
	 * @param string|array $friendIds 建立关注关系的团队id列表
	 */
	public function matterByFriend($friendIds, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 30];

		is_string($friendIds) && $friendIds = explose(',', $friendIds);
		$where = 'from_siteid in ("';
		$where .= implode('","', $friendIds);
		$where .= '")';

		$q = [
			$fields,
			'xxt_site_friend_subscription',
			$where,
		];
		$q2 = ['o' => 'put_at desc', 'r' => ['o' => ($page['at'] - 1) * $page['size'], 'l' => $page['size']]];

		$sites = $this->query_objs_ss($q, $q2);

		return $sites;
	}
}