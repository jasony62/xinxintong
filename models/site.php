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
}