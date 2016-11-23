<?php
namespace pl\fe\template;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 模板库管理控制器
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function get_action($template) {
		$template = $this->model('matter\template')->byId($template);

		return new \ResponseData($template);
	}
	/**
	 * 获得指定素材对应的模版
	 */
	public function byMatter_action($type, $id) {
		$model = $this->model();
		$q = [
			'*',
			"xxt_template",
			["matter_type" => $type, "matter_id" => $id],
		];
		$template = $model->query_obj_ss($q);

		return new \ResponseData($template);
	}

	/**
	 * 模版上架
	 *
	 * @param string $site
	 * @param string $scope [Platform|Site]
	 */
	public function put_action($site) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$matter = $this->getPostJson(true);
		$site = $this->model('site')->byId($site, ['fields', 'id,name']);

		$item = $this->model('matter\template')->putMatter($site, $loginUser, $matter);

		return new \ResponseData($item);
	}
	/**
	 *
	 */
	public function pushHome_action($template) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\template');

		$rst = $modelTmpl->pushHome($template);

		return new \ResponseData($rst);
	}
	/**
	 * @todo 如何检查当前用户是否有权限？
	 */
	public function update_action($id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$nv = $this->getPostJson(true);

		$rst = $this->model()->update('xxt_template', $nv, "id='$id'");

		return new \ResponseData($rst);
	}
	/**
	 * 在指定站点中收藏模版
	 *
	 * @param id $templte
	 * @param string $site 收藏模版的站点ID逗号分隔的字符串
	 */
	public function favor_action($template, $site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\template');

		if (false === ($template = $modelTmpl->byId($template))) {
			return new \ResponseError('数据不存在');
		}

		$modelSite = $this->model('site');
		$siteIds = explode(',', $site);
		foreach ($siteIds as $siteId) {
			$modelTmpl->favorBySite($user, $template, $siteId);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 在指定站点中取消收藏模版
	 *
	 * @param id $templte
	 * @param string $site 收藏模版的站点ID逗号分隔的字符串
	 */
	public function unfavor_action($template, $site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\template');

		if (false === ($template = $modelTmpl->byId($template))) {
			return new \ResponseError('数据不存在');
		}

		$rst = $modelTmpl->unfavorBySite($user, $template, $site);

		return new \ResponseData($rst);
	}
	/**
	 * 在指定站点中使用模版
	 *
	 * @param id $templte
	 * @param string $site 收藏模版的站点ID逗号分隔的字符串
	 */
	public function purchase_action($template, $site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\template');

		if (false === ($template = $modelTmpl->byId($template))) {
			return new \ResponseError('数据不存在');
		}

		$modelSite = $this->model('site');
		$siteIds = explode(',', $site);
		foreach ($siteIds as $siteId) {
			$modelTmpl->purchaseBySite($user, $template, $siteId);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 当前用户没有收藏过指定模板的站点
	 *
	 * @param int $template
	 */
	public function siteCanFavor_action($template) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\template');
		if (false === ($template = $modelTmpl->byId($template))) {
			return new \ResponseError('数据不存在');
		}

		$targets = []; // 符合条件的站点
		$sites = $this->model('site')->byUser($user->id);
		foreach ($sites as &$site) {
			if ($site->id === $template->siteid) {
				continue;
			}
			if ($modelTmpl->isFavorBySite($template, $site->id)) {
				$site->_favored = 'Y';
			}
			$targets[] = $site;
		}

		return new \ResponseData($targets);
	}
}