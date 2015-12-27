<?php
namespace shop;

include_once dirname(dirname(__FILE__)) . '/member_base.php';
/**
 *
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
	public function get_action($mattertype, $matterid) {
		$q = array(
			's.*',
			"xxt_shop_matter s",
			"s.matter_type='$mattertype' and s.matter_id='$matterid'",
		);
		$item = $this->model()->query_obj_ss($q);

		return new \ResponseData($item);
	}
	/**
	 *
	 * $matterType
	 * $page
	 * $size
	 */
	public function list_action($mattertype = 'enroll', $page = 1, $size = 20) {
		$q = array(
			's.*',
			"xxt_shop_matter s",
			"s.matter_type='$mattertype'",
		);
		$q2 = array(
			'o' => 'put_at desc',
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
		);

		$items = $this->model()->query_objs_ss($q, $q2);

		return new \ResponseData($items);
	}
	/**
	 * 素材上架
	 */
	public function put_action($mpid) {
		$matter = $this->getPostJson();

		$item = $this->model('shop\shelf')->putMatter($mpid, $matter);

		return new \ResponseData($item);
	}
	/**
	 * todo 如何检查当前用户是否有权限？
	 */
	public function update_action($id) {
		$nv = $this->getPostJson();

		$rst = $this->model()->update('xxt_shop_matter', (array) $nv, "id='$id'");

		return new \ResponseData($rst);
	}
}