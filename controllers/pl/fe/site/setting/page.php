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
	 * 更新主页频道
	 */
	public function refreshHomeChannel_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSp = $this->model('site\page');
		$refreshed = $modelSp->refreshHomeChannel($site, $id);

		return new \ResponseData($refreshed);
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
	/**
	 * 更新主页频道排序
	 */
	public function seqHomeChannel_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$channelSeqs = $this->getPostJson();
		$model = $this->model();
		foreach ($channelSeqs as $hcId => $seq) {
			$model->update('xxt_site_home_channel', ['seq' => $seq], ['id' => $hcId]);
		}

		return new \ResponseData('ok');
	}
}