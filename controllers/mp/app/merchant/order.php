<?php
namespace mp\app\merchant;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 商品订单
 */
class order extends \mp\app\app_base {
	/**
	 * 打开订购商品管理页面
	 */
	public function index_action() {
		$this->view_action('/mp/app/merchant/order');
	}
	/**
	 *
	 */
	public function get_action($order) {
		$order = $this->model('app\merchant\order')->byId($order);
		$order->extPropValues = empty($order->ext_prop_value) ? new \stdClass : json_decode($order->ext_prop_value);
		$order->feedback = empty($order->feedback) ? new \stdClass : json_decode($order->feedback);

		/*按分类和商品对sku进行分组*/
		$skus = $order->skus;
		$catelogs = array();
		if (!empty($skus)) {
			$cateSkus = array();
			$modelCate = $this->model('app\merchant\catelog');
			$modelProd = $this->model('app\merchant\product');
			$modelSku = $this->model('app\merchant\sku');
			$cateFields = 'id,sid,name,pattern,pages';
			$prodFields = 'id,sid,cate_id,name,main_img,img,detail_text,detail_text,prop_value,buy_limit,sku_info';
			$skuFields = 'id,sid,cate_id,cate_sku_id,icon_url,price,ori_price,quantity,validity_begin_at,validity_end_at,sku_value';
			foreach ($skus as &$sku) {
				if (!isset($catelogs[$sku->cate_id])) {
					/*catelog*/
					$catelog = $modelCate->byId($sku->cate_id, array('fields' => $cateFields, 'cascaded' => 'Y'));
					$catelog->pages = isset($catelog->pages) ? json_encode($catelog->pages) : new \stdClass;
					$catelog->products = array();
					$catelogs[$catelog->id] = &$catelog;
					/*product*/
					$product = $modelProd->byId($sku->prod_id, array('cascaded' => 'N', 'fields' => $prodFields, 'catelog' => $catelog));
					$product->skus = array();
					$catelog->products[$product->id] = &$product;
				} else {
					$catelog = &$catelogs[$sku->cate_id];
					if (!isset($catelog->products[$sku->prod_id])) {
						$product = $modelProd->byId($sku->prod_id, array('cascaded' => 'N', 'fields' => $prodFields, 'catelog' => $catelog));
						$product->skus = array();
						$catelog->products[$product->id] = &$product;
					} else {
						$product = $catelog->products[$sku->prod_id];
					}
				}
				if (isset($cateSkus[$sku->cate_sku_id])) {
					$cateSku = $cateSkus[$sku->cate_sku_id];
				} else {
					$cateSku = $modelCate->skuById($sku->cate_sku_id);
					$cateSkus[$sku->cate_sku_id] = $cateSku;
				}
				$product->skus[] = $modelSku->byId($sku->sku_id, array('cascaded' => 'Y', 'fields' => $skuFields, 'cateSku' => $cateSku));
			}
		}

		return new \ResponseData(array('order' => $order, 'catelogs' => $catelogs));
	}
	/**
	 *
	 */
	public function list_action($shop) {
		$modelOrder = $this->model('app\merchant\order');

		$orders = $modelOrder->byShopId($shop);
		$result = array(
			'orders' => $orders,
		);

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function update_action() {
		return new \ResponseData('ok');
	}
}
