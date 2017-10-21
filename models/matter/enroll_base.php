<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 * 登记类活动基类
 */
abstract class enroll_base extends app_base {
	/**
	 * 根据用户指定的规则设置
	 */
	protected function setEntryRuleByProto($oSite, &$oEntryRule, $oProtoEntryRule) {
		$oEntryRule->scope = $oProtoEntryRule->scope;
		switch ($oEntryRule->scope) {
		case 'group':
			if (!empty($oProtoEntryRule->group->id) && !empty($oProtoEntryRule->group->round->id)) {
				$oEntryRule->group = (object) ['id' => $oProtoEntryRule->group->id];
				$oEntryRule->group->round = (object) ['id' => $oProtoEntryRule->group->round->id];
			}
			break;
		case 'member':
			if (isset($oProtoEntryRule->mschemas)) {
				$oEntryRule->member = new \stdClass;
				foreach ($oProtoEntryRule->mschemas as $oMschema) {
					$oRule = new \stdClass;
					$oRule->entry = isset($oEntryRule->otherwise->entry) ? $oEntryRule->otherwise->entry : '';
					$oEntryRule->member->{$oMschema->id} = $oRule;
				}
				$oEntryRule->other = new \stdClass;
				$oEntryRule->other->entry = '$memberschema';
			}
			break;
		case 'sns':
			$oRule = new \stdClass;
			$oRule->entry = isset($oEntryRule->otherwise->entry) ? $oEntryRule->otherwise->entry : '';
			$oSns = new \stdClass;
			if (isset($oProtoEntryRule->sns)) {
				foreach ($oProtoEntryRule->sns as $snsName => $bValid) {
					if ($bValid) {
						$oSns->{$snsName} = $oRule;
					}
				}
			} else {
				$modelWx = $this->model('sns\wx');
				$wxOptions = ['fields' => 'joined'];
				if (($wx = $modelWx->bySite($oSite->id, $wxOptions)) && $wx->joined === 'Y') {
					$oSns->wx = $oRule;
				} else if (($wx = $modelWx->bySite('platform', $wxOptions)) && $wx->joined === 'Y') {
					$oSns->wx = $oRule;
				}
				$yxOptions = ['fields' => 'joined'];
				if ($yx = $this->model('sns\yx')->bySite($oSite->id, $yxOptions)) {
					if ($yx->joined === 'Y') {
						$oSns->yx = $oRule;
					}
				}
				if ($qy = $this->model('sns\qy')->bySite($oSite->id, ['fields' => 'joined'])) {
					if ($qy->joined === 'Y') {
						$oSns->qy = $oRule;
					}
				}
			}
			$oEntryRule->sns = $oSns;
			$oEntryRule->other = new \stdClass;
			$oEntryRule->other->entry = '$mpfollow';
			break;
		}

		return $oEntryRule;
	}
	/**
	 * 根据项目指定的规则设置
	 */
	protected function setEntryRuleByMission(&$oEntryRule, $oMisEntryRule) {
		if (isset($oMisEntryRule->scope) && $oMisEntryRule->scope !== 'none') {
			$oEntryRule->scope = $oMisEntryRule->scope;
			switch ($oEntryRule->scope) {
			case 'member':
				if (isset($oMisEntryRule->member)) {
					$oEntryRule->member = $oMisEntryRule->member;
					foreach ($oEntryRule->member as &$oRule) {
						$oRule->entry = isset($oEntryRule->otherwise->entry) ? $oEntryRule->otherwise->entry : '';
					}
					$oEntryRule->other = new \stdClass;
					$oEntryRule->other->entry = '$memberschema';
				}
				break;
			case 'sns':
				$oEntryRule->sns = new \stdClass;
				if (isset($oMisEntryRule->sns)) {
					foreach ($oMisEntryRule->sns as $snsName => $oRule) {
						if (isset($oRule->entry) && $oRule->entry === 'Y') {
							$oEntryRule->sns->{$snsName} = new \stdClass;
							$oEntryRule->sns->{$snsName}->entry = isset($oEntryRule->otherwise->entry) ? $oEntryRule->otherwise->entry : '';
						}
					}
					$oEntryRule->other = new \stdClass;
					$oEntryRule->other->entry = '$mpfollow';
				}
				break;
			}
		}

		return $oEntryRule;
	}
	/**
	 * 根据关联的分组活动设置题目
	 * 1.添加分组的轮次
	 * 2.关联姓名
	 */
	protected function setSchemaByGroupApp($groupAppId, &$oTemplateConfig) {
		$oGroupApp = $this->model('matter\group')->byId($groupAppId, ['cascaded' => 'Y']);
		if (false === $oGroupApp) {
			return $oTemplateConfig;
		}
		/* 关联姓名字段 */
		if (!empty($oTemplateConfig->schema)) {
			foreach ($oGroupApp->dataSchemas as $oGrpSchema) {
				if (($oGrpSchema->id === 'name' && $oGrpSchema->type === 'shorttext') || ($oGrpSchema->id === 'member.name' && $oGrpSchema->type === 'member')) {
					$oGrpNameSchema = $oGrpSchema;
					break;
				}
			}
			if (isset($oGrpNameSchema)) {
				foreach ($oTemplateConfig->schema as $oTmplSchema) {
					if (($oTmplSchema->id === 'name' && $oTmplSchema->type === 'shorttext') || ($oTmplSchema->id === 'member.name' && $oTmplSchema->type === 'member')) {
						$oTmplSchema->fromApp = $groupAppId;
						$oTmplSchema->requireCheck = 'Y';
						if ($oTmplSchema->type === 'member') {
							$oTmplSchema->type = 'shorttext';
							unset($oTmplSchema->schema_id);
						}
						if ($oTmplSchema->id === 'member.name') {
							$oTmplSchema->id = 'name';
						}
						break;
					}
				}
				foreach ($oTemplateConfig->pages as $oTmplPage) {
					if (!empty($oTmplPage->data_schemas)) {
						foreach ($oTmplPage->data_schemas as $oTmplPageWrap) {
							$oTmplPageSchema = $oTmplPageWrap->schema;
							if (($oTmplPageSchema->id === 'name' && $oTmplPageSchema->type === 'shorttext') || ($oTmplPageSchema->id === 'member.name' && $oTmplPageSchema->type === 'member')) {
								$oTmplPageSchema->fromApp = $groupAppId;
								$oTmplPageSchema->requireCheck = 'Y';
								if ($oTmplPageSchema->type === 'member') {
									$oTmplPageSchema->type = 'shorttext';
									unset($oTmplPageSchema->schema_id);
								}
								if ($oTmplPageSchema->id === 'member.name') {
									$oTmplPageSchema->id = 'name';
								}
								break;
							}
						}
					}
				}
			}
		}
		/* 分组活动轮次 */
		$oRoundSchema = new \stdClass;
		$oRoundSchema->id = '_round_id';
		$oRoundSchema->type = 'single';
		$oRoundSchema->title = '分组名称';
		$oRoundSchema->required = 'Y';
		$oRoundSchema->ops = [];
		if (!empty($oGroupApp->rounds)) {
			foreach ($oGroupApp->rounds as $oRound) {
				$op = new \stdClass;
				$op->v = $oRound->round_id;
				$op->l = $oRound->title;
				$oRoundSchema->ops[] = $op;
			}
		}
		if (empty($oTemplateConfig->schema)) {
			$oTemplateConfig->schema = [$oRoundSchema];
		} else {
			array_splice($oTemplateConfig->schema, 0, 0, [$oRoundSchema]);
		}
		/**
		 * 处理页面数据定义
		 */
		foreach ($oTemplateConfig->pages as $oAppPage) {
			if (!empty($oAppPage->data_schemas)) {
				/* 自动添加项目阶段定义 */
				if ($oAppPage->type === 'I') {
					$newPageSchema = new \stdClass;
					$schemaPhaseConfig = new \stdClass;
					$schemaPhaseConfig->component = 'R';
					$schemaPhaseConfig->align = 'V';
					$newPageSchema->schema = $oRoundSchema;
					$newPageSchema->config = $schemaPhaseConfig;
					array_splice($oAppPage->data_schemas, 0, 0, [$newPageSchema]);
				} else if ($oAppPage->type === 'V') {
					$newPageSchema = new \stdClass;
					$schemaPhaseConfig = new \stdClass;
					$schemaPhaseConfig->id = 'V' . time();
					$schemaPhaseConfig->pattern = 'record';
					$schemaPhaseConfig->inline = 'Y';
					$schemaPhaseConfig->splitLine = 'Y';
					$newPageSchema->schema = $oRoundSchema;
					$newPageSchema->config = $schemaPhaseConfig;
					array_splice($oAppPage->data_schemas, 0, 0, [$newPageSchema]);
				}
			}
		}

		return $oTemplateConfig;
	}
	/**
	 * 根据关联的通讯录设置题目
	 */
	protected function setSchemaByMschema($mschemaId, &$oTemplateConfig) {
		/* 通讯录关联题目 */
		$oMschema1st = $this->model('site\user\memberschema')->byId($mschemaId, ['fields' => 'id,attr_name,attr_mobile,attr_email', 'cascaded' => 'N']);
		/* 应用的题目 */
		foreach ($oTemplateConfig->schema as $oSchema) {
			if ($oSchema->type === 'shorttext' && in_array($oSchema->id, ['name', 'email', 'mobile'])) {
				if (false === $oMschema1st->attrs->{$oSchema->id}->hide) {
					$oSchema->type = 'member';
					$oSchema->schema_id = $oMschema1st->id;
					$oSchema->id = 'member.' . $oSchema->id;
				}
			}
		}
		/* 页面的题目 */
		foreach ($oTemplateConfig->pages as $oAppPage) {
			if (!empty($oAppPage->data_schemas)) {
				foreach ($oAppPage->data_schemas as $oSchemaConfig) {
					$oSchema = $oSchemaConfig->schema;
					if ($oSchema->type === 'shorttext' && in_array($oSchema->id, ['name', 'email', 'mobile'])) {
						if (false === $oMschema1st->attrs->{$oSchema->id}->hide) {
							$oSchema->type = 'member';
							$oSchema->schema_id = $oMschema1st->id;
							$oSchema->id = 'member.' . $oSchema->id;
						}
					}
				}
			}
		}

		return $oTemplateConfig;
	}
	/**
	 * 查找能用作记录昵称的题目
	 */
	public function findAssignedNicknameSchema($schemas) {
		$oNicknameSchema = null;
		foreach ($schemas as $oSchema) {
			if (isset($oSchema->required) && $oSchema->required === 'Y') {
				if (in_array($oSchema->type, ['shorttext', 'member'])) {
					if ($oSchema->title === '姓名') {
						$oNicknameSchema = $oSchema;
						break;
					}
					if (strpos($oSchema->title, '姓名')) {
						if (!isset($oNicknameSchema) || strlen($oSchema->title) < strlen($oNicknameSchema->title)) {
							$oNicknameSchema = $oSchema;
						}
					} else if (isset($oSchema->format) && $oSchema->format === 'name') {
						$oNicknameSchema = $oSchema;
					}
				}
			}
		}

		return $oNicknameSchema;
	}
	/**
	 * 获得进入规则中指定的通讯录
	 */
	public function getEntryMemberSchema($oEntryRule) {
		$aMatterMschemas = [];
		if (isset($oEntryRule->member)) {
			$modelMsc = $this->model('site\user\memberschema');
			foreach (array_keys((array) $oEntryRule->member) as $mschemaId) {
				$oMschema = $modelMsc->byId($mschemaId, ['cascaded' => 'N']);
				if (!empty($oMschema->matter_type)) {
					$aMatterMschemas[] = $oMschema;
				}
			}
		}

		return $aMatterMschemas;
	}
	/**
	 * 替换应用中的通讯录题型
	 */
	public function replaceMemberSchema(&$aDataSchemas, $oMschema) {
		foreach ($aDataSchemas as $oSchema) {
			/* 和通讯录解除关联 */
			if ($oSchema->type === 'member' && $oSchema->schema_id === $oMschema->id) {
				$oSchema->type = 'shorttext';
				$oSchema->id = str_replace('member.', '', $oSchema->id);
				if (in_array($oSchema->id, ['name', 'mobile', 'email'])) {
					$oSchema->format = $oSchema->id;
				} else {
					$oSchema->format = '';
				}
				unset($oSchema->schema_id);
			}
		}

		return [true];
	}
	/**
	 * 替换应用中的关联登记或分组活动题型
	 */
	public function replaceAssocSchema(&$aDataSchemas, $aAssocAppIds) {
		foreach ($aDataSchemas as $oSchema) {
			/* 和分组活动解除关联 */
			if (isset($oSchema->fromApp) && in_array($oSchema->fromApp, $aAssocAppIds)) {
				unset($oSchema->fromApp);
				unset($oSchema->requieCheck);
			}
		}

		return [true];
	}
}