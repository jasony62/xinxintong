<?php
namespace site;
/**
 * 站点消息通知模版
 */
class notice_model extends \TMS_MODEL {
	/**
	 *
	 */
	private function &_queryBy($where, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array(
			$fields,
			'xxt_site_notice',
			$where,
		);

		$notices = $this->query_objs_ss($q);

		return $notices;
	}
	/**
	 *
	 */
	public function &byId($id, $options = []) {
		$notice = $this->_queryBy("id=$id", $options);

		$notice = count($notice) === 1 ? $notice[0] : false;

		return $notice;
	}
	/**
	 *
	 */
	public function byName($siteId, $name, $options = []) {
		$notice = $this->_queryBy("siteid='$siteId' and event_name='$name'", $options);

		$notice = count($notice) === 1 ? $notice[0] : false;

		if (false === $notice && $siteId !== 'platform') {
			$notice = $this->_queryBy("siteid='platform' and event_name='$name'", $options);
			$notice = count($notice) === 1 ? $notice[0] : false;
		}

		return $notice;
	}
	/**
	 *
	 * $siteId
	 * $valid [null|Y|N]
	 */
	public function &bySite($siteId, $options = []) {
		$where = "siteid='$siteId'";

		$notices = $this->_queryBy($where, $options);

		return $notices;
	}
	/**
	 *
	 */
	public function &add($siteId, $name, $data = []) {
		$data['siteid'] = $siteId;
		$data['event_name'] = $name;

		$id = $this->insert('xxt_site_notice', $data, true);

		$notice = $this->byId($id);

		return $notice;
	}
}