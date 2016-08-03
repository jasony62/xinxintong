<?php
namespace pl\fe\shop;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 素材模版市场
 */
class shelf extends \pl\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action($site) {
		$params = array(
			'mpid' => $site,
		);

		\TPL::assign('params', $params);
		$this->view_action('/shop/shelf');
	}
	/**
	 *
	 */
	public function get_action($matterType, $matterId) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$q = array(
			's.*',
			"xxt_shop_matter s",
			["s.matter_type" => $matterType, "s.matter_id" => $matterId],
		);
		$item = $this->model()->query_obj_ss($q);

		return new \ResponseData($item);
	}
	/**
	 * 获得模板列表
	 *
	 * @param string $matterType
	 * @param int $page
	 * @param int $size
	 */
	public function list_action($matterType, $page = 1, $size = 20) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$matterType = $model->escape($matterType);

		$q = array(
			's.*',
			"xxt_shop_matter s",
			"s.matter_type='$matterType' and (creater='{$user->id}' or visible_scope='A')",
		);
		$q2 = [
			'o' => 'put_at desc',
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];
		if ($items = $model->query_objs_ss($q, $q2)) {
			$q[0] = "count(*)";
			$total = $model->query_val_ss($q);
		} else {
			$total = 0;
		}

		return new \ResponseData(array('templates' => $items, 'total' => $total));
	}
	/**
	 * 素材上架
	 *
	 * @param string $site
	 * @param string $scope [U|A]
	 */
	public function put_action($site, $scope) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$matter = $this->getPostJson();
		$options = ['scope' => $scope];

		$item = $this->model('shop\shelf')->putMatter($site, $user, $matter, $options);

		return new \ResponseData($item);
	}
	/**
	 * @todo 如何检查当前用户是否有权限？
	 */
	public function update_action($id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$nv = $this->getPostJson();

		$rst = $this->model()->update('xxt_shop_matter', $nv, "id='$id'");

		return new \ResponseData($rst);
	}
}