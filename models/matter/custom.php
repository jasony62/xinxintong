<?php
namespace matter;

require_once dirname(__FILE__) . '/article.php';

class custom_model extends article_model {
	/**
	 *
	 */
	public function getEntryUrl($siteId, $id) {
		$url = "http://" . APP_HTTP_HOST;
		$url .= "/rest/site/fe/matter";
		if ($siteId === 'platform') {
			if ($oArticle = $this->byId($id)) {
				$url .= "?site={$oArticle->siteid}&id={$id}&type=custom";
			} else {
				$url = "http://" . APP_HTTP_HOST;
			}
		} else {
			$url .= "?site={$siteId}&id={$id}&type=custom";
		}

		return $url;
	}
}