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
	public function list_action($shop, $page = 1, $size = 30) {
		$modelOrder = $this->model('app\merchant\order');

		$p = new \stdClass;
		$p->page = $page;
		$p->size = $size;

		$result = $modelOrder->byShopId($shop, array('page' => $p));

		return new \ResponseData($result);
	}
	/**
	 * 保存订单反馈信息并通知用户
	 */
	public function feedback_action($order) {
		$order = $this->model('app\merchant\order')->byId($order);
		$order->extPropValue = json_decode($order->ext_prop_value);

		$feedback = $this->getPostJson();
		$pv = empty($feedback) ? '{}' : \TMS_MODEL::toJson($feedback);

		$rst = $this->model()->update(
			'xxt_merchant_order',
			array('feedback' => $pv),
			"id=$order->id"
		);
		/*发通知*/
		$order->feedback = json_decode($pv);
		$this->_notify($this->mpid, $order);

		return new \ResponseData($rst);
	}
	/**
	 * 通知客服有新订单
	 */
	private function _notify($mpid, $order) {
		$modelProd = $this->model('app\merchant\product');
		$modelTmpl = $this->model('matter\tmplmsg\config');
		$products = json_decode($order->products);
		foreach ($products as $product) {
			/**/
			$product = $modelProd->byId($product->id, array('cascaded' => 'Y'));
			$mapping = $modelTmpl->byId($product->catelog->feedback_order_tmplmsg);
			if (false === $mapping) {
				return false;
			}
			/**/
			$tmplmsg = $modelTmpl->byId($mapping->msgid, array('cascaded' => 'Y'));
			if (empty($tmplmsg->params)) {
				return false;
			}
			/*构造消息数据*/
			$data = array();
			foreach ($mapping->mapping as $k => $p) {
				$v = '';
				switch ($p->src) {
				case 'product':
					if ($p->id === '__productName') {
						$v = $product->name;
					} else {
						$v = $product->propValue->{$p->id}->name;
					}
					break;
				case 'order':
					if ($p->id === '__orderSn') {
						$v = $order->trade_no;
					} else if ($p->id === '__orderState') {
						$v = '未付款';
					} else {
						$v = $order->extPropValue->{$product->catelog->id}->{$p->id};
					}
					break;
				case 'feedback':
					$v = $order->feedback->{$product->catelog->id}->{$p->id};
					break;
				case 'text':
					$v = $p->id;
					break;
				}
				$data[$k] = $v;
			}
			/**/
			$url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/merchant/order";
			$url .= "?mpid=" . $mpid;
			$url .= "&shop=" . $order->sid;
			$url .= "&order=" . $order->id;
			/**/
			$this->tmplmsgSendByOpenid($mpid, $tmplmsg->id, $order->buyer_openid, $data, $url);
		}
		return true;
	}
}
