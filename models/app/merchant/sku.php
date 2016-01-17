<?php
namespace app\merchant;
/**
 * 库存
 */
class sku_model extends \TMS_MODEL {
	/**
	 *
	 * @param string $id
	 */
	public function &byId($id, $options = array()) {
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cateSku = isset($options['cateSku']) ? $options['cateSku'] : false;

		$q = array(
			$fields,
			'xxt_merchant_product_sku',
			"id=$id",
		);
		$sku = $this->query_obj_ss($q);
		if ($sku) {
			if ($cascaded === 'Y') {
				if ($cateSku === false) {
					$modelCate = \TMS_MODEL::M('app\merchant\catelog');
					$cateSku = $modelCate->skuById($sku->cate_sku_id);
				}
				$sku->cateSku = $cateSku;
			}
		}

		return $sku;
	}
	/**
	 * 根据ID获得sku的列表
	 *
	 * @param string $ids
	 */
	public function byIds($ids, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : 'id,sid,cate_id,cate_sku_id,icon_url,ori_price,price,prod_id,product_code,unlimited_quantity,quantity,sku_value,validity_begin_at,validity_end_at,required';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'N';

		/*check parameters*/
		$checkedIds = array();
		$ids = explode(',', $ids);
		foreach ($ids as $skuId) {
			!empty($skuId) && $checkedIds[] = $skuId;
		}
		if (empty($checkedIds)) {
			return array();
		}
		$checkedIds = implode(',', $checkedIds);
		/*query*/
		$q = array(
			$fields,
			'xxt_merchant_product_sku',
			"id in ($checkedIds)",
		);
		$q2 = array('o' => 'validity_begin_at');

		$skus = $this->query_objs_ss($q, $q2);
		if (!empty($skus) && $cascaded === 'Y') {
			$cateSkus = array();
			$modelCate = \TMS_MODEL::M('app\merchant\catelog');
			foreach ($skus as &$sku) {
				if (!isset($cateSkus[$sku->cate_sku_id])) {
					$cateSkus[$sku->cate_sku_id] = $modelCate->skuById($sku->cate_sku_id);
				}
				$sku->cateSku = $cateSkus[$sku->cate_sku_id];
			}
		}

		return $skus;
	}
	/**
	 * 获得指定产品下符合条件的sku
	 *
	 * @param int $productId
	 * @param array options
	 *
	 */
	public function &byProduct($productId, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : 'id,cate_sku_id,icon_url,ori_price,price,product_code,unlimited_quantity,quantity,sku_value,validity_begin_at,validity_end_at,required';
		$beginAt = isset($options['beginAt']) ? $options['beginAt'] : 0;
		$endAt = isset($options['endAt']) ? $options['endAt'] : 0;

		$q = array(
			$fields,
			'xxt_merchant_product_sku',
			"prod_id=$productId",
		);
		/*根据sku的状态*/
		if (isset($options['state'])) {
			$state = $options['state'];
			isset($state['disabled']) && $q[2] .= " and disabled='" . $state['disabled'] . "'";
			isset($state['active']) && $q[2] .= " and active='" . $state['active'] . "'";
		}
		/*根据sku的有效期*/
		if ($beginAt || $endAt) {
			$q[2] .= " and (has_validity='N' or (has_validity>='Y'";
			$beginAt && $q[2] .= " and validity_begin_at>=$beginAt";
			$endAt && $q[2] .= " and validity_end_at<=$endAt";
			$q[2] .= "))";
		}
		$q2 = array('o' => 'validity_begin_at');

		$skus = $this->query_objs_ss($q, $q2);
		/*sku的分类信息*/
		$cateSkus = array();
		$modelCate = \TMS_MODEL::M('app\merchant\catelog');
		if (!empty($skus)) {
			$cateSkuOptions = array(
				'fields' => 'id,name,has_validity,require_pay',
			);
			foreach ($skus as &$sku) {
				if (isset($cateSkus[$sku->cate_sku_id])) {
					$cateSkus[$sku->cate_sku_id]->skus[] = $sku;
				} else {
					$cateSku = $modelCate->skuById($sku->cate_sku_id, $cateSkuOptions);
					$cateSku->skus = array($sku);
					$cateSkus[$sku->cate_sku_id] = $cateSku;
				}
				unset($sku->cate_sku_id);
			}
		} else {
			/*检查是否生成过sku，如果没有生成过返回false*/
			$q = array(
				'count(*)',
				'xxt_merchant_product_gensku_log',
				"prod_id=$productId and begin_at=$beginAt and end_at=$endAt",
			);
			if (0 === (int) $this->query_val_ss($q)) {
				$cateSkus = false;
			}
		}
		return $cateSkus;
	}
	/**
	 * 删除库存
	 *
	 * @param int $skuId
	 */
	public function remove($skuId) {
		$sku = $this->byId($skuId, 'N');
		if ($sku->used === 'Y') {
			$rst = $this->update('xxt_merchant_product_sku', array('disabled' => 'Y'), "id=$skuId");
		} else {
			$rst = $this->delete('xxt_merchant_product_sku', "id=$skuId");
		}

		return $rst;
	}
	/**
	 * 订购sku
	 *
	 * @param int $skuId
	 * @param int $count
	 */
	public function order($skuId, $count) {
		$sql = 'update xxt_merchant_product_sku';
		$sql .= " set used='Y',quantity=quantity-$count";
		$sql .= " where id=$skuId";

		$rst = $this->update($sql);

		return $rst;
	}
}