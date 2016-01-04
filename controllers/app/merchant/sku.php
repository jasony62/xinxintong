<?php
namespace app\merchant;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 商品库存
 */
class sku extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 获得指定商品下的sku
	 *
	 * @param int $shop id
	 * @param int $catelog id
	 * @param int $product id
	 * @param int $beginAt 有效期开始时间
	 * @param int $endAt 有效期结束时间
	 * @param string $autogen 是否自动生成
	 *
	 */
	public function byProduct_action($mpid, $shop, $catelog, $product, $beginAt = 0, $endAt = 0, $autogen = 'N') {
		$user = $this->getUser($mpid);

		/*有效期，缺省为当天*/
		$beginAt === 0 && ($beginAt = mktime(0, 0, 0));
		$endAt === 0 && ($endAt = mktime(23, 59, 59));
		/*sku状态*/
		$state = array(
			'disabled' => 'N',
			'active' => 'Y',
		);

		$options = array(
			'state' => $state,
			'beginAt' => $beginAt,
			'endAt' => $endAt,
		);

		$modelSku = $this->model('app\merchant\sku');
		$cateSkus = $modelSku->byProduct($product, $options);

		if ($autogen === 'Y' && $beginAt != 0 && $endAt != 0) {
			$q = array(
				'1',
				'xxt_merchant_product_gensku_log',
				"prod_id=$product and begin_at=$beginAt and end_at=$endAt",
			);
			if ('1' !== $modelSku->query_val_ss($q)) {
				$this->_autogen($user->openid, $catelog, $product, $beginAt, $endAt, $cateSkus);
				$modelSku->insert(
					'xxt_merchant_product_gensku_log',
					array(
						'mpid' => $mpid,
						'sid' => $shop,
						'cate_id' => $catelog,
						'prod_id' => $product,
						'creater' => $user->openid,
						'create_at' => time(),
						'begin_at' => $beginAt,
						'end_at' => $endAt,
					),
					false
				);
			}
		}

		return new \ResponseData($cateSkus);
	}
	/**
	 *
	 * @param string $mpid
	 * @param string $ids splited by comma
	 *
	 * @return
	 */
	public function list_action($mpid, $ids) {
		$modelSku = $this->model('app\merchant\sku');
		$skus = $modelSku->byIds($ids);
		/*按分类和商品进行分组*/
		$catelogs = array();
		if (!empty($skus)) {
			$modelCate = $this->model('app\merchant\catelog');
			$modelProd = $this->model('app\merchant\product');
			$cateFields = 'id,sid,name,pattern,pages';
			$prodFields = 'id,sid,cate_id,name,main_img,img,detail_text,detail_text,prop_value,buy_limit,sku_info';
			$cateSkuOptions = array(
				'fields' => 'id,name,has_validity,require_pay',
			);
			foreach ($skus as &$sku) {
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
			}
		}

		return new \ResponseData($catelogs);
	}
	/**
	 * 自动生成指定商品下的sku
	 *
	 * @param int $catelogId
	 * @param int $productId
	 * @param int $beginAt 有效期开始时间
	 * @param int $endAt 有效期结束时间
	 * @param string $autogen 是否自动生成
	 *
	 */
	private function _autogen($creater, $catelogId, $productId, $beginAt, $endAt, &$existedCateSkus) {
		$modelCate = $this->model('app\merchant\catelog');
		$modelSku = $this->model('app\merchant\sku');
		$cateSkuOptions = array(
			'fields' => 'mpid,id,sid,cate_id,name,has_validity,require_pay,can_autogen,autogen_rule',
		);
		$cateSkus = $modelCate->skus($catelogId, $cateSkuOptions);
		foreach ($cateSkus as $cs) {
			if ($cs->can_autogen === 'Y') {
				$merged = array();
				$existedCateSku = empty($existedCateSkus[$cs->id]) ? false : $existedCateSkus[$cs->id];
				$newSkus = $modelCate->autogenByCateSku($cs, $beginAt, $endAt);
				foreach ($newSkus as $ns) {
					if (false === $existedCateSku || !$this->_isSkuExisted($existedCateSku, $ns)) {
						$gened = array(
							'mpid' => $cs->mpid,
							'sid' => $cs->sid,
							'cate_id' => $cs->cate_id,
							'cate_sku_id' => $cs->id,
							'prod_id' => $productId,
							'create_at' => time(),
							'creater' => $creater,
							'creater_src' => 'F',
							'sku_value' => '{}',
							'ori_price' => $ns->price,
							'price' => $ns->price,
							'quantity' => $ns->quantity,
							'has_validity' => $cs->has_validity,
							'validity_begin_at' => $ns->validity_begin_at,
							'validity_end_at' => $ns->validity_end_at,
							'product_code' => '',
							'used' => 'Y',
							'active' => 'Y',
						);
						$skuId = $this->model()->insert('xxt_merchant_product_sku', $gened, true);
						$merged[] = $modelSku->byId($skuId);
					}
				}
				if (!empty($merged)) {
					if ($existedCateSku) {
						$existedCateSku->skus = array_merge($ecs->skus, $merge);
					} else {
						$cs->skus = $merged;
						unset($cs->can_autogen);
						$existedCateSkus[$cs->id] = $cs;
					}
				}
			}
		}

		return true;
	}
	/**
	 * 检查sku是否已经存在
	 */
	private function _isSkuExisted($existedCateSku, $checkedSku) {
		foreach ($existedCateSku->skus as $existed) {
			if ($existed->validity_begin_at == $checkedSku->validity_begin_at && $existed->validity_end_at == $checkedSku->validity_end_at) {
				return true;
			}
		}
		return false;
	}
}