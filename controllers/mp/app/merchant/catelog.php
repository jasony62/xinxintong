<?php
namespace mp\app\merchant;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 商品分类
 */
class catelog extends \mp\app\app_base {
	/**
	 * 打开商品分类管理页面
	 */
	public function index_action() {
		$this->view_action('/mp/app/merchant/catelog/base');
	}
	/**
	 *
	 */
	public function sku_action() {
		$this->view_action('/mp/app/merchant/catelog/base');
	}
	/**
	 *
	 */
	public function page_action() {
		$this->view_action('/mp/app/merchant/catelog/base');
	}
	/**
	 *
	 */
	public function product_action() {
		$this->view_action('/mp/app/merchant/catelog/base');
	}
	/**
	 *
	 */
	public function tmplmsg_action() {
		$this->view_action('/mp/app/merchant/catelog/base');
	}
	/**
	 *
	 */
	public function order_action() {
		$this->view_action('/mp/app/merchant/catelog/base');
	}
	/**
	 *
	 * @param int $catelog
	 */
	public function get_action($catelog, $cascaded = 'Y') {
		$options = array(
			'fields' => '*',
			'cascaded' => $cascaded,
		);
		if ($catelog = $this->model('app\merchant\catelog')->byId($catelog, $options)) {
			$catelog->shop = $this->model('app\merchant\shop')->byId($catelog->sid);
		}

		return new \ResponseData($catelog);
	}
	/**
	 * @param string $shop
	 */
	public function list_action($shop) {
		$state = array('disabled' => 'N');
		$catelogs = $this->model('app\merchant\catelog')->byShopId($shop, $state);

		return new \ResponseData($catelogs);
	}
	/**
	 * 关联的数据
	 *
	 * $id
	 */
	public function cascaded_action($id) {
		$cascaded = $this->model('app\merchant\catelog')->cascaded($id);

		return new \ResponseData($cascaded);
	}
	/**
	 * 在指定商铺下创建分类
	 *
	 * @param int $shop
	 */
	public function create_action($shop) {
		$creater = \TMS_CLIENT::get_client_uid();
		$current = time();
		/*商品分类*/
		$cate = array(
			'mpid' => $this->mpid,
			'sid' => $shop,
			'create_at' => time(),
			'creater' => $creater,
			'name' => '新分类',
		);
		$cate['id'] = $this->model()->insert('xxt_merchant_catelog', $cate, true);
		/*每个分类至少有一个缺省的sku*/
		$sku = new \stdClass;
		$sku->name = '新库存定义';
		$sku->autogen_rule = '{}';
		$sku = $this->model('app\merchant\catelog')->defineSku($this->mpid, $shop, $cate['id'], $sku);

		return new \ResponseData($cate);
	}
	/**
	 * 更新分类的基础信息
	 *
	 * @param int $catelog
	 */
	public function update_action($catelog) {
		$nv = $this->getPostJson();
		$rst = $this->_update($catelog, $nv);

		if (isset($nv->has_validity) && $nv->has_validity === 'Y') {
			$this->model()->update(
				'xxt_merchant_catelog_sku',
				array('has_validity' => 'Y'),
				"cate_id=$catelog"
			);
			$this->model()->update(
				'xxt_merchant_product_sku',
				array('has_validity' => 'Y'),
				"cate_id=$catelog"
			);
		}

		return new \ResponseData($rst);
	}
	/**
	 *
	 * @param int $catelog
	 */
	public function activate_action($catelog) {
		$modelProp = $this->model('app\merchant\property');
		$modelProp->referOrderByCatelog($catelog);
		$modelProp->referFeedbackByCatelog($catelog);

		$updated = new \stdClass;
		$updated->active = 'Y';
		$rst = $this->_update($catelog, $updated);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * @param int $catelog
	 */
	public function deactivate_action($catelog) {
		$updated = new \stdClass;
		$updated->active = 'N';
		$rst = $this->_update($catelog, $updated);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * @param int $product
	 */
	public function remove_action($catelog) {
		$modelCate = $this->model('app\merchant\catelog');
		$catelog = $modelCate->byId($catelog);
		if ($catelog->used === 'N') {
			$rst = $modelCate->remove($catelog->id);
		} else {
			$rst = $modelCate->disable($catelog->id);
		}

		return new \ResponseData($rst);
	}
	/**
	 * 添加属性
	 *
	 * $id catelog's id
	 */
	public function propCreate_action($id) {
		$cate = $this->model('app\merchant\catelog')->byId($id);
		if (false === $cate) {
			return new \ResponseError('指定的分类不存在，无法添加属性');
		}

		$creater = \TMS_CLIENT::get_client_uid();

		$prop = array(
			'mpid' => $this->mpid,
			'sid' => $cate->sid,
			'cate_id' => $id,
			'create_at' => time(),
			'creater' => $creater,
			'name' => '新属性',
		);

		$prop['id'] = $this->model()->insert('xxt_merchant_catelog_property', $prop, true);

		return new \ResponseData($prop);
	}
	/**
	 *
	 */
	public function propUpdate_action() {
		$updated = $this->getPostJson();

		$data = array();
		$data['name'] = $updated->name;

		$rst = $this->model()->update('xxt_merchant_catelog_property', $data, "id=$updated->id");

		return new \ResponseData($data);
	}
	/**
	 *
	 * @param int $property
	 */
	public function propRemove_action($property) {
		$modelProp = $this->model('app\merchant\property');
		$property = $modelProp->byId($property);
		if ($property->used === 'N') {
			$rst = $modelProp->remove($property->id);
		} else {
			$rst = $modelProp->disable($property->id);
		}

		return new \ResponseData($rst);
	}
	/**
	 * 添加属性
	 *
	 * $id catelog's id
	 */
	public function orderPropCreate_action($id) {
		$cate = $this->model('app\merchant\catelog')->byId($id);
		if (false === $cate) {
			return new \ResponseError('指定的分类不存在，无法添加属性');
		}

		$creater = \TMS_CLIENT::get_client_uid();

		$prop = array(
			'mpid' => $this->mpid,
			'sid' => $cate->sid,
			'cate_id' => $id,
			'create_at' => time(),
			'creater' => $creater,
			'name' => '新属性',
		);

		$prop['id'] = $this->model()->insert('xxt_merchant_order_property', $prop, true);

		return new \ResponseData($prop);
	}
	/**
	 *
	 */
	public function orderPropUpdate_action() {
		$updated = $this->getPostJson();

		$data = array();
		$data['name'] = $updated->name;

		$rst = $this->model()->update('xxt_merchant_order_property', $data, "id=$updated->id");

		return new \ResponseData($data);
	}
	/**
	 *
	 */
	public function orderPropRemove_action($id) {
		$modelProp = $this->model('app\merchant\property');
		$property = $modelProp->orderById($id);
		if ($property->used === 'N') {
			$rst = $modelProp->orderRemove($property->id);
		} else {
			$rst = $modelProp->orderDisable($property->id);
		}

		return new \ResponseData($rst);
	}
	/**
	 * 添加属性
	 *
	 * $id catelog's id
	 */
	public function feedbackPropCreate_action($id) {
		$cate = $this->model('app\merchant\catelog')->byId($id);
		if (false === $cate) {
			return new \ResponseError('指定的分类不存在，无法添加属性');
		}

		$creater = \TMS_CLIENT::get_client_uid();

		$prop = array(
			'mpid' => $this->mpid,
			'sid' => $cate->sid,
			'cate_id' => $id,
			'create_at' => time(),
			'creater' => $creater,
			'name' => '新属性',
		);

		$prop['id'] = $this->model()->insert('xxt_merchant_order_feedback_property', $prop, true);

		return new \ResponseData($prop);
	}
	/**
	 *
	 */
	public function feedbackPropUpdate_action() {
		$updated = $this->getPostJson();

		$data = array();
		$data['name'] = $updated->name;

		$rst = $this->model()->update('xxt_merchant_order_feedback_property', $data, "id=$updated->id");

		return new \ResponseData($data);
	}
	/**
	 *
	 */
	public function feedbackPropRemove_action($id) {
		$modelProp = $this->model('app\merchant\property');
		$property = $modelProp->feedbackById($id);
		if ($property->used === 'N') {
			$rst = $modelProp->feedbackRemove($property->id);
		} else {
			$rst = $modelProp->feedbackDisable($property->id);
		}

		return new \ResponseData($rst);
	}
	/**
	 * 获得指定分类下sku定义
	 *
	 * @param int $shop
	 * @param int $catelog
	 *
	 * @return sku列表
	 */
	public function skuList_action($shop, $catelog) {
		$modelCate = $this->model('app\merchant\catelog');

		$skus = $modelCate->skus($catelog);
		foreach ($skus as &$sku) {
			$sku->autogen_rule = json_decode($sku->autogen_rule);
		}

		return new \ResponseData($skus);
	}
	/**
	 * @param int $shop
	 * @param int $catelog
	 */
	public function skuCreate_action($shop, $catelog) {
		$data = new \stdClass;
		$data->name = '新库存定义';
		$data->has_validity = 'N';
		$data->autogen_rule = '{}';

		$sku = $this->model('app\merchant\catelog')->defineSku($this->mpid, $shop, $catelog, $data);

		return new \ResponseData($sku);
	}
	/**
	 *
	 */
	public function skuUpdate_action($sku) {
		$posted = $this->getPostJson();

		$data = $posted;
		if (isset($data->autogen_rule)) {
			$data->autogen_rule = json_encode($data->autogen_rule);
		}

		$data->modify_at = time();
		$data->reviser = \TMS_CLIENT::get_client_uid();

		$rst = $this->model()->update('xxt_merchant_catelog_sku', (array) $data, "id=$sku");
		if ($rst && isset($data->has_validity)) {
			$this->model()->update(
				'xxt_merchant_product_sku',
				array('has_validity' => $data->has_validity),
				"cate_sku_id=$sku"
			);
		}

		return new \ResponseData($rst);
	}
	/**
	 * @param int $sku
	 */
	public function skuRemove_action($sku) {
		$modelCate = $this->model('app\merchant\catelog');

		$rst = $modelCate->removeSku($sku);

		return new \ResponseData($rst);
	}
	/**
	 * @param int $catelog
	 * @param string $type
	 */
	public function pageCreate_action($catelog, $type) {
		return new \ResponseData($page);
	}
	/**
	 * 更新分类的基础信息
	 *
	 * @param int $catelog
	 * @param object $data
	 */
	private function _update($catelogId, $data) {
		$reviser = \TMS_CLIENT::get_client_uid();

		$data->reviser = $reviser;
		$data->modify_at = time();

		$rst = $this->model()->update(
			'xxt_merchant_catelog',
			(array) $data,
			"id=$catelogId"
		);

		return new \ResponseData($rst);
	}
}