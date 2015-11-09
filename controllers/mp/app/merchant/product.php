<?php
namespace mp\app\merchant;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 商品
 */
class product extends \mp\app\app_base {
	/**
	 * 打开订购商品管理页面
	 */
	public function index_action() {
		$this->view_action('/mp/app/merchant/product/base');
	}
	/*
	 *
	 */
	public function sku_action() {
		$this->view_action('/mp/app/merchant/product/base');
	}
	/*
	 *
	 */
	public function order_action() {
		$this->view_action('/mp/app/merchant/product/base');
	}
	/**
	 * 获得商品
	 */
	public function get_action($product) {
		$model = $this->model('app\merchant\product');
		$options = array(
			'cascaded' => 'Y',
		);
		$prod = $model->byId($product, $options);
		return new \ResponseData($prod);
	}
	/**
	 * 获得商品列表
	 *
	 * @param string $shop
	 * @param string $catelog
	 */
	public function list_action($shop, $catelog) {
		$model = $this->model('app\merchant\product');
		$state = array('disabled' => 'N');
		$products = $model->byShopId($shop, $catelog, $state);

		return new \ResponseData($products);
	}
	/**
	 * 关联的数据
	 *
	 * @param int $product
	 */
	public function cascaded_action($product) {
		$cascaded = $this->model('app\merchant\product')->cascaded($product);

		return new \ResponseData($cascaded);
	}
	/**
	 * 在指定分类下创建商品
	 *
	 * @param int $catelog
	 */
	public function create_action($catelog) {
		$modelCate = $this->model('app\merchant\catelog');
		$catelog = $modelCate->byId($catelog);
		if (false === $catelog) {
			return new \ResponseError('指定的分类不存在，无法创建产品');
		}
		/*更新分类和商品属性的状态*/
		$modelCate->refer($catelog->id);
		$this->model('app\merchant\property')->referByCatelog($catelog->id);
		/*创建商品*/
		$creater = \TMS_CLIENT::get_client_uid();
		$product = array(
			'mpid' => $this->mpid,
			'sid' => $catelog->sid,
			'cate_id' => $catelog->id,
			'create_at' => time(),
			'creater' => $creater,
			'name' => '新商品',
		);
		$product['id'] = $this->model()->insert('xxt_merchant_product', $product, true);

		//$cascaded = $this->model('app\merchant\product')->cascaded($product['id']);
		//$product['catelog'] = $cascaded->catelog;
		//$product['propValue2'] = $cascaded->propValue2;

		return new \ResponseData($product);
	}
	/**
	 *
	 */
	public function update_action($product) {
		$nv = $this->getPostJson();
		$rst = $this->_update($product, $nv);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * @param int $catelog
	 */
	public function activate_action($product) {
		$modelProp = $this->model('app\merchant\property');
		$modelProp->referByCatelog($product);

		$updated = new \stdClass;
		$updated->active = 'Y';
		$rst = $this->_update($product, $updated);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * @param int $catelog
	 */
	public function deactivate_action($product) {
		$updated = new \stdClass;
		$updated->active = 'N';
		$rst = $this->_update($product, $updated);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	private function propValueCreate($cateId, $propId, $data) {
		$cate = $this->model('app\merchant\catelog')->byId($cateId);
		if (false === $cate) {
			return new \ResponseError('指定的分类不存在，无法创建分类属性值');
		}

		$creater = \TMS_CLIENT::get_client_uid();

		$propValue = array(
			'mpid' => $cate->mpid,
			'sid' => $cate->sid,
			'cate_id' => $cate->id,
			'prop_id' => $propId,
			'create_at' => time(),
			'creater' => $creater,
			'name' => $data,
		);

		$id = $this->model()->insert('xxt_merchant_catelog_property_value', $propValue, true);

		return $id;
	}
	/**
	 * 更新属性值
	 *
	 * $id product's id
	 */
	public function propUpdate_action($product) {
		$posted = $this->getPostJson();
		if (empty($posted->name)) {
			return new \ResponseError('属性值为空');
		}

		$product = $this->model('app\merchant\product')->byId($product);
		/**
		 * 获得属性值ID
		 */
		$q = array(
			'id',
			'xxt_merchant_catelog_property_value',
			"prop_id=$posted->prop_id and name='$posted->name'",
		);
		$vid = $this->model()->query_val_ss($q);
		$vid === false && $vid = $this->propValueCreate($product->cate_id, $posted->prop_id, $posted->name);
		/**
		 * 更新产品的属性值
		 */
		if ($product->prop_value) {
			$propValue = json_decode($product->prop_value);
		} else {
			$propValue = new \stdClass;
		}

		$propValue->{(string) $posted->prop_id} = (string) $vid;

		$reviser = \TMS_CLIENT::get_client_uid();
		$nv = new \stdClass;
		$nv->reviser = $reviser;
		$nv->modify_at = time();
		$nv->prop_value = json_encode($propValue);

		$rst = $this->model()->update(
			'xxt_merchant_product',
			(array) $nv,
			"id='$product->id'"
		);

		return new \ResponseData(array('id' => $vid, 'name' => $posted->name));
	}
	/**
	 *
	 * @param int $product
	 */
	public function remove_action($product) {
		$modelProd = $this->model('app\merchant\product');
		$product = $modelProd->byId($product);
		if ($product->used === 'N') {
			$rst = $modelProd->remove($product->id);
		} else {
			$rst = $modelProd->disable($product->id);
		}

		return new \ResponseData($rst);
	}
	/**
	 * @param int $product
	 */
	public function skuList_action($product) {
		$modelSku = $this->model('app\merchant\sku');
		$options = array(
			'disabled' => 'N',
		);
		$skus = $modelSku->byProduct($product, $options);

		return new \ResponseData($skus);
	}
	/**
	 * 添加产品的sku
	 *
	 * @param int $product product's id
	 * @param int $cateSku catelog sku's id
	 */
	public function skuCreate_action($product, $cateSku) {
		$prod = $this->model('app\merchant\product')->byId($product);
		$cateSku = $this->model('app\merchant\catelog')->skuById($cateSku);

		$creater = \TMS_CLIENT::get_client_uid();

		$sku = array(
			'mpid' => $prod->mpid,
			'sid' => $prod->sid,
			'cate_id' => $prod->cate_id,
			'cate_sku_id' => $cateSku->id,
			'prod_id' => $prod->id,
			'create_at' => time(),
			'creater' => $creater,
			'sku_value' => '{}',
			'ori_price' => 0,
			'price' => 0,
			'quantity' => 1,
			'has_validity' => $cateSku->has_validity,
			'product_code' => '',
		);
		$skuId = $this->model()->insert('xxt_merchant_product_sku', $sku, true);
		$sku = $this->model('app\merchant\sku')->byId($skuId);

		/*更新catelog sku状态*/
		$this->model('app\merchant\catelog')->useSku($cateSku->id);

		return new \ResponseData($sku);
	}
	/**
	 * 更改sku基本信息
	 *
	 * @param int $sku product sku's id
	 */
	public function skuUpdate_action($sku) {
		$nv = $this->getPostJson();
		$rst = $this->_skuUpdate($sku, $nv);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * @param int $catelog
	 */
	public function skuActivate_action($sku) {
		$updated = new \stdClass;
		$updated->active = 'Y';
		$rst = $this->_skuUpdate($sku, $updated);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * @param int $catelog
	 */
	public function skuDeactivate_action($sku) {
		$updated = new \stdClass;
		$updated->active = 'N';
		$rst = $this->_skuUpdate($sku, $updated);

		return new \ResponseData($rst);
	}
	/**
	 * @param int $sku
	 */
	public function skuRemove_action($sku) {
		$modelSku = $this->model('app\merchant\sku');

		$rst = $modelSku->remove($sku);

		return new \ResponseData($rst);
	}
	/**
	 * @param int $product
	 * @param object $data
	 */
	private function _update($product, $data) {
		$reviser = \TMS_CLIENT::get_client_uid();

		$data->reviser = $reviser;
		$data->modify_at = time();

		$rst = $this->model()->update(
			'xxt_merchant_product',
			(array) $data,
			"id='$product'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 更改sku基本信息
	 *
	 * @param int $sku product sku's id
	 * @param object $data
	 */
	private function _skuUpdate($sku, $data) {
		$reviser = \TMS_CLIENT::get_client_uid();

		$data->reviser = $reviser;
		$data->modify_at = time();

		$rst = $this->model()->update(
			'xxt_merchant_product_sku',
			(array) $data,
			"id=$sku"
		);

		return new \ResponseData($rst);
	}
}