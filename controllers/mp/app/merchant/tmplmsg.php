<?php
namespace mp\app\merchant;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 通知消息
 */
class tmplmsg extends \mp\app\app_base {
	/**
	 * 建立映射关系
	 */
	public function setup_action($catelog, $mappingid = 0) {
		$posted = $this->getPostJson();
		$model = $this->model();
		foreach ($posted->mapping as &$prop) {
			if ($prop->src === 'text') {
				$prop->id = urlencode($prop->id);
			}
		}
		if ($mappingid == 0) {
			/*建立影射关系*/
			$mappingid = $model->insert(
				'xxt_tmplmsg_mapping',
				array(
					'msgid' => $posted->msgid,
					'mapping' => urldecode(json_encode($posted->mapping)),
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
					'mapping' => urldecode(json_encode($posted->mapping)),
				),
				"id=$mappingid"
			);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 清除映射关系
	 */
	public function clean_action($catelog, $mappingid, $event) {
		$this->model()->delete('xxt_tmplmsg_mapping', "id=$mappingid");

		$this->model()->update(
			'xxt_merchant_catelog',
			array($event . '_tmplmsg' => 0),
			"id=$catelog"
		);

		return new \ResponseData('ok');
	}
}