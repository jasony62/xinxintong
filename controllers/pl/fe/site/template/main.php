<?php
namespace pl\fe\site\template;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 站点模板管理控制器
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/template');
		exit;
	}
	/**
	 *
	 */
	public function get_action($template) {
		$template = $this->model('matter\template')->byId($template);

		return new \ResponseData($template);
	}
	/**
	 * 获得模板列表
	 *
	 * @param string $matterType
	 * @param int $page
	 * @param int $size
	 */
	public function list_action($site, $matterType, $scenario = null, $scope = 'S', $page = 1, $size = 20) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$matterType = $model->escape($matterType);

		if ($scope === 'S') {
			$q = [
				's.*',
				"xxt_template s",
				"1=1",
			];
		} else if (in_array($scope, ['favor', 'purchase'])) {
			$q = [
				'*',
				"xxt_template_order",
			];
			if ($scope === 'favor') {
				$q[2] = "favor='Y'";
			} else {
				$q[2] = "purchase='Y'";
			}
		}
		$q[2] .= " and matter_type='$matterType'";
		if (!empty($scenario)) {
			$q[2] .= " and s.scenario='$scenario'";
		}
		$q[2] .= " and siteid='$site'";

		$q2 = [
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];
		if ($scope === 'S') {
			$q2['o'] = 'put_at desc';
		} else if ($scope === 'favor') {
			$q2['o'] = 'favor_at desc';
		} else if ($scope === 'purchase') {
			$q2['o'] = 'purchase_at desc';
		}

		if ($orders = $model->query_objs_ss($q, $q2)) {
			$q[0] = "count(*)";
			$total = $model->query_val_ss($q);
		} else {
			$total = 0;
		}

		return new \ResponseData(['templates' => $orders, 'total' => $total]);
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
	 * 在指定站点中收藏模版
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
}