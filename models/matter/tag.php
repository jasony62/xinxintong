<?php
namespace matter;
/**
 * 素材分类标签
 */
class tag_model extends \TMS_MODEL {
	/**
	 * 给素材添加标签
	 *
	 * $resType
	 */
	public function add($siteId, $resType, $tagTitles, $subType = 0) {
		is_string($tagTitles) && $tagTitles = explode(',', $tagTitles);

		foreach ($tagTitles as $added) {
		}

		return true;
	}
}