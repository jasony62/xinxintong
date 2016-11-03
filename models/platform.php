<?php
/**
 * 平台
 */
class platform_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &get($options = []) {
		$fields = empty($options['fields']) ? '*' : $options['fields'];
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : '';
		$q = [
			$fields,
			'xxt_platform',
			'1=1',
		];
		if (false === ($platform = $this->query_obj_ss($q))) {
			$this->insert('xxt_platform', ['home_carousel' => '']);
			$platform = $this->query_obj_ss($q);
		}
		if (!empty($cascaded)) {
			$cascaded = explode(',', $cascaded);
			$modelCode = \TMS_APP::M('code\page');
			foreach ($cascaded as $field) {
				if ($field === 'home_page') {
					$platform->home_page = $modelCode->lastPublishedByName('platform', $platform->home_page_name, ['fields' => 'id,html,css,js']);
				} else if ($field === 'template_page') {
					$platform->template_page = $modelCode->lastPublishedByName('platform', $platform->template_page_name, ['fields' => 'id,html,css,js']);
				} else if ($field === 'site_page') {
					$platform->site_page = $modelCode->lastPublishedByName('platform', $platform->site_page_name, ['fields' => 'id,html,css,js']);
				}
			}
		}

		return $platform;
	}
}