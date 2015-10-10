<?php
namespace mp\app\merchant;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 通知消息
 */
class tmplmsg extends \mp\app\app_base {
	/**
	 * 打开订购商品管理页面
	 */
	public function index_action() {
		$this->view_action('/mp/app/merchant/shop');
	}
	/**
	 * 建立映射关系ß
	 */
	public function setup_action($catelog, $mappingid = 0) {
		$posted = $this->getPostJson();
		$model = $this->model();
		if ($mappingid == 0) {
			/*建立影射关系*/
			$mappingid = $model->insert(
				'xxt_tmplmsg_mapping',
				array(
					'msgid' => $posted->msgid,
					'mapping' => json_encode($posted->mapping),
				),
				true
			);
			/*记录影射关系*/
			$model->update(
				'xxt_merchant_catelog',
				array(
					$posted->evt . '_tmplmsg' => $mappingid,
				),
				"id='$catelog'"
			);
		} else {
			/*建立影射关系*/
			$mappingid = $model->update(
				'xxt_tmplmsg_mapping',
				array(
					'msgid' => $posted->msgid,
					'mapping' => json_encode($posted->mapping),
				),
				"id=$mappingid"
			);
		}

		return new \ResponseData('ok');
	}
}