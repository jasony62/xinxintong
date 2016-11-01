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
			"siteid='$siteId'",
		];
		$q2 = ['o' => 'seq'];
		$homeChannels = $this->query_objs_ss($q, $q2);

		return $homeChannels;
	}
	/**
	 *
	 */
	public function &addHomeChannel(&$user, &$site, &$channel) {
		$q = [
			'max(seq)',
			'xxt_site_home_channel',
			"siteid='{$site->id}'",
		];
		$maxSeq = (int) $this->query_val_ss($q);

		$hc = new \stdClass;
		$hc->creater = $user->id;
		$hc->creater_name = $user->name;
		$hc->put_at = time();
		$hc->siteid = $site->id;
		$hc->channel_id = $channel->id;
		$hc->title = $channel->title;
		$hc->summary = $channel->summary;
		$hc->pic = $channel->pic;
		$hc->seq = $maxSeq + 1;

		$hc->id = $this->insert('xxt_site_home_channel', $hc, true);

		return $hc;
	}
	/**
	 *
	 */
	public function &removeHomeChannel($siteId, $homeChannelId) {
		$q = [
			'*',
			'xxt_site_home_channel',
			"siteid='$siteId' and id=$homeChannelId",
		];
		if ($hc = $this->query_obj_ss($q)) {
			$this->delete('xxt_site_home_channel', "id=$homeChannelId");
			$this->update("update xxt_site_home_channel set seq=seq-1 where siteid='{$siteId}' and seq>{$hc->seq}");
		}

		return $hc;
	}
}