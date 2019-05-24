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
	public function listHomeChannel_action($site, $homeGroup = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSp = $this->model('site\page');
		$options = ['home_group' => $homeGroup];
		$hcs = $modelSp->homeChannelBySite($site, $options);

		return new \ResponseData($hcs);
	}
	/**
	 * 在指定团队下添加主页频道
	 */
	public function addHomeChannel_action($site, $homeGroup = 'C') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oChannel = $this->getPostJson();
		$oSite = $this->model('site')->byId($site);
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}

		$modelSp = $this->model('site\page');
		$hc = $modelSp->addHomeChannel($user, $oSite, $oChannel, $homeGroup);

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
	/**
	 * 修改主页频道
	 */
	public function updateHomeChannel_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$post = $this->getPostJson();
		$model = $this->model();
		$data = [];
		!empty($post->homeGroup) && $data['home_group'] = $post->homeGroup;
		!empty($post->display_name) && $data['display_name'] = $post->display_name;

		if(!empty($data)){
			$model->update('xxt_site_home_channel', $data, ['id' => $id]);
		}

		return new \ResponseData('ok');
	}
}