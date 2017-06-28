<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动标签
 */
class tag extends base {
	/**
	 *
	 */
	public function create_action($app) {
		/* 登记活动定义 */
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ResponseError('指定的登记活动不存在，请检查参数是否正确');
		}

		$posted = $this->getPostJson();

		$current = time();
		$newTags = [];
		foreach ($posted as $tagLabel) {
			$oNewTag = new \stdClass;
			$oNewTag->id = $current++;
			$oNewTag->label = $tagLabel;
			$newTags[] = $oNewTag;
		}

		return new \ResponseData($newTags);
	}
}