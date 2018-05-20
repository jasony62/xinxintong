<?php
namespace matter\enroll;

require_once dirname(__FILE__) . '/entity.php';

/**
 * 登记活动记录
 */
abstract class record_base extends entity_model {
	/**
	 * 根据题目获得在记录中的值
	 */
	public function getValueBySchema($oSchema, $oData) {
		$schemaId = $oSchema->id;
		if (strpos($schemaId, 'member.') === 0) {
			$schemaId = explode('.', $schemaId);
			if (count($schemaId) === 2) {
				$schemaId = $schemaId[1];
				if (isset($oData->member->{$schemaId})) {
					$value = $oData->member->{$schemaId};
				}
			}
		} else {
			$value = empty($oData->{$schemaId}) ? '' : $oData->{$schemaId};
		}

		return isset($value) ? $value : '';
	}
}