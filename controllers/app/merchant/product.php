<?php
namespace app\merchant;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 商品
 */
class product extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 进入商品展示页
	 *
	 * @param string $mpid mpid'id
	 * @param int $shop shop'id
	 * @param int $catelog
	 * @param int $product
	 *
	 * @return
	 */
	public function index_action($mpid, $shop, $catelog, $product, $mocker = null, $code = null) {
		//$openid = $this->doAuth($mpid, $code, $mocker);
		$options = array(
			'fields' => 'title',
			'cascaded' => 'N',
		);
		$page = $this->model('app\merchant\page')->byType('product', $shop, $catelog, 0, $options);
		if (empty($page)) {
			$this->outputError('指定的页面不存在');
		} else {
			$page = $page[0];
			\TPL::assign('title', $page->title);
			\TPL::output('/app/merchant/product');
			exit;
		}
	}
	/**
	 * 获得商品的页面定义
	 */
	public function pageGet_action($mpid, $shop, $catelog, $product) {
		// current visitor
		$user = $this->getUser($mpid);
		// page
		$page = $this->model('app\merchant\page')->byType('product', $shop, $catelog);
		if (empty($page)) {
			return new \ResponseError('没有获得商品页定义');
		}
		$page = $page[0];

		$params = array(
			'user' => $user,
			'page' => $page,
		);

		return new \ResponseData($params);
	}
	/**
	 * 获得符合条件的商品
	 *
	 * @param int $catelog 商品所属的分类
	 * @param string $pvids 逗号分隔的商品属性值
	 * @param int $beginAt 商品的sku的有效期开始时间
	 * @param int $endAt 商品的sku的有效期结束时间
	 *
	 */
	public function list_action($catelog, $pvids = '', $beginAt = 0, $endAt = 0, $cascaded = 'N', $hasSku = 'N') {
		/*分类*/
		$modelCate = $this->model('app\merchant\catelog');
		$cateFields = 'id,sid,name,pattern,pages';
		$catelog = $modelCate->byId($catelog, array('fields' => $cateFields, 'cascaded' => 'Y'));
		/*商品属性*/
		$pvids = empty($pvids) ? array() : explode(',', $pvids);
		/*商品状态*/
		$state = array(
			'disabled' => 'N',
			'active' => 'Y',
		);
		/*有效期，缺省为当天*/
		$beginAt === 0 && ($beginAt = mktime(0, 0, 0));
		$endAt === 0 && ($endAt = mktime(23, 59, 59));

		$options = array(
			'fields' => 'id,cate_id,name,main_img,img,detail_img,detail_text,prop_value,sku_info',
			'state' => $state,
			'cascaded' => $cascaded,
			'beginAt' => $beginAt,
			'endAt' => $endAt,
		);
		$modelProd = $this->model('app\merchant\product');
		$products = $modelProd->byPropValue($catelog, $pvids, $options);
		if ($hasSku === 'Y') {
			$products2 = array();
			/*过滤掉没有sku的商品*/
			$cateSkus = $modelCate->skus($catelog->id);
			foreach ($products as &$prod) {
				if ($prod->cateSkus === false) {
					foreach ($cateSkus as $cs) {
						if ($cs->can_autogen === 'Y') {
							$skus = $modelCate->autogenByCateSku($cs, $beginAt, $endAt);
							if (!empty($skus)) {
								if ($prod->cateSkus === false) {
									$prod->cateSkus = new \stdClass;
								}
								$cs->skus = $skus;
								$prod->cateSkus->{$cs->id} = $cs;
							}
						}
					}
				} else {
					$i = $j = 0;
					foreach ($prod->cateSkus as $cs) {
						$i++;
						if (!$this->_checkSkuValidity($cs->skus, $beginAt, $endAt)) {
							unset($prod->cateSkus->{$cs->id});
							$j++;
						}
					}
					$i === $j && $prod->cateSkus = false;
				}
				if ($prod->cateSkus !== false) {
					/*检查在指定的时间范围内是否有sku*/
					$products2[] = $prod;
				}
			}
			return new \ResponseData(array('products' => $products2, 'catelog' => $catelog));
		} else {
			return new \ResponseData(array('products' => $products, 'catelog' => $catelog));
		}
	}
	/**
	 * 只要在连续的sku中，有任何一个sku在指定时间范围内已经被专用，就整体不可用
	 */
	private function _checkSkuValidity(&$skus, $beginAt, $endAt) {
		foreach ($skus as $sku) {
			if ((int) $sku->quantity === 0 && (int) $sku->validity_begin_at >= $beginAt && (int) $sku->validity_end_at <= $endAt) {
				return false;
			}
		}
		return true;
	}
	/**
	 *
	 * @param int $product
	 */
	public function get_action($product) {
		$modelProd = $this->model('app\merchant\product');
		$options = array(
			'cascaded' => 'Y',
		);
		$prod = $modelProd->byId($product, $options);

		return new \ResponseData($prod);
	}
}