<?php
namespace pl\be;

require_once dirname(__FILE__) . '/base.php';
/**
 * 平台
 */
class platform extends \pl\be\base {
	/**
	 *
	 */
	public function get_action() {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$platform = $this->model('platform')->get(['cascaded' => 'home_page_name']);
		if ($platform) {
			if (!empty($platform->home_carousel)) {
				$platform->home_carousel = json_decode($platform->home_carousel);
			}
		}

		return new \ResponseData($platform);
	}
	/**
	 *
	 */
	public function update_action() {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$nv = $this->getPostJson();
		foreach ($nv as $n => $v) {
			if ($n === 'home_carousel') {
				$nv->{$n} = json_encode($v);
			}
		}
		$rst = $this->model()->update(
			'xxt_platform',
			$nv,
			"1=1"
		);

		return new \ResponseData($rst);
	}
}