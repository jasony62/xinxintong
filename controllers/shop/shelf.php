<?php
namespace shop;

include_once dirname(dirname(__FILE__)) . '/member_base.php';
/**
 * 素材货架
 */
class shelf extends \member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action($mpid, $code = null, $mocker = null) {
		$openid = $this->doAuth($mpid, $code, $mocker);

		$params = array(
			'mpid' => $mpid,
		);

		\TPL::assign('params', $params);
		$this->view_action('/shop/shelf');
	}
	/**
	 *
	 */
	public function get_action($matterType, $matterId) {
		$q = array(
			's.*',
			"xxt_shop_matter s",
			"s.matter_type='$matterType' and s.matter_id='$matterId'",
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
		$uid = \TMS_CLIENT::get_client_uid();

		$q = array(
			's.*',
			"xxt_shop_matter s",
			"s.matter_type='$matterType' and (creater='$uid' or visible_scope='A')",
		);
		$q2 = array(
			'o' => 'put_at desc',
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
		);
		$model = $this->model();
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
	 * @param string $mpid
	 * @param string $scope [U|A]
	 */
	public function put_action($mpid, $scope) {
		$account = \TMS_CLIENT::account();
		$matter = $this->getPostJson();
		$options = array('scope' => $scope);

		$item = $this->model('shop\shelf')->putMatter($mpid, $account, $matter, $options);

		return new \ResponseData($item);
	}
	/**
	 * @todo 如何检查当前用户是否有权限？
	 */
	public function update_action($id) {
		$nv = $this->getPostJson();

		$rst = $this->model()->update('xxt_shop_matter', (array) $nv, "id='$id'");

		return new \ResponseData($rst);
	}
}