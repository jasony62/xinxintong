<?php
namespace app\merchant;
/**
 * 商品
 */
class product_model extends \TMS_MODEL {
	/**
	 * @param int $id
	 */
	public function &byId($id, $options = array()) {
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'N';
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$catelog = isset($options['catelog']) ? $options['catelog'] : false;

		$q = array(
			$fields,
			'xxt_merchant_product p',
			"id=$id",
		);

		if ($prod = $this->query_obj_ss($q)) {
			if ($cascaded === 'Y') {
				if ($catelog === false) {
					$cateFields = 'id,sid,name,submit_order_tmplmsg,pay_order_tmplmsg,feedback_order_tmplmsg';
					$catelog = \TMS_APP::M('app\merchant\catelog')->byId($prod->cate_id, array('fields' => $cateFields, 'cascaded' => 'Y'));
				}
				$prod->catelog = $catelog;
			}
			if ($catelog) {
				$prod->propValue = $this->_fillPropValue($prod->prop_value, $catelog);
			}
		}

		return $prod;
	}
	/**
	 *
	 * @param string oriPropValue
	 * @param object $catelog
	 */
	private function &_fillPropValue($oriPropValue, $catelog) {
		$fullPropValue = new \stdClass;
		$oriPropValue = $oriPropValue ? json_decode($oriPropValue) : (new \stdClass);
		$cateProperties = $catelog->properties;
		$catePropValues = $catelog->propValues;
		foreach ($cateProperties as $prop) {
			if (isset($catePropValues) && isset($catePropValues->{$prop->id})) {
				$pvs = $catePropValues->{$prop->id};
				foreach ($pvs as $pv) {
					if (isset($oriPropValue->{$prop->id}) && $pv->id === $oriPropValue->{$prop->id}) {
						$spv = new \stdClass;
						$spv->id = $pv->id;
						$spv->name = $pv->name;
						$fullPropValue->{$prop->id} = $spv;
						break;
					}
				}
			} else {
				$propValue2->{$prop->id} = '';
			}
		}

		return $fullPropValue;
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
	public function &byPropValue($cateId, $vids, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';
		$state = isset($options['state']) ? $options['state'] : array();

		$q = array(
			$fields,
			'xxt_merchant_product',
			"cate_id=$cateId",
		);
		foreach ($vids as $vid) {
			$q[2] .= " and prop_value like '%:\"$vid\"%'";
		}
		isset($state['disabled']) && $q[2] .= " and disabled='" . $state['disabled'] . "'";
		isset($state['active']) && $q[2] .= " and active='" . $state['active'] . "'";

		$products = $this->query_objs_ss($q);

		if (!empty($products) && $cascaded === 'Y') {
			$cateFields = 'id,sid,name';
			$catelog = \TMS_APP::M('app\merchant\catelog')->byId($cateId, array('fields' => $cateFields, 'cascaded' => 'Y'));
			foreach ($products as &$prod) {
				$prod->catelog = $catelog;
				$prod->propValue = $this->_fillPropValue($prod->prop_value, $catelog);
			}
		}

		return $products;
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