<?php
/**
 *
 */
class site_model extends \TMS_MODEL {
	/**
	 * 创建站点
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
	 *
	 */
	public function &byId($siteId, $options = array()) {
		$fields = empty($options['fields']) ? '*' : $options['fields'];
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : '';
		$q = [
			$fields,
			'xxt_site',
			"id='$siteId'",
		];
		if (($site = $this->query_obj_ss($q)) && !empty($cascaded)) {
			$cascaded = explode(',', $cascaded);
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

		return $site;
	}
	/**
	 * 指定用户管理的站点
	 */
	public function &byUser($userId) {
		/* 当前用户管理的站点 */
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
	 * 站点是否已经被关注
	 */
	public function isSubscribedBySite($targetId, $mySiteId) {
		$q = [
			'count(*)',
			'xxt_site_subscriber',
			["siteid" => $targetId, "from_siteid" => $mySiteId],
		];

		$cnt = (int) $this->query_val_ss($q);

		return $cnt > 0;
	}
	/**
	 * 建立指定站点间的关注关系
	 */
	public function subscribe(&$user, &$target, &$subscriber) {
		if ($this->isSubscribedBySite($target->id, $subscriber->id)) {
			return false;
		}
		$rel = new \stdClass;
		$rel->siteid = $target->id;
		$rel->site_name = $target->name;
		$rel->from_siteid = $subscriber->id;
		$rel->from_site_name = $subscriber->name;
		$rel->creater = $user->id;
		$rel->creater_name = $user->name;
		$rel->subscribe_at = time();

		$rel->id = $this->insert('xxt_site_subscriber', $rel, true);

		return $rel;
	}
	/**
	 *
	 */
	public function subscriber($siteId) {
		$q = [
			'*',
			'xxt_site_subscriber',
			"siteid='$siteId'",
		];
		$q2 = ['o' => 'subscribe_at desc'];

		$subscribers = $this->query_objs_ss($q, $q2);

		return $subscribers;
	}
	/**
	 * 推送素材给关注站点
	 */
	public function pushToSubscriber(&$matter, &$user) {
		$siteId = $matter->siteid;
		$subscribers = $this->subscriber($siteId);
		$current = time();
		foreach ($subscribers as $subscriber) {
			$subscription = new \stdClass;
			$subscription->siteid = $subscriber->from_siteid;
			$subscription->put_at = $current;
			$subscription->from_siteid = $subscriber->siteid;
			$subscription->from_site_name = $subscriber->site_name;
			$subscription->matter_id = $matter->id;
			$subscription->matter_type = $matter->type;
			$subscription->matter_title = $matter->title;
			$subscription->matter_pic = $matter->pic;
			$subscription->matter_summary = $matter->summary;

			$this->insert('xxt_site_subscription', $subscription, false);
		}

		return count($subscribers);
	}
}