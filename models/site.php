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
	public function &byId($id, $options = array()) {
		$fields = empty($options['fields']) ? '*' : $options['fields'];
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : '';
		$q = array(
			$fields,
			'xxt_site',
			"id='$id'",
		);
		if (($site = $this->query_obj_ss($q)) && !empty($cascaded)) {
			$cascaded = explode(',', $cascaded);
			$modelCode = \TMS_APP::M('code\page');
			foreach ($cascaded as $field) {
				if ($field === 'home_page_id') {

				} else if ($field === 'header_page_id' && $site->header_page_id) {
					$site->header_page = $modelCode->byId($site->header_page_id, 'html,css,js');
				} else if ($field === 'footer_page_id') {

				} else if ($field === 'shift2pc_page_id') {

				}
			}
		}

		return $site;
	}
}