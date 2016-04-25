<?php
namespace matter\merchant;
/**
 *
 */
class property_model extends \TMS_MODEL {
	/**
	 * $id
	 */
	public function &byId($id) {
		$q = array(
			'*',
			'xxt_merchant_catelog_property p',
			"id=$id",
		);

		$prop = $this->query_obj_ss($q);

		return $prop;
	}
	/**
	 *
	 * @param int $id
	 */
	public function remove($id) {
		$rst = $this->delete('xxt_merchant_catelog_property_value', "prop_id=$id");
		$rst = $this->delete('xxt_merchant_catelog_property', "id=$id");

		return $rst;
	}
	/**
	 *
	 * @param int $id
	 */
	public function refer($id) {
		$rst = $this->update(
			'xxt_merchant_catelog_property',
			array('used' => 'Y'),
			"id=$id"
		);

		return $rst;
	}
	/**
	 *
	 * @param int $id
	 */
	public function referByCatelog($catelogId) {
		$rst = $this->update(
			'xxt_merchant_catelog_property',
			array('used' => 'Y'),
			"cate_id=$catelogId"
		);

		return $rst;
	}
	/**
	 *
	 * @param int $id
	 */
	public function disable($id) {
		$rst = $this->update(
			'xxt_merchant_catelog_property',
			array('disabled' => 'Y'),
			"id=$id"
		);

		return $rst;
	}
	/**
	 * $id
	 */
	public function &orderById($id) {
		$q = array(
			'*',
			'xxt_merchant_order_property',
			"id=$id",
		);

		$prop = $this->query_obj_ss($q);

		return $prop;
	}
	/**
	 *
	 * @param int $id
	 */
	public function orderRemove($id) {
		$rst = $this->delete('xxt_merchant_order_property', "id=$id");

		return $rst;
	}
	/**
	 *
	 * @param int $id
	 */
	public function referOrderByCatelog($catelogId) {
		$rst = $this->update(
			'xxt_merchant_order_property',
			array('used' => 'Y'),
			"cate_id=$catelogId"
		);

		return $rst;
	}
	/**
	 *
	 * @param int $id
	 */
	public function orderRefer($id) {
		$rst = $this->update(
			'xxt_merchant_order_property',
			array('used' => 'Y'),
			"id=$id"
		);

		return $rst;
	}
	/**
	 *
	 * @param int $id
	 */
	public function orderDisable($id) {
		$rst = $this->update(
			'xxt_merchant_order_property',
			array('disabled' => 'Y'),
			"id=$id"
		);

		return $rst;
	}
	/**
	 * $id
	 */
	public function &feedbackById($id) {
		$q = array(
			'*',
			'xxt_merchant_order_feedback_property',
			"id=$id",
		);

		$prop = $this->query_obj_ss($q);

		return $prop;
	}
	/**
	 *
	 * @param int $id
	 */
	public function feedbackRemove($id) {
		$rst = $this->delete('xxt_merchant_order_feedback_property', "id=$id");

		return $rst;
	}
	/**
	 *
	 * @param int $id
	 */
	public function feedbackRefer($id) {
		$rst = $this->update(
			'xxt_merchant_order_feedback_property',
			array('used' => 'Y'),
			"id=$id"
		);

		return $rst;
	}
	/**
	 *
	 * @param int $id
	 */
	public function referFeedbackByCatelog($catelogId) {
		$rst = $this->update(
			'xxt_merchant_order_feedback_property',
			array('used' => 'Y'),
			"cate_id=$catelogId"
		);

		return $rst;
	}
	/**
	 *
	 * @param int $id
	 */
	public function feedbackDisable($id) {
		$rst = $this->update(
			'xxt_merchant_order_feedback_property',
			array('disabled' => 'Y'),
			"id=$id"
		);

		return $rst;
	}
}