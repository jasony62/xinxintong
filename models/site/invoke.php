<?php
namespace site;
/**
 * 发布在平台主页上的团队
 */
class invoke_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function bySite($site, $options = []) {
		$fields = !empty($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_site_invoke',
			['siteid' => $site],
		];

		$invoke = $this->query_obj_ss($q);
		if ($invoke) {
			if (property_exists($invoke, 'invoker_ip')) {
				$invoke->invokerIps = empty($invoke->invoker_ip) ? [] : explode(',',$invoke->invoker_ip);
			}
		}

		return $invoke;
	}
	/*
	 *
	 */
	public function create($site, $user) {
		$current = time();
		$data = new \stdClass;
		$data->siteid = $this->escape($site);
		$data->invoker = $user->id;
		$data->invoker_name = $this->escape($user->name);
		$data->create_at = $current;

		$this->insert('xxt_site_invoke', $data, false);
		$invoke = $this->bySite($site);

		return $invoke;
	}
}