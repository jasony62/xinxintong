<?php
namespace op\merchant;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 订单
 */
class order extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action($mpid = null, $shop = null, $order = null) {
		// page
		$options = array(
			'cascaded' => 'N',
			'fields' => 'title',
		);
		$page = $this->model('app\merchant\page')->byType('op.order', $shop, 0, 0, $options);
		$page = $page[0];

		\TPL::assign('title', $page->title);
		\TPL::output('/op/merchant/order');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($mpid, $shop) {
		// current visitor
		$user = $this->getUser($mpid);
		// page
		$page = $this->model('app\merchant\page')->byType('op.order', $shop);
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
	/**
	 * 查看订单
	 */
	public function get_action($mpid, $order) {
		//$fan = $this->getCookieOAuthUser($mpid);
		//if (empty($fan->openid))
		//    return new \ResponseError('无法获得当前用户身份信息');
		//
		$order = $this->model('app\merchant\order')->byId($order);
		$skus = $order->skus;

		/*按分类和商品对sku进行分组*/
		$catelogs = array();
		if (!empty($skus)) {
			$modelCate = $this->model('app\merchant\catelog');
			$modelProd = $this->model('app\merchant\product');
			$modelSku = $this->model('app\merchant\sku');
			$cateFields = 'id,name,pattern,pages';
			$prodFields = 'id,name,main_img,img,detail_text,detail_text,prop_value,buy_limit,sku_info';
			$cateSkuOptions = array(
				'fields' => 'id,name,has_validity,require_pay',
			);
			$skuOptions = array(
				'cascaded' => 'N',
				'fields' => 'id,cate_id,cate_sku_id,prod_id,icon_url,price,ori_price,quantity,validity_begin_at,validity_end_at,sku_value',
			);
			foreach ($skus as &$sku) {
				$sku = $modelSku->byId($sku->sku_id, $skuOptions);
				if (false === $sku) {
					continue;
				}
				if (!isset($catelogs[$sku->cate_id])) {
					/*catelog*/
					$catelog = $modelCate->byId($sku->cate_id, array('fields' => $cateFields, 'cascaded' => 'Y'));
					$catelog->pages = isset($catelog->pages) ? json_decode($catelog->pages) : new \stdClass;
					$catelog->products = array();
					$catelogs[$catelog->id] = &$catelog;
					/*product*/
					$product = $modelProd->byId($sku->prod_id, array('cascaded' => 'N', 'fields' => $prodFields, 'catelog' => $catelog));
					$product->cateSkus = array();
					/*catelog sku*/
					$cateSku = $modelCate->skuById($sku->cate_sku_id, $cateSkuOptions);
					$cateSku->skus = array($sku);
					$product->cateSkus[$cateSku->id] = $cateSku;
					$catelog->products[$product->id] = $product;
				} else {
					$catelog = &$catelogs[$sku->cate_id];
					if (!isset($catelog->products[$sku->prod_id])) {
						$product = $modelProd->byId($sku->prod_id, array('cascaded' => 'N', 'fields' => $prodFields, 'catelog' => $catelog));
						$product->cateSkus = array();
						/*catelog sku*/
						$cateSku = $modelCate->skuById($sku->cate_sku_id, $cateSkuOptions);
						$cateSku->skus = array($sku);
						$product->cateSkus[$cateSku->id] = $cateSku;
						$catelog->products[$product->id] = $product;
					} else {
						$product = $catelog->products[$sku->prod_id];
						if (!isset($product->cateSkus[$sku->cate_sku_id])) {
							/*catelog sku*/
							$cateSku = $modelCate->skuById($sku->cate_sku_id, $cateSkuOptions);
							$cateSku->skus = array($sku);
							$product->cateSkus[$cateSku->id] = $cateSku;
						} else {
							$product->cateSkus[$sku->cate_sku_id]->skus[] = $sku;
						}
					}
				}
				unset($sku->cate_id);
				unset($sku->cate_sku_id);
				unset($sku->prod_id);
			}
		}

		return new \ResponseData(array('order' => $order, 'catelogs' => $catelogs));
	}
	/**
	 * 保存订单反馈信息并通知用户
	 */
	public function feedback_action($mpid, $order) {
		$order = $this->model('app\merchant\order')->byId($order);
		$order->extPropValue = json_decode($order->ext_prop_value);

		$feedback = $this->getPostJson();
		$pv = empty($feedback) ? '{}' : \TMS_MODEL::toJson($feedback);

		$rst = $this->model()->update(
			'xxt_merchant_order',
			array(
				'feedback' => $pv,
				'order_status' => 3, // 已确认
			),
			"id=$order->id"
		);
		/*发通知*/
		$order->feedback = json_decode($pv);
		$this->_notify($mpid, 'feedback', $order);

		return new \ResponseData($rst);
	}
	/**
	 * 完成订单处理
	 *
	 * @param string $mpid
	 * @param int $order
	 *
	 */
	public function finish_action($mpid, $order) {
		$modelOrd = $this->model('app\merchant\order');
		// notify
		$order = $modelOrd->byId($order);
		$order->extPropValue = json_decode($order->ext_prop_value);

		$feedback = $this->getPostJson();
		$pv = empty($feedback) ? '{}' : \TMS_MODEL::toJson($feedback);

		$rst = $this->model()->update(
			'xxt_merchant_order',
			array(
				'feedback' => $pv,
				'order_status' => 5, // 已完成
			),
			"id=$order->id"
		);
		/*发通知*/
		$order->feedback = json_decode($pv);
		$this->_notify($mpid, 'finish', $order);

		return new \ResponseData($rst);
	}
	/**
	 * 取消订单
	 *
	 * @param string $mpid
	 * @param int $order
	 *
	 */
	public function cancel_action($mpid, $order) {
		$modelOrd = $this->model('app\merchant\order');
		$rst = $modelOrd->cancel($order);
		// notify
		$order = $modelOrd->byId($order);
		$order->extPropValue = json_decode($order->ext_prop_value);

		$feedback = $this->getPostJson();
		$pv = empty($feedback) ? '{}' : \TMS_MODEL::toJson($feedback);

		$rst = $this->model()->update(
			'xxt_merchant_order',
			array(
				'feedback' => $pv,
			),
			"id=$order->id"
		);
		/*发通知*/
		$order->feedback = json_decode($pv);
		$this->_notify($mpid, 'cancel', $order);

		return new \ResponseData($rst);
	}
	/**
	 * 通知客户已受理订单
	 */
	private function _notify($mpid, $action, $order) {
		$modelProd = $this->model('app\merchant\product');
		$modelTmpl = $this->model('matter\tmplmsg');
		$products = json_decode($order->products);
		$actionTmplmsg = $action . '_order_tmplmsg';
		$pendings = array();
		foreach ($products as $product) {
			/**/
			$product = $modelProd->byId($product->id, array('cascaded' => 'Y'));
			if (empty($product->catelog->{$actionTmplmsg})) {
				continue;
			}
			$orderTmplmsgId = $product->catelog->{$actionTmplmsg};
			/*获得模板消息定义*/
			if (isset($pendings[$orderTmplmsgId]['mapping'])) {
				$mapping = $pendings[$orderTmplmsgId]['mapping'];
			} else {
				$mapping = $modelTmpl->mappingById($orderTmplmsgId);
				if (false === $mapping) {
					continue;
				}
				$tmplmsg = $modelTmpl->byId($mapping->msgid, array('cascaded' => 'Y'));
				if (empty($tmplmsg->params)) {
					continue;
				}
				$pendings[$orderTmplmsgId] = array(
					'mapping' => $mapping,
					'tmplmsg' => $tmplmsg,
					'onlyOrder' => true,
				);
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
					$pendings[$orderTmplmsgId]['onlyOrder'] = false;
					break;
				case 'order':
					if ($p->id === '__orderSn') {
						$v = $order->trade_no;
					} else if ($p->id === '__orderState') {
						$v = '未付款';
					} else {
						if (isset($order->extPropValue->{$product->catelog->id}->{$p->id})) {
							$v = $order->extPropValue->{$product->catelog->id}->{$p->id};
						} else {
							$v = '';
						}
					}
					break;
				case 'feedback':
					if (isset($order->feedback->{$product->catelog->id}->{$p->id})) {
						$v = $order->feedback->{$product->catelog->id}->{$p->id};
					} else {
						$v = '';
					}
					break;
				case 'text':
					$v = $p->id;
					break;
				}
				$data[$k] = $v;
			}
			//保存数据
			$pendings[$orderTmplmsgId]['data'][] = $data;
		}
		/*订单访问地址*/
		$url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/merchant/order";
		$url .= "?mpid=" . $mpid;
		$url .= "&shop=" . $order->sid;
		$url .= "&order=" . $order->id;
		/*发送消息*/
		foreach ($pendings as $pending) {
			$tmplmsg = $pending['tmplmsg'];
			$datas = $pending['data'];
			if ($pending['onlyOrder'] === true) {
				/*如果只包含订单信息则只发送一条*/
				$datas = array($pending['data'][0]);
			}
			foreach ($datas as $data) {
				/*发送模版消息*/
				$this->tmplmsgSendByOpenid($mpid, $tmplmsg->id, $order->buyer_openid, $data, $url);
				break;
			}
		}
		return true;
	}
}