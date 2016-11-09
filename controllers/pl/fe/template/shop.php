<?php
namespace pl\fe\template;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 应用模版商店
 */
class shop extends \pl\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	public function get_action($matterType, $matterId) {
		$model = $this->model();
		$q = [
			'*',
			"xxt_template",
			["s.matter_type" => $matterType, "s.matter_id" => $matterId],
		];
		$item = $model->query_obj_ss($q);

		return new \ResponseData($item);
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
	public function list_action($matterType, $scenario = null, $site = null, $page = 1, $size = 20) {
		$modelTmpl = $this->model('matter\template');
		$matterType = $modelTmpl->escape($matterType);

		$q = [
			'*',
			"xxt_template",
			"visible_scope='P' and matter_type='$matterType'",
		];
		if (!empty($scenario)) {
			$q[2] .= " and s.scenario='$scenario'";
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
		$q = [
			'id,creater_name,create_at,name',
			'xxt_site s',
			"(creater='{$user->id}' or exists(select 1 from xxt_site_admin sa where sa.siteid=s.id and uid='{$user->id}')) and state=1",
		];
		$q2 = ['o' => 'create_at desc'];

		$targets = []; // 符合条件的站点
		$sites = $this->model()->query_objs_ss($q, $q2);
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