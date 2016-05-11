<?php
namespace app\merchant;
/**
 * 定制页面
 */
class page_model extends \TMS_MODEL {
	/**
	 * @param int $id
	 */
	public function &byId($id, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';

		$q = array(
			$fields,
			'xxt_merchant_page',
			"id=$id",
		);

		$page = $this->query_obj_ss($q);

		if ($page && $cascaded === 'Y') {
			$code = \TMS_APP::M('code\page')->byId($page->code_id);
			$page->html = $code->html;
			$page->css = $code->css;
			$page->js = $code->js;
			$page->ext_js = $code->ext_js;
			$page->ext_css = $code->ext_css;
		}

		return $page;
	}
	/**
	 * @param int $shopId
	 */
	public function &byShopId($shopId) {
		$q = array(
			'*',
			'xxt_merchant_page',
			"sid=$shopId",
		);
		$q2 = array(
			'o' => 'seq',
		);

		$pages = $this->query_objs_ss($q, $q2);

		return $pages;
	}
	/**
	 *
	 * @param string $type
	 * @param int $shopId
	 * @param int $catelogId
	 * @param int $productId
	 * @param array $options
	 */
	public function &byType($type, $shopId, $catelogId = 0, $productId = 0, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';

		$q = array(
			$fields,
			'xxt_merchant_page',
			"sid=$shopId and cate_id=$catelogId and prod_id=$productId and type='$type'",
		);
		$q2 = array(
			'o' => 'seq',
		);

		$pages = $this->query_objs_ss($q, $q2);
		/*如果找不到定义，就从父资源中找定义*/
		if (count($pages) === 0) {
			if ($productId != 0) {
				$pages = $this->byType($type, $shopId, $catelogId, 0, $options);
			} else if ($catelogId != 0) {
				$pages = $this->byType($type, $shopId, 0, 0, $options);
			}
		}
		if (count($pages) > 0 && $cascaded === 'Y') {
			$modelCode = \TMS_APP::M('code\page');
			foreach ($pages as &$page) {
				$code = $modelCode->byId($page->code_id);
				$page->html = $code->html;
				$page->css = $code->css;
				$page->js = $code->js;
				$page->ext_js = $code->ext_js;
				$page->ext_css = $code->ext_css;
			}
		}

		return $pages;
	}
	/**
	 * 创建页面
	 */
	public function add($mpid, $data, $shopId, $catelogId = 0, $productId = 0) {
		$uid = \TMS_CLIENT::get_client_uid();

		$code = \TMS_APP::model('code\page')->create($uid);

		$newPage = array(
			'mpid' => $mpid,
			'sid' => $shopId,
			'cate_id' => $catelogId,
			'prod_id' => $productId,
			'creater' => $uid,
			'create_at' => time(),
			'type' => $data['type'],
			'name' => isset($data['name']) ? $data['name'] : '新页面',
			'title' => isset($data['title']) ? $data['title'] : '新页面',
			'seq' => $data['seq'],
			'code_id' => $code->id,
		);

		$apid = $this->insert('xxt_merchant_page', $newPage, true);

		$newPage['id'] = $apid;

		return (object) $newPage;
	}
	/**
	 *
	 */
	public function remove($pageId) {
		$page = $this->byId($pageId, array('cascaded' => 'N'));
		\TMS_APP::model('code\page')->remove($page->code_id);
		$rst = $this->delete('xxt_merchant_page', "id=$pageId");

		return $rst;
	}
}