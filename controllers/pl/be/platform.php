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
			if (!empty($platform->home_nav)) {
				$platform->home_nav = json_decode($platform->home_nav);
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
		$model = $this->model();

		foreach ($nv as $n => $v) {
			if ($n === 'home_carousel') {
				$nv->{$n} = json_encode($v);
			} else if ($n === 'home_qrcode_group') {
				$nv->{$n} = $model->escape($model->toJson($v));
			} else if ($n === 'home_nav') {
				$nv->{$n} = $model->escape($model->toJson($v));
			}
		}
		$rst = $model->update(
			'xxt_platform',
			$nv,
			"1=1"
		);

		return new \ResponseData($rst);
	}
}