<?php
namespace pl\fe\site\setting;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 站点定制页面
 */
class page extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/setting');
		exit;
	}
	/**
	 *
	 */
	public function listHomeChannel_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSp = $this->model('site\page');
		$hcs = $modelSp->homeChannelBySite($site);

		return new \ResponseData($hcs);
	}
	/**
	 * 添加主页频道
	 */
	public function addHomeChannel_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$channel = $this->getPostJson();
		$site = $this->model('site')->byId($site);

		$modelSp = $this->model('site\page');
		$hc = $modelSp->addHomeChannel($user, $site, $channel);

		return new \ResponseData($hc);
	}
	/**
	 * 删除主页频道
	 */
	public function removeHomeChannel_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSp = $this->model('site\page');
		$removed = $modelSp->removeHomeChannel($site, $id);

		return new \ResponseData($removed);
	}
}