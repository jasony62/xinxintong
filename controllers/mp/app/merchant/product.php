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
		$this->view_action('/mp/app/merchant/shop');
	}
	/**
	 * 单个产品编辑页面
	 */
	public function edit_action() {
		$this->view_action('/mp/app/merchant/product/edit');
	}
	/**
	 * 获得商品
	 */
	public function get_action($id) {
		$model = $this->model('app\merchant\product');
		$prod = $model->byId($id, 'Y');
		return new \ResponseData($prod);
	}
	/**
	 * 获得商品列表
	 */
	public function list_action($shopId, $cateId) {
		$model = $this->model('app\merchant\product');
		$products = $model->byShopId($shopId, $cateId);
		foreach ($products as &$prod) {
			$cascaded = $model->cascaded($prod->id);
			$prod->propValue2 = $cascaded->propValue2;
			$prod->skus = $cascaded->skus;
		}
		return new \ResponseData($products);
	}
	/**
	 * 关联的数据
	 *
	 * $id
	 */
	public function cascaded_action($id) {
		$cascaded = $this->model('app\merchant\product')->cascaded($id);

		return new \ResponseData($cascaded);
	}
	/**
	 *
	 * $cateId
	 */
	public function create_action($cateId) {
		$cate = $this->model('app\merchant\catelog')->byId($cateId);
		if (false === $cate) {
			return new \ResponseError('指定的分类不存在，无法创建产品');
		}

		$creater = \TMS_CLIENT::get_client_uid();

		$product = array(
			'mpid' => $this->mpid,
			'sid' => $cate->sid,
			'cate_id' => $cate->id,
			'create_at' => time(),
			'creater' => $creater,
			'name' => '新产品',
		);

		$product['id'] = $this->model()->insert('xxt_merchant_product', $product, true);

		$cascaded = $this->model('app\merchant\product')->cascaded($product['id']);
		$product['catelog'] = $cascaded->catelog;
		$product['propValue2'] = $cascaded->propValue2;
		$product['skus'] = $cascaded->skus;

		return new \ResponseData($product);
	}
	/**
	 *
	 */
	public function update_action($id) {
		$reviser = \TMS_CLIENT::get_client_uid();

		$nv = $this->getPostJson();

		$nv->reviser = $reviser;
		$nv->modify_at = time();

		$rst = $this->model()->update('xxt_merchant_product', (array) $nv, "id='$id'");

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
	public function propUpdate_action($id) {
		$posted = $this->getPostJson();

		if (empty($posted->name)) {
			return new \ResponseError('属性值为空');
		}

		$prod = $this->model('app\merchant\product')->byId($id);
		/**
		 * 获得属性值ID
		 */
		$q = array(
			'id',
			'xxt_merchant_catelog_property_value',
			"prop_id=$posted->prop_id and name='$posted->name'",
		);
		$vid = $this->model()->query_val_ss($q);
		$vid === false && $vid = $this->propValueCreate($prod->cate_id, $posted->prop_id, $posted->name);
		/**
		 * 更新产品的属性值
		 */
		if ($prod->prop_value) {
			$propValue = json_decode($prod->prop_value);
		} else {
			$propValue = new \stdClass;
		}

		$propValue->{(string) $posted->prop_id} = (string) $vid;

		$reviser = \TMS_CLIENT::get_client_uid();
		$nv = new \stdClass;
		$nv->reviser = $reviser;
		$nv->modify_at = time();
		$nv->prop_value = json_encode($propValue);

		$rst = $this->model()->update('xxt_merchant_product', (array) $nv, "id='$id'");

		return new \ResponseData(array('id' => $vid, 'name' => $posted->name));
	}
	/**
	 *
	 */
	public function remove_action() {
		return new \ResponseData('ok');
	}
	/**
	 * $id product's id
	 */
	public function skuGet_action($id) {
		return new \ResponseData('ok');
	}
	/**
	 * 添加产品的sku
	 *
	 * $id product's id
	 */
	public function skuCreate_action($id) {
		$prod = $this->model('app\merchant\product')->byId($id);

		$creater = \TMS_CLIENT::get_client_uid();

		$sku = array(
			'mpid' => $prod->mpid,
			'sid' => $prod->sid,
			'cate_id' => $prod->cate_id,
			'prod_id' => $prod->id,
			'create_at' => time(),
			'creater' => $creater,
			'sku_value' => '{}',
			'ori_price' => 0,
			'price' => 0,
			'quantity' => 1,
			'product_code' => '',
		);

		$sku['id'] = $this->model()->insert('xxt_merchant_product_sku', $sku, true);

		return new \ResponseData($sku);
	}
	/**
	 * 更改sku基本信息
	 *
	 * $id product sku's id
	 */
	public function skuUpdate_action($id) {
		$reviser = \TMS_CLIENT::get_client_uid();

		$nv = $this->getPostJson();

		$nv->reviser = $reviser;
		$nv->modify_at = time();

		$rst = $this->model()->update('xxt_merchant_product_sku', (array) $nv, "id='$id'");

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function skuRemove_action() {
		return new \ResponseData('ok');
	}
}
