<?php
namespace app\merchant;
/**
 * 订单
 */
class order_model extends \TMS_MODEL {
	/**
	 * @param int $id
	 */
	public function &byId($id, $options = array()) {
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array(
			$fields,
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
	public function &byTradeNo($tradeNo, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array(
			$fields,
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
	public function &byShopid($shopId, $options = array()) {
		$openid = isset($options['openid']) ? $options['openid'] : null;
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		/*orders*/
		$q = array(
			'*',
			'xxt_merchant_order',
			"sid=$shopId",
		);
		/*buyer*/
		!empty($openid) && $q[2] .= " and buyer_openid='$openid'";
		/*status*/
		!empty($options['status']) && $q[2] .= " and order_status in(" . implode(',', $options['status']) . ")";
		/*order by*/
		$q2 = array(
			'o' => 'order_create_time desc',
		);
		if (isset($options['page'])) {
			$page = $options['page'];
			$q2['r'] = array('o' => ($page->page - 1) * $page->size, 'l' => $page->size);
		}
		$orders = $this->query_objs_ss($q, $q2);
		/*total*/
		if (empty($orders)) {
			$total = 0;
		} else {
			$q[0] = "count(*)";
			$total = $this->query_val_ss($q);
		}

		$result = array('orders' => $orders, 'total' => $total);

		return $result;
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
		$productIds = array();
		$skus = array();
		$totalPrice = 0;
		$modelSku = \TMS_APP::M('app\merchant\sku');
		foreach ($info->skus as $skuId => $skuInfo) {
			$sku = $modelSku->byId($skuId);
			$sku->__count = $skuInfo->count;
			$totalPrice += $skuInfo->count * $sku->price;
			$skus[] = $sku;
			$productIds[$sku->prod_id] = 1;
		}
		//商品信息
		$products = array();
		$modelProd = \TMS_APP::M('app\merchant\product');
		foreach ($productIds as $prodId => $v) {
			$product = $modelProd->byId($prodId);
			$products[] = array(
				'id' => $product->id,
				'cate_id' => $product->cate_id,
				'name' => urlencode($product->name),
				'main_img' => $product->main_img,
			);
			/*更新商品定义状态*/
			if ($product->used === 'N') {
				$modelProd->refer($product->id);
			}
		}
		/*创建订单*/
		if (empty($info->extPropValues)) {
			$info->extPropValues = new \stdClass;
		}
		$epv = self::toJson($info->extPropValues);

		$order = array(
			'trade_no' => $trade_no,
			'mpid' => $mpid,
			'sid' => $sku->sid,
			'products' => urldecode(json_encode($products)),
			'order_status' => 1,
			'order_total_price' => $totalPrice,
			'order_create_time' => time(),
			'order_express_price' => 0,
			'ext_prop_value' => $epv,
			'buyer_openid' => $user->openid,
			'buyer_nick' => $user->fan->nickname,
			'receiver_name' => isset($info->receiver_name) ? $info->receiver_name : '',
			'receiver_mobile' => isset($info->receiver_mobile) ? $info->receiver_mobile : '',
			'receiver_email' => isset($info->receiver_email) ? $info->receiver_email : '',
			'payby' => isset($info->payby) ? $info->payby : '',
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
				'cate_id' => $sku->cate_id,
				'cate_sku_id' => $sku->cate_sku_id,
				'prod_id' => $sku->prod_id,
				'sku_id' => $sku->id,
				'sku_price' => $sku->price,
				'sku_count' => $sku->__count,
			);
			$orderSku['id'] = $this->insert('xxt_merchant_order_sku', $orderSku, true);
			/*更新商品sku状态*/
			$modelSku->order($sku->id, $sku->__count);
		}

		return $order;
	}
	/**
	 * 创建订单
	 *
	 * @param string $mpid
	 * @param object $user
	 * @param int $orderId
	 * @param object $info
	 *
	 * @return object $order
	 */
	public function &modify($mpid, $user, $orderId, $info) {
		if (empty($info->extPropValues)) {
			$info->extPropValues = new \stdClass;
		}
		$epv = self::toJson($info->extPropValues);
		$order = array(
			'ext_prop_value' => $epv,
			'receiver_name' => $info->receiver_name,
			'receiver_mobile' => $info->receiver_mobile,
			'receiver_email' => $info->receiver_email,
		);
		$rst = $this->update('xxt_merchant_order', $order, "id=$orderId");

		return $rst;
	}
	/**
	 * 完成订单
	 */
	public function finish($order) {
		$updated = array(
			'order_status' => 5,
		);
		$rst = $this->update('xxt_merchant_order', $updated, "id=$order");

		return $rst;
	}
	/**
	 * 取消订单
	 */
	public function cancel($orderId) {
		$updated = array(
			'order_status' => -1,
		);
		$rst = $this->update('xxt_merchant_order', $updated, "id=$orderId");
		/*取消对库存的占用*/
		if ($rst) {
			$skus = $this->skus($orderId);
			foreach ($skus as $sku) {
				$this->update("update xxt_merchant_product_sku set quantity=quantity+1 where id=$sku->sku_id");
			}
		}

		return $rst;
	}
	/**
	 * 取消订单
	 *
	 * @param int $orderId
	 */
	public function cancelByBuyer($orderId) {
		$updated = array(
			'order_status' => -2,
		);
		$rst = $this->update('xxt_merchant_order', $updated, "id=$orderId");
		/*取消对库存的占用*/
		if ($rst) {
			$skus = $this->skus($orderId);
			foreach ($skus as $sku) {
				$this->update("update xxt_merchant_product_sku set quantity=quantity+1 where id=$sku->sku_id");
			}
		}

		return $rst;
	}
	/**
	 * @param int $orderId
	 */
	private function &skus($orderId) {
		$fields = 'id,cate_id,cate_sku_id,prod_id,sku_id,sku_price,sku_count';
		$q = array(
			$fields,
			'xxt_merchant_order_sku',
			"oid=$orderId",
		);
		$skus = $this->query_objs_ss($q);

		return $skus;
	}
}