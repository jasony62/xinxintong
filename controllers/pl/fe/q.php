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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		$modelQurl = $this->model('q\url');

		$task = $modelQurl->byUrl($oUser, $site, $oPosted->url);

		return new \ResponseData($task);
	}
	/**
	 * 创建快速进入短链接
	 * 一个url只允许创建一个短链接，若已经有，就返回已有的
	 *
	 * @param string $site
	 * @param string $url
	 *
	 * @return
	 */
	public function create_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		if (empty($oPosted->url)) {
			return new \ParameterError();
		}

		$url = $oPosted->url;
		$title = isset($oPosted->title) ? $oPosted->title : '';

		$modelQurl = $this->model('q\url');
		if ($oShortUrl = $modelQurl->byUrl($oUser, $site, $url)) {
			return new \ResponseData(['code' => $oShortUrl->code]);
		}

		$code = $modelQurl->add($oUser, $site, $url, $title);

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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		$modelQurl = $this->model('q\url');

		$data = [];
		foreach ($oPosted as $key => $val) {
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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		$modelQurl = $this->model('q\url');

		$rst = $modelQurl->remove($oUser, $site, $oPosted->url);

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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		$modelQurl = $this->model('q\url');

		$rst = $modelQurl->update(
			'xxt_short_url',
			$oPosted->config, "target_url='{$oPosted->url}'"
		);

		return new \ResponseData($rst);
	}
}