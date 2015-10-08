<?php
namespace app\merchant;
/**
 *
 */
class page_model extends \TMS_MODEL {
	/**
	 * $id
	 */
	public function &byId($id) {
		$q = array(
			'*',
			'xxt_merchant_page',
			"id=$id",
		);

		$page = $this->query_obj_ss($q);

		if ($page) {
			$code = \TMS_APP::M('code/page')->byId($page->code_id);
			$page->html = $code->html;
			$page->css = $code->css;
			$page->js = $code->js;
			$page->ext_js = $code->ext_js;
			$page->ext_css = $code->ext_css;
		}

		return $page;
	}
	/**
	 *
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
	 */
	public function &byType($shopId, $type) {
		$q = array(
			'*',
			'xxt_merchant_page',
			"sid=$shopId and type='$type'",
		);
		$q2 = array(
			'o' => 'seq',
		);

		$pages = $this->query_objs_ss($q, $q2);
		if (count($pages)) {
			$modelCode = \TMS_APP::M('code/page');
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
	public function add($mpid, $shopId, $data) {
		$uid = \TMS_CLIENT::get_client_uid();

		$code = \TMS_APP::model('code/page')->create($uid);

		$newPage = array(
			'mpid' => $mpid,
			'sid' => $shopId,
			'creater' => $uid,
			'create_at' => time(),
			'type' => $data['type'],
			'title' => isset($data['title']) ? $data['title'] : '新页面',
			'code_id' => $code->id,
		);

		$apid = $this->insert('xxt_merchant_page', $newPage, true);

		$newPage['id'] = $apid;

		return (object) $newPage;
	}
}