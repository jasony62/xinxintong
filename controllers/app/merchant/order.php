<?php
namespace app\merchant;

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
	 * 进入发起订单页
	 *
	 * 要求当前用户必须是关注用户
	 *
	 * @param string $mpid mpid'id
	 * @param int $product
	 * @param int $sku
	 *
	 */
	public function index_action($mpid, $product = null, $sku = null, $order = null, $mocker = null, $code = null) {
		/**
		 * 获得当前访问用户
		 */
		$openid = $this->doAuth($mpid, $code, $mocker);

		\TPL::output('/app/merchant/order');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($mpid, $shop) {
		// current visitor
		$user = $this->getUser($mpid);
		// page
		$page = $this->model('app\merchant\page')->byType('order', $shop);
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
	 * 获得指定订单的完整信息
	 *
	 * @param string $mpid
	 * @param int $order
	 *
	 * @return
	 */
	public function get_action($mpid, $order = null) {
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
			$cateFields = 'id,sid,name,pattern';
			$prodFields = 'id,sid,cate_id,name,main_img,img,detail_text,detail_text,prop_value,buy_limit,sku_info';
			$skuFields = 'id,sid,cate_id,cate_sku_id,icon_url,price,ori_price,quantity,validity_begin_at,validity_end_at,sku_value';
			foreach ($skus as &$sku) {
				if (!isset($catelogs[$sku->cate_id])) {
					/*catelog*/
					$catelog = $modelCate->byId($sku->cate_id, array('fields' => $cateFields, 'cascaded' => 'Y'));
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
	 * 创建订单
	 *
	 * @param string $mpid
	 *
	 * @return int order's id
	 */
	public function create_action($mpid) {
		$user = $this->getUser($mpid, array('verbose' => array('fan' => 'Y')));
		if (empty($user->openid)) {
			return new \ResponseError('无法获得当前用户身份信息');
		}

		$orderInfo = $this->getPostJson();

		$order = $this->model('app\merchant\order')->create($mpid, $user, $orderInfo);

		$this->notify($mpid, $order);

		return new \ResponseData($order->id);
	}
	/**
	 * 通知客服有新订单
	 */
	private function notify($mpid, $order) {
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
		foreach ($products as $product) {
			$product = $modelProd->byId($product->id, array('cascaded' => 'Y'));
			$mapping = $modelTmpl->mappingById($product->catelog->submit_order_tmplmsg);
			if (false === $mapping) {
				continue;
			}
			/*获得模板消息定义*/
			$tmplmsg = $modelTmpl->byId($mapping->msgid, array('cascaded' => 'N'));
			if (empty($tmplmsg->params)) {
				continue;
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
						$v = $product->propValue2->{$p->id}->name;
					}
					break;
				case 'order':
					if ($p->id === '__orderSn') {
						$v = $order->trade_no;
					} else if ($p->id === '__orderState') {
						$v = '待付款';
					} else {
						$v = $order->extPropValue->{$p->id};
					}
					break;
				case 'text':
					$v = $p->id;
					break;
				}
				$data[$k] = $v;
			}
			/*订单访问地址*/
			$url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/op/merchant/order";
			$url .= "?mpid=" . $mpid;
			$url .= "&shop=" . $order->sid;
			$url .= "&order=" . $order->id;
			/*发送模版消息*/
			foreach ($staffs as $staff) {
				switch ($staff->idsrc) {
				case 'M':
					$fan = $modelFan->byMid($staff->identity);
					$this->tmplmsgSendByOpenid($mpid, $tmplmsg->id, $fan->openid, $data, $url);
					break;
				}
			}
		}

		return true;
	}
}