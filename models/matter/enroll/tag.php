<?php
namespace matter\enroll;
/**
 *
 */
class tag_model extends \TMS_MODEL {
	/**
	 * 获得登记活动的填写项标签
	 *
	 * @param object $oApp
	 */
	public function byApp($oApp) {
		$tags = [];
		for ($i = 1; $i <= 10; $i++) {
			$oNewTag = new \stdClass;
			$oNewTag->id = (string) $i;
			$oNewTag->label = 'tag' . $i;
			$tags[] = $oNewTag;
		}
		return $tags;
	}
}