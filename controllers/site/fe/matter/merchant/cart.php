<?php
namespace site\fe\matter\merchant;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 购物车
 */
class cart extends \site\fe\matter\base {
	/**
	 * 进入发起订单页
	 *
	 * 要求当前用户必须是关注用户
	 *
	 * @param string $site mpid'id
	 * @param int $product
	 * @param int $sku
	 *
	 */
	public function index_action($site, $shop, $mocker = null, $code = null) {
		$options = array(
			'fields' => 'title',
			'cascaded' => 'N',
		);
		$page = $this->model('matter\merchant\page')->byType('cart', $shop, 0, 0, $options);
		$page = $page[0];

		\TPL::assign('title', $page->title);
		\TPL::output('/site/fe/matter/merchant/cart');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($site, $shop) {
		// current visitor
		$user = $this->who;
		// page
		$page = $this->model('matter\merchant\page')->byType('cart', $shop);
		if (empty($page)) {
			return new \ResponseError('没有获得购物车页定义');
		}
		$page = $page[0];

		$params = array(
			'user' => $user,
			'page' => $page,
		);

		return new \ResponseData($params);
	}
}