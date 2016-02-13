<?php
namespace app\merchant;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 查看订单
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
	 * 进入发起订单页
	 *
	 * 要求当前用户必须是关注用户
	 *
	 * @param string $mpid mpid'id
	 * @param int $product
	 * @param int $sku
	 *
	 */
	public function index_action($mpid, $shop, $order = '', $mocker = null, $code = null) {
		/**
		 * 获得当前访问用户
		 */
		$openid = $this->doAuth($mpid, $code, $mocker);
		// page
		$options = array(
			'cascaded' => 'N',
			'fields' => 'title',
		);
		$page = $this->model('app\merchant\page')->byType('order', $shop, 0, 0, $options);
		$page = $page[0];

		\TPL::assign('title', $page->title);
		\TPL::output('/app/merchant/order');
		exit;
	}
	/**
	 * 获得订单页面定义
	 *
	 * @param string mpid
	 * @param int mpid
	 * @param int order
	 */
	public function pageGet_action($mpid, $shop, $order = '') {
		// current visitor
		$user = $this->getUser($mpid);
		// shop
		$shop = $this->model('app\merchant\shop')->byId($shop, array('fields' => 'id,title,order_status,buyer_api,payby'));
		$shop->order_status = empty($shop->order_status) ? new \stdClass : json_decode($shop->order_status);
		$shop->payby = empty($shop->payby) ? array() : explode(',', $shop->payby);
		// page
		$page = $this->model('app\merchant\page')->byType('order', $shop->id);
		if (empty($page)) {
			return new \ResponseError('没有获得订单页定义');
		}
		$page = $page[0];

		$params = array(
			'shop' => $shop,
			'user' => $user,
			'page' => $page,
		);
		/*联系人信息*/
		if (!empty($shop->buyer_api)) {
			$buyerApi = json_decode($shop->buyer_api);
			$authid = $buyerApi->authid;
			$modelMemb = $this->model('user/member');
			if ($existentMember = $modelMemb->byOpenid($mpid, $user->openid, 'name,mobile,email', $authid)) {
				$params['orderInfo'] = array(
					'receiver_name' => $existentMember->name,
					'receiver_mobile' => $existentMember->mobile,
					'receiver_email' => $existentMember->email,
				);
			}
		}

		return new \ResponseData($params);
	}
	/**
	 *
	 * 获得订单页中指定组件的定制信息
	 *
	 * @param string $page order|ordernew
	 * @param string $comp skus
	 * @param int $shop
	 * @param int $catelog
	 * @param int $product
	 */
	public function componentGet_action($page, $comp, $shop, $catelog = 0, $product = 0) {
		// page
		$pageType = $page . '.' . $comp;
		$page = $this->model('app\merchant\page')->byType($pageType, $shop, $catelog, 0);
		if (empty($page)) {
			$page = array('html' => '', 'css' => '', 'js' => '');
		} else {
			$page = $page[0];
		}

		return new \ResponseData($page);
	}
	/**
	 * 获得指定订单的完整信息
	 *
	 * @param string $mpid
	 * @param int $order
	 *
	 * @return
	 */
	public function get_action($mpid, $order) {
		$order = $this->model('app\merchant\order')->byId($order);
		$order->extPropValues = empty($order->ext_prop_value) ? new \stdClass : json_decode($order->ext_prop_value);
		$order->feedback = empty($order->feedback) ? new \stdClass : json_decode($order->feedback);

		/*按分类和商品对sku进行分组*/
		$skus = $order->skus;
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
	 * 修改订单
	 *
	 * @param string $mpid
	 * @param int $order
	 *
	 * @return int order's id
	 */
	public function modify_action($mpid, $order) {
		$user = $this->getUser($mpid, array('verbose' => array('fan' => 'Y')));
		if (empty($user->openid)) {
			return new \ResponseError('无法获得当前用户身份信息');
		}

		$orderInfo = $this->getPostJson();

		$rst = $this->model('app\merchant\order')->modify($mpid, $user, $order, $orderInfo);

		//$this->_notify($mpid, $order);

		return new \ResponseData($rst);
	}
	/**
	 * 取消订单
	 *
	 * @param string $mpid
	 * @param int $order
	 */
	public function cancel_action($mpid, $order) {
		$modelOrd = $this->model('app\merchant\order');
		$rst = $modelOrd->cancelByBuyer($order);

		return new \ResponseData($rst);
	}
	/**
	 * 通知客服有新订单
	 */
	private function _notify($mpid, $order) {
		/*客服员工*/
		$staffs = $this->model('app\merchant\shop')->staffAcls($mpid, $order->sid, 'c');
		if (empty($staffs)) {
			return false;
		}
		/*每个产品独立发通知*/
		$modelProd = $this->model('app\merchant\product');
		$modelTmpl = $this->model('matter\tmplmsg');
		$modelFan = $this->model('user/fans');
		$products = json_decode($order->products);
		$pendings = array();
		foreach ($products as $product) {
			$product = $modelProd->byId($product->id, array('cascaded' => 'Y'));
			/*获得模板消息定义*/
			if (isset($pendings[$product->catelog->submit_order_tmplmsg]['mapping'])) {
				$mapping = $pendings[$product->catelog->submit_order_tmplmsg]['mapping'];
			} else {
				$mapping = $modelTmpl->mappingById($product->catelog->submit_order_tmplmsg);
				if (false === $mapping) {
					continue;
				}
				$tmplmsg = $modelTmpl->byId($mapping->msgid, array('cascaded' => 'Y'));
				if (empty($tmplmsg->params)) {
					continue;
				}
				$pendings[$product->catelog->submit_order_tmplmsg]['mapping'] = $mapping;
				$pendings[$product->catelog->submit_order_tmplmsg]['tmplmsg'] = $tmplmsg;
				$pendings[$product->catelog->submit_order_tmplmsg]['onlyOrder'] = true;
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
					$pendings[$product->catelog->submit_order_tmplmsg]['onlyOrder'] = false;
					break;
				case 'order':
					if ($p->id === '__orderSn') {
						$v = $order->trade_no;
					} else if ($p->id === '__orderState') {
						$v = '待付款';
					} else {
						$v = '';
						if (!empty($order->extPropValue->{$product->cate_id})) {
							$epv = $order->extPropValue->{$product->cate_id};
							if (!empty($epv->{$p->id})) {
								$v = $epv->{$p->id};
							}
						}
					}
					break;
				case 'text':
					$v = $p->id;
					break;
				}
				$data[$k] = $v;
			}
			//保存数据
			$pendings[$product->catelog->submit_order_tmplmsg]['data'][] = $data;
		}
		/*订单访问地址*/
		$url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/op/merchant/order";
		$url .= "?mpid=" . $mpid;
		$url .= "&shop=" . $order->sid;
		$url .= "&order=" . $order->id;
		foreach ($pendings as $pending) {
			$tmplmsg = $pending['tmplmsg'];
			$datas = $pending['data'];
			if ($pending['onlyOrder'] === true) {
				/*如果只包含订单信息则只发送一条*/
				$datas = array($pending['data'][0]);
			}
			foreach ($datas as $data) {
				/*发送模版消息*/
				foreach ($staffs as &$staff) {
					switch ($staff->idsrc) {
					case 'M':
						if (isset($staff->fan)) {
							$fan = $staff->fan;
						} else {
							$fan = $modelFan->byMid($staff->identity);
							$staff->fan = $fan;
						}
						if ($fan && !empty($fan->openid)) {
							$this->tmplmsgSendByOpenid($mpid, $tmplmsg->id, $fan->openid, $data, $url);
						}
						break;
					}
				}
			}
		}

		return true;
	}
}