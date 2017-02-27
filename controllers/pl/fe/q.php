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
		if (empty($posted->url)) {
			return new \ParameterError();
		}

		$url = $posted->url;
		$title = isset($posted->title) ? $posted->title : '';

		$modelQurl = $this->model('q\url');
		$code = $modelQurl->add($user, $site, $url, $title);

		return new \ResponseData(['code' => $code]);
	}
	/**
	 * 更新短链接属性
	 *
	 * @param string $site
	 * @param string $code
	 *
	 * @return
	 */
	public function update_action($site, $code) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$modelQurl = $this->model('q\url');

		$data = [];
		foreach ($posted as $key => $val) {
			if ($key === 'can_favor') {
				$data['can_favor'] = $val === 'Y' ? 'Y' : 'N';
			}
		}
		if (count($data) === 0) {
			return new \ResponseError('没有指定要更新的数据');
		}

		$rst = $modelQurl->update(
			'xxt_short_url',
			$data,
			["siteid" => $site, "code" => $code]
		);

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