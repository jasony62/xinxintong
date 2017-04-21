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
			["id" => $siteId, 'state' => 1],
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
	 * 获得指定团队的个人关注用户
	 */
	public function subscriber($siteId, $page = 1, $size = 10) {
		$q = [
			'*',
			'xxt_site_subscriber',
			["siteid" => $siteId],
		];

		if (empty($page) || empty($size)) {
			$result = $this->query_objs_ss($q);
		} else {
			$result = new \stdClass;
			$q2 = ['o' => 'subscribe_at desc'];
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];

			$result->subscribers = $this->query_objs_ss($q, $q2);

			$q[0] = 'count(*)';
			$result->total = $this->query_val_ss($q);
		}

		return $result;
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
	public function friendBySite($siteId, $page = 1, $size = 10) {
		$result = new \stdClass;

		$siteId = $this->escape($siteId);
		$q = [
			'*',
			'xxt_site_friend',
			"siteid='$siteId' and subscribe_at<>0",
		];
		if (empty($page) || empty($size)) {
			$result = $this->query_objs_ss($q);
		} else {
			$q2 = ['o' => 'subscribe_at desc', 'r' => ['o' => ($page - 1) * $size, 'l' => $size]];

			$result->subscribers = $this->query_objs_ss($q, $q2);

			$q[0] = 'count(*)';
			$result->total = $this->query_val_ss($q);
		}

		return $result;
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
	 * 推送素材给关注团队的站点用户
	 *
	 * @param object $oSite
	 * @param object $oMatter
	 */
	public function pushToClient($oSite, &$oMatter) {
		$subscribers = $this->subscriber($oSite->id, null, null);
		/* @todo  这种实现方式有问题，如果用户数量太多，性能上不可接受 */
		if (count($subscribers) === 0) {
			return 0;
		}
		$current = time();

		foreach ($subscribers as $subscriber) {
			$q = [
				'id',
				'xxt_site_subscription',
				['matter_id' => $oMatter->id, 'matter_type' => $oMatter->type, 'userid' => $subscriber->userid],
			];
			if ($rel = $this->query_obj_ss($q)) {
				$subscription = new \stdClass;
				$subscription->site_name = $oSite->name;
				$subscription->put_at = $current;
				$subscription->nickname = $subscriber->nickname;
				$subscription->matter_title = $oMatter->title;
				$subscription->matter_pic = $oMatter->pic;
				$subscription->matter_summary = $oMatter->summary;

				$this->update('xxt_site_subscription', $subscription, ['id' => $rel->id]);
			} else {
				$subscription = new \stdClass;
				$subscription->siteid = $oSite->id;
				$subscription->site_name = $oSite->name;
				$subscription->put_at = $current;
				$subscription->userid = $subscriber->userid;
				$subscription->nickname = $subscriber->nickname;
				$subscription->matter_id = $oMatter->id;
				$subscription->matter_type = $oMatter->type;
				$subscription->matter_title = $oMatter->title;
				$subscription->matter_pic = $oMatter->pic;
				$subscription->matter_summary = $oMatter->summary;

				$this->insert('xxt_site_subscription', $subscription, false);
			}
		}
		/* 给关注团队的站点用户发送通知 */
		if (!isset($notice)) {
			$notice = $this->model('site\notice')->byName('platform', 'site.home.publish.client');
			if ($notice) {
				$tmplConfig = $this->model('matter\tmplmsg\config')->byId($notice->tmplmsg_config_id, ['cascaded' => 'Y']);
				if (isset($tmplConfig->tmplmsg)) {
					$params = $this->_buildPublishNotice($tmplConfig, $oSite, $oMatter);
					$modelTmplBat = $this->model('matter\tmplmsg\batch');
					$creater = new \stdClass;
					$creater->src = 'pl';
					$modelTmplBat->send($oSite->id, $tmplConfig->msgid, $creater, $subscribers, $params, ['event_name' => 'site.home.publish.client', 'send_from' => 'site:' . $oSite->id]);
				}
			}
		}

		return count($subscribers);
	}
	/**
	 * 推送素材给关注团队的团队
	 *
	 * @param object $oSite
	 * @param object $oMatter
	 *
	 */
	public function pushToFriend($oSite, &$oMatter) {
		$friends = $this->friendBySite($oSite->id, null, null);
		$current = time();
		foreach ($friends as $friend) {
			$q = [
				'id',
				'xxt_site_friend_subscription',
				['matter_id' => $oMatter->id, 'matter_type' => $oMatter->type, 'from_siteid' => $friend->from_siteid],
			];
			if ($rel = $this->query_obj_ss($q)) {
				$subscription = new \stdClass;
				$subscription->site_name = $oSite->name;
				$subscription->put_at = $current;
				$subscription->from_site_name = $friend->from_site_name;
				$subscription->matter_title = $oMatter->title;
				$subscription->matter_pic = $oMatter->pic;
				$subscription->matter_summary = $oMatter->summary;

				$this->update('xxt_site_friend_subscription', $subscription, ['id' => $rel->id]);
			} else {
				$subscription = new \stdClass;
				$subscription->siteid = $oSite->id;
				$subscription->site_name = $oSite->name;
				$subscription->put_at = $current;
				$subscription->from_siteid = $friend->from_siteid;
				$subscription->from_site_name = $friend->from_site_name;
				$subscription->matter_id = $oMatter->id;
				$subscription->matter_type = $oMatter->type;
				$subscription->matter_title = $oMatter->title;
				$subscription->matter_pic = $oMatter->pic;
				$subscription->matter_summary = $oMatter->summary;

				$this->insert('xxt_site_friend_subscription', $subscription, false);
			}
			/* 给关注团队的团队用户发送通知 */
			if (!isset($notice)) {
				$notice = $this->model('site\notice')->byName('platform', 'site.home.publish.friend');
				if ($notice) {
					$tmplConfig = $this->model('matter\tmplmsg\config')->byId($notice->tmplmsg_config_id, ['cascaded' => 'Y']);
					if (isset($tmplConfig->tmplmsg)) {
						$params = $this->_buildPublishNotice($tmplConfig, $oSite, $oMatter);
					} else {
						$params = false;
					}
				}
			}
			if (isset($tmplConfig->msgid) && $params) {
				$this->_notifyPublishFriend($oSite->id, $friend->from_siteid, $tmplConfig->msgid, $params);
			}
		}

		return count($friends);
	}
	/**
	 * 构造事件通知
	 */
	private function _buildPublishNotice($tmplConfig, $oFromSite, $oMatter) {
		$params = new \stdClass;

		foreach ($tmplConfig->tmplmsg->params as $param) {
			$mapping = $tmplConfig->mapping->{$param->pname};
			if ($mapping->src === 'matter') {
				if (isset($oApp->{$mapping->id})) {
					$value = $oApp->{$mapping->id};
				}
			} else if ($mapping->src === 'text') {
				$value = $mapping->name;
			}
			!isset($value) && $value = '';
			$params->{$param->pname} = $value;
		}
		!empty($oMatter->entryUrl) && $params->url = $oMatter->entryUrl;

		return $param;
	}
	/**
	 * 给关注团队的管理员用户发送通知
	 */
	private function _notifyPublishFriend($bySiteId, $friendSiteId, $tmplmsgId, &$params) {
		// 团队下的所有管理员用户
		$admins = $this->model('site\admin')->bySite($bySiteId);

		if (count($admins)) {
			$receivers = [];
			foreach ($admins as $admin) {
				$receiver = new \stdClass;
				$receiver->userid = $admin->uid;
				$receivers[] = $receiver;
			}

			$modelTmplBat = $this->model('matter\tmplmsg\plbatch');
			$modelTmplBat->send($bySiteId, $tmplmsgId, $receivers, $params, ['event_name' => 'site.home.publish.friend', 'send_from' => 'site:' . $bySiteId]);
		}

		return [true];
	}
	/**
	 * 返回建立了关注关系的团队可以看到的素材
	 *
	 * @param string|array $friendIds 建立关注关系的团队id列表
	 */
	public function matterByFriend($friendIds, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 10];

		$result = new \stdClass;

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

		$result->matters = $this->query_objs_ss($q, $q2);

		$q[0] = 'count(*)';
		$result->total = $this->query_val_ss($q);

		return $result;
	}
}