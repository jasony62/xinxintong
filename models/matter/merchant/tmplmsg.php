<?php
namespace matter\merchant;
/**
 * 模版消息通知
 */
class tmplmsg_model extends \TMS_MODEL {
	/**
	 * 获得指定分类的模版消息
	 */
	public function &byCatelog($catelogId, $eventName = null) {
		//
		$mappings = new \stdClass;
		//
		$fields = $eventName === null ? array('submit_order', 'pay_order', 'feedback_order') : array($eventName);
		$q = array(
			implode('_tmplmsg,', $fields) . '_tmplmsg',
			'xxt_merchant_catelog',
			"id=$catelogId",
		);
		$catelog = $this->query_obj_ss($q);
		if ($catelog) {
			foreach ($fields as $eventName) {
				$modelTmpl = $this->model('matter\tmplmsg\config');
				$mapping = $modelTmpl->byId($catelog->{$eventName . '_tmplmsg'});
				$mappings->{$eventName} = $mapping;
			}
		}

		return $mappings;
	}
}