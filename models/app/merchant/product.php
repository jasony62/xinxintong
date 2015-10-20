<?php
namespace app\merchant;
/**
 *
 */
class product_model extends \TMS_MODEL {
	/**
	 * $id
	 */
	public function &byId($id, $cascaded = 'N') {
		$q = array(
			'*',
			'xxt_merchant_product p',
			"id=$id",
		);

		$prod = $this->query_obj_ss($q);

		if ($cascaded === 'Y') {
			$cascaded = $this->cascaded($id);
			$prod->catelog = $cascaded->catelog;
			$prod->propValue2 = $cascaded->propValue2;
		}

		return $prod;
	}
	/**
	 *
	 *
	 * @param int $shopId
	 * @param int $cateId
	 * @param array $state
	 *
	 */
	public function &byShopId($shopId, $cateId, $state = array()) {
		$q = array(
			'*',
			'xxt_merchant_product p',
			"sid=$shopId and cate_id=$cateId",
		);
		isset($state['disabled']) && $q[2] .= " and disabled='" . $state['disabled'] . "'";
		isset($state['active']) && $q[2] .= " and active='" . $state['active'] . "'";

		$q2 = array('o' => 'create_at desc');

		$products = $this->query_objs_ss($q, $q2);

		return $products;
	}
	/**
	 * 根据属性值获得产品列表
	 */
	public function &byPropValue($cateId, $vids, $cascaded = 'Y', $state = array()) {
		$q = array(
			'*',
			'xxt_merchant_product p',
			"cate_id=$cateId",
		);
		foreach ($vids as $vid) {
			$q[2] .= " and prop_value like '%:\"$vid\"%'";
		}
		isset($state['disabled']) && $q[2] .= " and disabled='" . $state['disabled'] . "'";
		isset($state['active']) && $q[2] .= " and active='" . $state['active'] . "'";

		$products = $this->query_objs_ss($q);

		if ($cascaded === 'Y') {
			foreach ($products as &$prod) {
				$cascaded = $this->cascaded($prod->id);
				foreach ($cascaded as $k => $v) {
					$prod->{$k} = $v;
				}
			}
		}

		return $products;
	}
	/**
	 * $id catelog's id
	 */
	public function &cascaded($id) {
		$cascaded = new \stdClass;

		$prod = $this->byId($id);
		/**
		 * 分类
		 */
		$catelog = \TMS_APP::M('app\merchant\catelog')->byId($prod->cate_id);
		$cateCascaded = \TMS_APP::M('app\merchant\catelog')->cascaded($prod->cate_id);
		$catelog->properties = $cateCascaded->properties;
		isset($cateCascaded->propValues) && $catelog->propValues = $cateCascaded->propValues;

		$cascaded->catelog = $catelog;
		/**
		 * 分类属性
		 */
		$propValue2 = new \stdClass;
		$propValue = $prod->prop_value ? json_decode($prod->prop_value) : (new \stdClass);
		foreach ($catelog->properties as $prop) {
			if (isset($catelog->propValues) && isset($catelog->propValues->{$prop->id})) {
				$pvs = $catelog->propValues->{$prop->id};
				foreach ($pvs as $pv) {
					if (isset($propValue->{$prop->id}) && $pv->id === $propValue->{$prop->id}) {
						$spv = new \stdClass;
						$spv->id = $pv->id;
						$spv->name = $pv->name;
						$propValue2->{$prop->id} = $spv;
						break;
					}
				}
			} else {
				$propValue2->{$prop->id} = '';
			}
		}
		$cascaded->propValue2 = $propValue2;
		/**
		 * order properties
		 */
		$catelog->orderProperties = $cateCascaded->orderProperties;
		$catelog->feedbackProperties = $cateCascaded->feedbackProperties;

		return $cascaded;
	}
	/**
	 *
	 * @param int $productId
	 */
	public function remove($productId) {
		/**/
		$rst = $this->delete('xxt_merchant_product_sku', "prod_id=$productId");
		$rst = $this->delete('xxt_merchant_product', "id=$productId");

		return $rst;
	}
	/**
	 *
	 * @param int @catelogId
	 */
	public function refer($productId) {
		$rst = $this->update(
			'xxt_merchant_product',
			array('used' => 'Y'),
			"id=$productId"
		);

		return $rst;
	}
	/**
	 *
	 * @param int @catelogId
	 */
	public function disable($productId) {
		$rst = $this->update(
			'xxt_merchant_product',
			array('disabled' => 'Y', 'active' => 'N'),
			"id=$productId"
		);

		return $rst;
	}
}