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
			$prod->skus = $cascaded->skus;
		}

		return $prod;
	}
	/**
	 *
	 * $shopId
	 * $cateId
	 */
	public function &byShopId($shopId, $cateId) {
		$q = array(
			'*',
			'xxt_merchant_product p',
			"sid=$shopId and cate_id=$cateId",
		);
		$q2 = array('o' => 'create_at desc');

		$products = $this->query_objs_ss($q, $q2);

		return $products;
	}
	/**
	 * 根据属性值获得产品列表
	 */
	public function &byPropValue($cateId, $vids, $cascaded = 'Y') {
		$q = array(
			'*',
			'xxt_merchant_product p',
			"cate_id=$cateId",
		);
		foreach ($vids as $vid) {
			$q[2] .= " and prop_value like '%:\"$vid\"%'";
		}

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
		 * sku
		 */
		$q = array(
			'*',
			'xxt_merchant_product_sku',
			"prod_id=$id",
		);
		$skus = $this->query_objs_ss($q);
		$cascaded->skus = $skus;

		return $cascaded;
	}
}
