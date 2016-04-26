<?php
namespace site\fe\matter\merchant;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 新建订单
 */
class ordernew extends \site\fe\matter\base {
	/**
	 * 进入发起订单页
	 *
	 * 要求当前用户必须是注册用户
	 *
	 * @param string $site mpid'id
	 * @param int $shop
	 *
	 */
	public function index_action($site, $shop) {
		/*检查进入规则*/
		$shop = $this->model('matter\merchant\shop')->byId($shop, array('fields' => 'id,buyer_api'));
		$shop->buyer_api = json_decode($shop->buyer_api);
		$this->checkEntryRule($site, $shop, $this->who, true);
		/*page*/
		$options = array(
			'cascaded' => 'N',
			'fields' => 'title',
		);
		$page = $this->model('matter\merchant\page')->byType('ordernew', $shop->id, 0, 0, $options);
		$page = $page[0];

		\TPL::assign('title', $page->title);
		\TPL::output('/site/fe/matter/merchant/ordernew');
		exit;
	}
	/**
	 * 检查发起订单规则
	 */
	protected function checkEntryRule($siteId, &$app, &$user, $redirect = false) {
		if (!isset($user->members->{$app->buyer_api->authid})) {
			/*内置页面*/
			$aMemberSchemas = array($app->buyer_api->authid);
			if ($redirect) {
				/*页面跳转*/
				$this->gotoMember($siteId, $aMemberSchemas, $user->uid);
			} else {
				/*返回地址*/
				$this->gotoMember($siteId, $aMemberSchemas, $user->uid, false);
			}
		}

		return true;
	}
	/**
	 * 获得订单页面定义
	 *
	 * @param string mpid
	 * @param int mpid
	 * @param int order
	 */
	public function pageGet_action($site, $shop) {
		// current visitor
		$user = $this->who;
		// shop
		$shop = $this->model('matter\merchant\shop')->byId($shop, array('fields' => 'id,title,order_status,buyer_api,payby'));
		$shop->order_status = empty($shop->order_status) ? new \stdClass : json_decode($shop->order_status);
		$shop->payby = empty($shop->payby) ? array() : explode(',', $shop->payby);
		// page
		$page = $this->model('matter\merchant\page')->byType('ordernew', $shop->id);
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
			$schemaId = $buyerApi->authid;
			if ($member = $user->members->{$schemaId}) {
				$modelMemb = $this->model('site\user\member');
				if ($member = $modelMemb->byId($member->id, array('fields' => 'name,mobile,email'))) {
					$params['orderInfo'] = array(
						'receiver_name' => $member->name,
						'receiver_mobile' => $member->mobile,
						'receiver_email' => $member->email,
					);
				}
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
		$page = $this->model('matter\merchant\page')->byType($pageType, $shop, $catelog, 0);
		if (empty($page)) {
			$page = array('html' => '', 'css' => '', 'js' => '');
		} else {
			$page = $page[0];
		}

		return new \ResponseData($page);
	}
	/**
	 * 创建订单
	 *
	 * @param string $site
	 *
	 * @return int order's id
	 */
	public function create_action($site, $shop) {
		$user = $this->who;
		$orderInfo = $this->getPostJson();
		//if (empty((array) $orderInfo->skus)) {
		//	return new \ResponseError('没有选择商品库存，无法创建订单');
		//}

		$order = $this->model('matter\merchant\order')->create($site, $user, $orderInfo);
		//$this->_notify($site, $order);

		/*保留联系人信息*/
		$shop = $this->model('matter\merchant\shop')->byId($shop);
		/*if (!empty($shop->buyer_api)) {
			$buyerApi = json_decode($shop->buyer_api);
			$authid = $buyerApi->authid;
			$modelMemb = $this->model('user/member');
			$member = new \stdClass;
			$member->name = isset($orderInfo->receiver_name) ? $orderInfo->receiver_name : '';
			$member->mobile = isset($orderInfo->receiver_mobile) ? $orderInfo->receiver_mobile : '';
			$member->email = isset($orderInfo->receiver_email) ? $orderInfo->receiver_email : '';
			if ($existentMember = $modelMemb->byOpenid($site, $user->openid, 'mid', $authid)) {
				$rst = $modelMemb->modify($site, $authid, $existentMember->mid, $member);
			} else {
				$rst = $modelMemb->create2($site, $authid, $user->fan->fid, $member);
			}
			if (false === $rst[0]) {
				return new \ResponseError($rst[1]);
			}
		}*/
		return new \ResponseData($order->id);
	}
	/**
	 * 通知客服有新订单
	 */
	private function _notify($site, $order) {
		/*客服员工*/
		$staffs = $this->model('matter\merchant\shop')->staffAcls($site, $order->sid, 'c');
		if (empty($staffs)) {
			return false;
		}
		/*每个产品独立发通知*/
		$modelProd = $this->model('matter\merchant\product');
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
		$url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/op/matter/merchant/order";
		$url .= "?site=" . $site;
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
							$this->tmplmsgSendByOpenid($site, $tmplmsg->id, $fan->openid, $data, $url);
						}
						break;
					}
				}
			}
		}

		return true;
	}
}