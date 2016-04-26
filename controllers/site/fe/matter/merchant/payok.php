<?php
namespace site\fe\matter\merchant;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 支付
 */
class payok extends \site\fe\matter\base {
	/**
	 * 进入支付页
	 *
	 * 要求当前用户必须是认证用户
	 *
	 * $site mpid'id
	 * $shop shop'id
	 * $sku sku'id
	 */
	public function index_action() {
		\TPL::output('/site/fe/matter/merchant/payok');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($site, $shop) {
		// current visitor
		$user = $this->who;
		// page
		$page = $this->model('matter\merchant\page')->byType('payok', $shop);
		if (empty($page)) {
			return new \ResponseError('没有获得订单页定义');
		}
		$page = $page[0];

		$params = array(
			'user' => $user,
			'page' => $page,
		);

		return new \ResponseData($params);
	}
}