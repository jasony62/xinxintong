<?php
namespace pl\fe;

require_once dirname(__FILE__) . '/base.php';
/**
 * 快速进入
 */
class q extends \pl\fe\base {
	/**
	 * 获得快速进入短链接
	 *
	 * @param string $site
	 * @param string $url
	 *
	 * @return
	 */
	public function get_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$modelQurl = $this->model('q\url');

		$task = $modelQurl->byUrl($user, $site, $posted->url);

		return new \ResponseData($task);
	}
	/**
	 * 创建快速进入短链接
	 *
	 * @param string $site
	 * @param string $url
	 *
	 * @return
	 */
	public function create_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$modelQurl = $this->model('q\url');

		$code = $modelQurl->add($user, $site, $posted->url);

		return new \ResponseData(['code' => $code]);
	}
	/**
	 * 删除快速进入短链接
	 *
	 * @param string $site
	 * @param string $url
	 *
	 * @return
	 */
	public function remove_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$modelQurl = $this->model('q\url');

		$rst = $modelQurl->remove($user, $site, $posted->url);

		return new \ResponseData($rst);
	}
	/**
	 * 删除快速进入短链接
	 *
	 * @param string $site
	 * @param string $url
	 *
	 * @return
	 */
	public function config_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$modelQurl = $this->model('q\url');

		$rst = $modelQurl->update(
			'xxt_short_url',
			$posted->config, "target_url='{$posted->url}'"
		);

		return new \ResponseData($rst);
	}
}