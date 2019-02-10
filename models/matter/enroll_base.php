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
		if (isset($oProtoEntryRule->scope) && is_object($oProtoEntryRule->scope)) {
			$oEntryRule->scope = $oProtoEntryRule->scope;
			if ($this->getDeepValue($oEntryRule, 'scope.member') === 'Y') {
				if (isset($oProtoEntryRule->member)) {
					$oEntryRule->member = new \stdClass;
					foreach ($oProtoEntryRule->member as $msid => $oMschema) {
						$oRule = new \stdClass;
						$oRule->entry = 'Y';
						$oEntryRule->member->{$msid} = $oRule;
					}
				}
			}
			if ($this->getDeepValue($oEntryRule, 'scope.group') === 'Y') {
				if (!empty($oProtoEntryRule->group->id)) {
					$oEntryRule->group = (object) ['id' => $oProtoEntryRule->group->id];
					if (!empty($oProtoEntryRule->group->team->id)) {
						$oEntryRule->group->team = (object) ['id' => $oProtoEntryRule->group->team->id];
					}
				}
			}
			if ($this->getDeepValue($oEntryRule, 'scope.enroll') === 'Y') {
				if (!empty($oProtoEntryRule->enroll->id)) {
					$oEntryRule->enroll = (object) ['id' => $oProtoEntryRule->enroll->id];
				}
			}
			if ($this->getDeepValue($oEntryRule, 'scope.sns') === 'Y') {
				$oRule = new \stdClass;
				$oRule->entry = 'Y';
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
			}
		} else {
			$oEntryRule->scope = new \stdClass;
		}

		return $oEntryRule;
	}
	/**
	 * 根据项目指定的规则设置
	 */
	protected function setEntryRuleByMission(&$oEntryRule, $oMisEntryRule) {
		if (isset($oMisEntryRule->scope)) {
			if (empty($oEntryRule->scope) || !is_object($oEntryRule->scope)) {
				$oEntryRule->scope = new \stdClass;
			}
			if ($this->getDeepValue($oMisEntryRule, 'scope.register') === 'Y') {
				$oEntryRule->scope->register = 'Y';
			}
			if (isset($oMisEntryRule->member)) {
				$oEntryRule->member = $oMisEntryRule->member;
				foreach ($oEntryRule->member as &$oRule) {
					$oRule->entry = isset($oEntryRule->otherwise->entry) ? $oEntryRule->otherwise->entry : '';
				}
				$oEntryRule->scope->member = 'Y';
			}
			if (isset($oMisEntryRule->sns)) {
				$oEntryRule->sns = new \stdClass;
				foreach ($oMisEntryRule->sns as $snsName => $oRule) {
					if (isset($oRule->entry) && $oRule->entry === 'Y') {
						$oEntryRule->sns->{$snsName} = new \stdClass;
						$oEntryRule->sns->{$snsName}->entry = isset($oEntryRule->otherwise->entry) ? $oEntryRule->otherwise->entry : '';
					}
				}
				$oEntryRule->scope->sns = 'Y';
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
				if (($oGrpSchema->id === 'name' && $oGrpSchema->type === 'shorttext') || ($oGrpSchema->id === 'member.name' && in_array($oGrpSchema->type, ['shorttext']))) {
					$oGrpNameSchema = $oGrpSchema;
					break;
				}
			}
			if (isset($oGrpNameSchema)) {
				/* 替换模板中包含的姓名题 */
				foreach ($oTemplateConfig->schema as $oTmplSchema) {
					if ($oTmplSchema->type === 'shorttext' || in_array($oTmplSchema->id, ['name', 'member.name'])) {
						$oTmplSchema->fromApp = $groupAppId;
						$oTmplSchema->requireCheck = 'Y';
						if (isset($oTmplSchema->mschema_id)) {
							unset($oTmplSchema->mschema_id);
						}
						if ($oTmplSchema->id === 'member.name') {
							$oTmplSchema->id = 'name';
						}
						$oTmplNameSchema = $oTmplSchema;
						break;
					}
				}
				if (isset($oTmplNameSchema)) {
					/* 替换页面中包含的姓名题 */
					foreach ($oTemplateConfig->pages as $oTmplPage) {
						if (!empty($oTmplPage->data_schemas)) {
							foreach ($oTmplPage->data_schemas as $oTmplPageWrap) {
								$oTmplPageSchema = $oTmplPageWrap->schema;
								if ($oTmplPageSchema->id === $oTmplNameSchema) {
									$oTmplPageSchema->fromApp = $groupAppId;
									$oTmplPageSchema->requireCheck = 'Y';
									if (isset($oTmplPageSchema->mschema_id)) {
										unset($oTmplPageSchema->mschema_id);
									}
									if ($oTmplPageSchema->id === 'member.name') {
										$oTmplPageSchema->id = 'name';
									}
									break;
								}
							}
						}
					}
				} else {
					/* 模板中没有姓名题，添加 */
					$oNameSchema = new \stdClass;
					$oNameSchema->id = $oGrpNameSchema->id;
					$oNameSchema->type = $oGrpNameSchema->type;
					$oNameSchema->title = $oGrpNameSchema->title;
					$oNameSchema->format = 'name';
					$oNameSchema->required = 'Y';
					$oNameSchema->fromApp = $groupAppId;
					$oNameSchema->requireCheck = 'Y';
					if (empty($oTemplateConfig->schema)) {
						$oTemplateConfig->schema = [$oNameSchema];
					} else {
						array_splice($oTemplateConfig->schema, 0, 0, [$oNameSchema]);
					}
					/**
					 * 处理页面数据定义
					 */
					foreach ($oTemplateConfig->pages as $oAppPage) {
						if (!empty($oAppPage->data_schemas)) {
							/* 自动添加项目阶段定义 */
							if ($oAppPage->type === 'I') {
								$newPageSchema = new \stdClass;
								$oSchemaNameConfig = new \stdClass;
								$newPageSchema->schema = $oNameSchema;
								$newPageSchema->config = $oSchemaNameConfig;
								array_splice($oAppPage->data_schemas, 0, 0, [$newPageSchema]);
							} else if ($oAppPage->type === 'V') {
								$newPageSchema = new \stdClass;
								$oSchemaNameConfig = new \stdClass;
								$oSchemaNameConfig->id = 'V' . time();
								$oSchemaNameConfig->pattern = 'record';
								$oSchemaNameConfig->splitLine = 'Y';
								$newPageSchema->schema = $oNameSchema;
								$newPageSchema->config = $oSchemaNameConfig;
								array_splice($oAppPage->data_schemas, 0, 0, [$newPageSchema]);
							}
						}
					}
				}
			}
		}
		/* 分组活动轮次 */
		$oGrpSchema = $this->model('matter\enroll\schema')->newAssocGroupSchema($oGroupApp);
		if (empty($oTemplateConfig->schema)) {
			$oTemplateConfig->schema = [$oGrpSchema];
		} else {
			array_splice($oTemplateConfig->schema, 0, 0, [$oGrpSchema]);
		}
		/**
		 * 处理页面数据定义
		 */
		foreach ($oTemplateConfig->pages as $oAppPage) {
			if (!empty($oAppPage->data_schemas)) {
				if ($oAppPage->type === 'I') {
					$newPageSchema = new \stdClass;
					$oSchemaRoundConfig = new \stdClass;
					$oSchemaRoundConfig->component = 'R';
					$oSchemaRoundConfig->align = 'V';
					$newPageSchema->schema = $oGrpSchema;
					$newPageSchema->config = $oSchemaRoundConfig;
					array_splice($oAppPage->data_schemas, 0, 0, [$newPageSchema]);
				} else if ($oAppPage->type === 'V') {
					$newPageSchema = new \stdClass;
					$oSchemaRoundConfig = new \stdClass;
					$oSchemaRoundConfig->id = 'V' . time();
					$oSchemaRoundConfig->pattern = 'record';
					$oSchemaRoundConfig->splitLine = 'Y';
					$newPageSchema->schema = $oGrpSchema;
					$newPageSchema->config = $oSchemaRoundConfig;
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
					$oSchema->mschema_id = $oMschema1st->id;
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
							$oSchema->mschema_id = $oMschema1st->id;
							$oSchema->id = 'member.' . $oSchema->id;
						}
					}
					break;
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
	public function replaceMemberSchema(&$aDataSchemas, $oMschema = null, $bKeepSchemaId = false) {
		foreach ($aDataSchemas as $oSchema) {
			/* 和通讯录解除关联 */
			if (isset($oSchema->mschema_id)) {
				if (empty($oMschema) || $oSchema->mschema_id === $oMschema->id) {
					$oSchema->type = 'shorttext';
					$memberProp = str_replace('member.', '', $oSchema->id);
					if (!$bKeepSchemaId) {
						$oSchema->id = $memberProp;
					}
					if (in_array($memberProp, ['name', 'mobile', 'email'])) {
						$oSchema->format = $memberProp;
					} else {
						$oSchema->format = '';
					}
					unset($oSchema->mschema_id);
				}
			}
		}

		return [true];
	}
	/**
	 * 替换应用中的关联登记或分组活动题型
	 */
	public function replaceAssocSchema(&$aDataSchemas, $aAssocAppIds = null) {
		foreach ($aDataSchemas as $oSchema) {
			/* 和分组活动解除关联 */
			if (isset($oSchema->fromApp) && (empty($aAssocAppIds) || in_array($oSchema->fromApp, $aAssocAppIds))) {
				unset($oSchema->fromApp);
				unset($oSchema->requireCheck);
			}
		}

		return [true];
	}
	/**
	 * 解除和项目的关联
	 */
	public function quitMission($oApp) {
		$modelPg = $this->model('matter\\' . $this->getTypeName() . '\\page');
		$oUpdatedApp = new \stdClass; // 要更新的活动数据
		$aUpdatedPages = []; // 要更新的页面数据
		$aDataSchemas = $oApp->dataSchemas;
		$bDataSchemasModified = false;
		$oEntryRule = $oApp->entryRule;
		$bEntryRuleModified = false;

		/* 修改进入规则 */
		if (isset($oEntryRule->scope)) {
			if ($oEntryRule->scope->member === 'Y') {
				/* 移除和项目通讯录的关联 */
				$aMatterMschemas = $this->getEntryMemberSchema($oEntryRule);
				foreach ($aMatterMschemas as $oMschema) {
					if (!empty($oMschema->matter_type)) {
						/* 页面的题目 */
						foreach ($oApp->pages as $oPage) {
							$rst = $modelPg->replaceMemberSchema($oPage, $oMschema);
							if (true === $rst[0]) {
								$aUpdatedPages[$oPage->id] = $oPage;
							}
						}
						/* 应用的题目 */
						$rst = $this->replaceMemberSchema($aDataSchemas, $oMschema);
						if (true === $rst[0]) {
							$bDataSchemasModified = true;
						}
						unset($oEntryRule->member->{$oMschema->id});
						$bEntryRuleModified = true;
					}
				}
				if (count((array) $oEntryRule->member) === 0) {
					$oEntryRule->scope = 'none';
					unset($oEntryRule->member);
				}
			}
		}
		/* 移除和项目中其他活动的关联 */
		$aAssocApps = [];
		if (isset($oEntryRule->group->id)) {
			$aAssocApps[] = $oEntryRule->group->id;
			unset($oEntryRule->scope->group);
			unset($oEntryRule->group);
			$bEntryRuleModified = true;
		}
		if (isset($oEntryRule->enroll->id)) {
			$aAssocApps[] = $oEntryRule->enroll->id;
			unset($oEntryRule->scope->enroll);
			unset($oEntryRule->enroll);
			$bEntryRuleModified = true;
		}
		if (count($aAssocApps)) {
			/* 页面的题目 */
			foreach ($oApp->pages as $oPage) {
				$rst = $modelPg->replaceAssocSchema($oPage, $aAssocApps);
				if (true === $rst[0]) {
					$aUpdatedPages[$oPage->id] = $oPage;
				}
			}
			/* 应用的题目 */
			$rst = $this->replaceAssocSchema($aDataSchemas, $aAssocApps);
			if (true === $rst[0]) {
				$bDataSchemasModified = true;
			}
		}

		/* 设置更新的属性 */
		$oUpdatedApp->mission_id = $oApp->mission_id = 0;
		$oUpdatedApp->sync_mission_round = $oApp->sync_mission_round = 'N';
		if ($bDataSchemasModified) {
			$oUpdatedApp->data_schemas = $this->escape($this->toJson($aDataSchemas));
		}if ($bEntryRuleModified) {
			$oUpdatedApp->entry_rule = $this->escape(json_encode($oEntryRule));
		}
		if (count($aUpdatedPages)) {
			$oUpdatedApp->pages = $aUpdatedPages;
		}

		return [true, $oUpdatedApp];
	}
}