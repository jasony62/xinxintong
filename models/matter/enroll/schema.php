<?php
namespace matter\enroll;
/**
 *
 */
class schema_model extends \TMS_MODEL {
	/**
	 * 去除题目中的通讯录信息
	 */
	public function wipeMschema(&$oSchema, $oMschema) {
		if ($oSchema->type === 'member' && $oSchema->schema_id === $oMschema->id) {
			/* 更新题目 */
			$oSchema->type = 'shorttext';
			$oSchema->id = str_replace('member.', '', $oSchema->id);
			if (in_array($oSchema->id, ['name', 'mobile', 'email'])) {
				$oSchema->format = $oSchema->id;
			} else {
				$oSchema->format = '';
			}
			unset($oSchema->schema_id);

			return true;
		}

		return false;
	}
	/**
	 * 去除和其他活动的题目的关联
	 */
	public function wipeAssoc(&$oSchema, $aAssocAppIds) {
		if (isset($oSchema->fromApp) && in_array($oSchema->fromApp, $aAssocAppIds)) {
			unset($oSchema->fromApp);
			unset($oSchema->requieCheck);

			return true;
		}

		return false;
	}
	/**
	 * 去除掉无效的内容
	 *
	 * 1、无效的字段
	 * 2、无效的设置，例如隐藏条件
	 */
	public function purify($aAppSchemas) {
		$purified = [];
		foreach ($aAppSchemas as $oSchema) {
			unset($oSchema->summary);
			unset($oSchema->_open);
			unset($oSchema->_ver);
			/* 关联到其他应用时才需要检查 */
			if (empty($oSchema->fromApp)) {
				unset($oSchema->requireCheck);
			}
			$purified[] = $oSchema;
		}

		return $purified;
	}
}