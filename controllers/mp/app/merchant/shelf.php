<?php
namespace mp\app\merchant;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 商品货架
 */
class shelf extends \mp\app\app_base {
	/**
	 * 打开订购商品管理页面
	 */
	public function index_action() {
		$this->view_action('/mp/app/merchant/shelf');
	}
	/**
	 *
	 */
	public function get_action() {
		return new \ResponseData('ok');
	}
	/**
	 *
	 */
	public function update_action() {
		return new \ResponseData('ok');
	}
}