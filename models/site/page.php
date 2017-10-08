<?php
namespace site;
/**
 * 站点自定义页
 */
class page_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &homeChannelBySite($siteId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_site_home_channel',
			['siteid' => $siteId],
		];
		if (!empty($options['home_group'])) {
			$q[2]['home_group'] = $options['home_group'];
		}
		$q2 = ['o' => 'seq'];
		$homeChannels = $this->query_objs_ss($q, $q2);

		return $homeChannels;
	}
	/**
	 * 在主页中添加频道
	 */
	public function &addHomeChannel(&$user, &$site, &$channel, $homeGroup) {
		$q = [
			'max(seq)',
			'xxt_site_home_channel',
			["siteid" => $site->id, 'home_group' => $homeGroup],
		];
		$maxSeq = (int) $this->query_val_ss($q);

		$hc = new \stdClass;
		$hc->creater = $user->id;
		$hc->creater_name = $this->escape($user->name);
		$hc->put_at = time();
		$hc->siteid = $site->id;
		$hc->channel_id = $channel->id;
		$hc->title = $this->escape($channel->title);
		$hc->display_name = $this->escape($channel->title);
		$hc->summary = isset($channel->summary) ? $this->escape($channel->summary) : '';
		//$hc->pic = $channel->pic;
		$hc->seq = $maxSeq + 1;
		$hc->home_group = $this->escape($homeGroup);

		$hc->id = $this->insert('xxt_site_home_channel', $hc, true);

		return $hc;
	}
	/**
	 *
	 */
	public function &refreshHomeChannel($siteId, $homeChannelId) {
		$q = [
			'*',
			'xxt_site_home_channel',
			["siteid" => $siteId, "id" => $homeChannelId],
		];
		if ($hc = $this->query_obj_ss($q)) {
			$modelCh = $this->model('matter\channel');
			$channel = $modelCh->byId($hc->channel_id);
			$updated = [
				'title' => $this->escape($channel->title),
				'summary' => $this->escape($channel->summary),
				'pic' => $channel->pic,
			];
			$this->update('xxt_site_home_channel', $updated, ["id" => $homeChannelId]);
			$hc = $this->query_obj_ss($q);
		}

		return $hc;
	}
	/**
	 *
	 */
	public function &removeHomeChannel($siteId, $homeChannelId) {
		$q = [
			'*',
			'xxt_site_home_channel',
			["siteid" => $siteId, "id" => $homeChannelId],
		];
		if ($hc = $this->query_obj_ss($q)) {
			$this->delete('xxt_site_home_channel', "id=$homeChannelId");
			$this->update("update xxt_site_home_channel set seq=seq-1 where siteid='{$siteId}' and seq>{$hc->seq}");
		}

		return $hc;
	}
}