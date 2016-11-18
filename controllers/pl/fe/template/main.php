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
	/**
	 * 获得模板列表
	 *
	 * @param string $matterType
	 * @param string $scenario
	 * @param string $site 在哪个站点中查看模版
	 * @param int $page
	 * @param int $size
	 *
	 */
	public function share2Me_action($matterType, $scenario = null, $site = null, $page = 1, $size = 20) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\template');
		$matterType = $modelTmpl->escape($matterType);

		$q = [
			'*',
			"xxt_template t",
			"matter_type='$matterType' and exists(select 1 from xxt_template_acl a where a.receiver='{$user->id}' and t.id=a.template_id)",
		];
		if (!empty($scenario)) {
			$q[2] .= " and scenario='$scenario'";
		}
		$q2 = [
			'o' => 'put_at desc',
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];

		if ($templates = $modelTmpl->query_objs_ss($q, $q2)) {
			$q[0] = "count(*)";
			$total = $modelTmpl->query_val_ss($q);
			if (!empty($site)) {
				/* 叠加是否已被站点收藏的信息 */
				foreach ($templates as &$template) {
					if ($modelTmpl->isFavorBySite($template, $site)) {
						$template->_favored = 'Y';
					}
				}
			}
		} else {
			$total = 0;
		}

		return new \ResponseData(['templates' => $templates, 'total' => $total]);
	}
}