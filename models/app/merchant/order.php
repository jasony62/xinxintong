<?php
namespace app\merchant;
/**
 * 订单
 */
class order_model extends \TMS_MODEL {
	/**
	 * @param int $id
	 */
	public function &byId($id, $cascaded = 'Y') {
		$q = array(
			'*',
			'xxt_merchant_order',
			"id=$id",
		);
		$order = $this->query_obj_ss($q);
		if ($order && $cascaded === 'Y') {
			$order->skus = $this->skus($id);
		}

		return $order;
	}
	/**
	 * @param string $tradeNo
	 */
	public function &byTradeNo($tradeNo) {
		$q = array(
			'*',
			'xxt_merchant_order',
			"trade_no='$tradeNo'",
		);

		$order = $this->query_obj_ss($q);

		return $order;
	}
	/**
	 * 店铺下的订单
	 *
	 * @param int $shopId
	 */
	public function &byShopid($shopId, $openid = null) {
		$q = array(
			'*',
			'xxt_merchant_order',
			"sid=$shopId",
		);
		!empty($openid) && $q[2] .= " and buyer_openid='$openid'";

		$q2 = array(
			'o' => 'order_create_time desc',
		);

		$orders = $this->query_objs_ss($q, $q2);

		return $orders;
	}
	/**
	 * 创建订单
	 *
	 * @param string $mpid
	 * @param object $user
	 * @param object $info
	 *
	 * @return object $order
	 */
	public function &create($mpid, $user, $info) {
		//订单号
		$trade_no = date('YmdHis') . mt_rand(100000, 999999);
		//库存信息
		$skus = array();
		$totalPrice = 0;
		foreach ($info->skus as $skuId => $skuInfo) {
			$sku = \TMS_APP::M('app\merchant\sku')->byId($skuId);
			$sku->__count = $skuInfo->count;
			$totalPrice += $skuInfo->count * $sku->price;
			$skus[] = $sku;
		}
		//商品信息
		$product = \TMS_APP::M('app\merchant\product')->byId($sku->prod_id);
		/*更新商品定义状态*/
		if ($product->used === 'N') {
			\TMS_APP::M('app\merchant\product')->refer($product->id);
		}
		/*创建订单*/
		if (empty($info->extPropValues)) {
			$info->extPropValues = new \stdClass;
			$epv = '{}';
		} else {
			$epv = new \stdClass;
			foreach ($info->extPropValues as $k => $v) {
				$epv->{$k} = urlencode($v);
			}
			$epv = urldecode(json_encode($epv));
		}
		$order = array(
			'trade_no' => $trade_no,
			'mpid' => $mpid,
			'sid' => $sku->sid,
			'order_status' => 1,
			'order_total_price' => $totalPrice,
			'order_create_time' => time(),
			'order_express_price' => 0,
			'ext_prop_value' => $epv,
			'buyer_openid' => $user->openid,
			'buyer_nick' => $user->fan->nickname,
			'receiver_name' => $info->receiver_name,
			'receiver_mobile' => $info->receiver_mobile,
			'product_id' => $sku->prod_id,
			'product_name' => $product->name,
			'product_img' => $product->main_img,
			//'product_sku' => $skuId,
			//'product_price' => $sku->price,
			//'product_count' => $info->product_count,
		);
		$order['id'] = $this->insert('xxt_merchant_order', $order, true);
		$order['extPropValue'] = $info->extPropValues;
		$order = (object) $order;
		//订单包含的库存
		foreach ($skus as $sku) {
			$orderSku = array(
				'mpid' => $mpid,
				'sid' => $sku->sid,
				'oid' => $order->id,
				'sku_id' => $sku->id,
				'sku_price' => $sku->price,
				'sku_count' => $sku->__count,
			);
			$orderSku['id'] = $this->insert('xxt_merchant_order_sku', $orderSku, true);
			/*更新商品sku状态*/
			\TMS_APP::M('app\merchant\sku')->refer($sku->id);
		}

		return $order;
	}
	/**
	 * @param int $orderId
	 */
	private function &skus($orderId) {
		$fields = 'id,sku_id,sku_price,sku_count';
		$q = array(
			$fields,
			'xxt_merchant_order_sku',
			"oid=$orderId",
		);
		$skus = $this->query_objs_ss($q);
		if (!empty($skus)) {
			$modelCate = \TMS_APP::M('app\merchant\catelog');
			foreach ($skus as &$sku) {
				$sku->cateSku = $modelCate->skuById($sku->sku_id);
			}
		}

		return $skus;
	}
}