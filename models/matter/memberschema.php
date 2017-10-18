<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 *
 */
class memberschema_model extends app_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_site_member_schema';
	}
	/**
	 *
	 */
	public function getEntryUrl($siteId, $id, $baseUrl = null) {
		$url = 'http://' . APP_HTTP_HOST;
		$url .= empty($baseUrl) ? '/rest/site/fe/user/member' : $baseUrl;

		if ($siteId === 'platform') {
			if ($oMschema = $this->byId($id)) {
				$url .= "?site={$oMschema->siteid}&schema=" . $id;
			} else {
				$url = 'http://' . APP_HTTP_HOST . '/404.html';
			}
		} else {
			$url .= "?site={$siteId}&schema=" . $id;
		}

		return $url;
	}
}