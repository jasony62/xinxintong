<?php
namespace pl\fe\template;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台模版库
 */
class platform extends \pl\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 获得模板列表
	 *
	 * @param string $matterType
	 * @param string $scenario
	 * @param int $page
	 * @param int $size
	 *
	 */
	public function list_action($matterType = null, $scenario = null, $page = 1, $size = 20) {
		$modelTmpl = $this->model('matter\template');
		$matterType = $modelTmpl->escape($matterType);

		$q = [
			'*',
			"xxt_template",
			['visible_scope' => 'P']
		];
		if(!empty($matterType)){
			$q[2]['matter_type'] = $matterType;
		}
		if (!empty($scenario)) {
			$q[2] .= " and scenario='$scenario'";
		}
		$q2 = [
			'o' => 'put_at desc',
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];

		$templates = $modelTmpl->query_objs_ss($q, $q2);
		$q[0] = "count(*)";
		$total = $modelTmpl->query_val_ss($q);

		return new \ResponseData(['templates' => $templates, 'total' => $total]);
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
	public function share2Me_action($matterType = null, $scenario = null, $site = null, $page = 1, $size = 20) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\template');
		$matterType = empty($matterType)? null : $modelTmpl->escape($matterType);

		$q = [
			'*',
			"xxt_template t",
			"exists(select 1 from xxt_template_acl a where a.receiver='{$user->id}' and t.id=a.template_id)",
		];
		if(!empty($matterType)){
			$q[2] .= " and ".$matterType;
		}
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