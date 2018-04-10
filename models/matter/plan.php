<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 *
 */
class plan_model extends app_base {
	/**
	 * 记录日志时需要的列
	 */
	const LOG_FIELDS = 'siteid,id,title,summary,pic,mission_id';
	/**
	 *
	 */
	protected function table() {
		return 'xxt_plan';
	}
	/**
	 * 活动进入链接
	 */
	public function getEntryUrl($siteId, $id) {
		if ($siteId === 'platform') {
			$oApp = $this->byId($id, ['fields' => 'siteid,state', 'cascaded' => 'N']);
			if (false === $oApp || $oApp->state !== '1') {
				return APP_PROTOCOL . APP_HTTP_HOST . '/404.html';
			} else {
				$siteId = $oApp->siteid;
			}
		}

		$url = APP_PROTOCOL . APP_HTTP_HOST;
		$url .= '/rest/site/fe/matter/plan';
		$url .= "?site={$siteId}&app={$id}";

		return $url;
	}
	/**
	 * 任务活动的汇总展示链接
	 */
	public function getOpUrl($siteId, $id) {
		$url = APP_PROTOCOL . APP_HTTP_HOST;
		$url .= '/rest/site/op/matter/plan';
		$url .= "?site={$siteId}&app=" . $id;

		return $url;
	}
	/**
	 * 任务活动的统计报告链接
	 */
	public function getRpUrl($siteId, $id) {
		$url = APP_PROTOCOL . APP_HTTP_HOST;
		$url .= '/rest/site/op/matter/plan/report';
		$url .= "?site={$siteId}&app=" . $id;

		return $url;
	}
	/**
	 * 获得指定素材
	 */
	public function &byId($id, $aOptions = []) {
		if ($oMatter = parent::byId($id, $aOptions)) {
			$oMatter->type = 'plan';
			if (!empty($oMatter->siteid) && !empty($oMatter->id)) {
				$oMatter->entryUrl = $this->getEntryUrl($oMatter->siteid, $oMatter->id);
				$oMatter->opUrl = $this->getOpUrl($oMatter->siteid, $oMatter->id);
				$oMatter->rpUrl = $this->getRpUrl($oMatter->siteid, $oMatter->id);
			}
			/* entry rule */
			if (property_exists($oMatter, 'entry_rule')) {
				$oMatter->entryRule = $oMatter->entry_rule;
				unset($oMatter->entry_rule);
			}
			/* check schemas */
			if (property_exists($oMatter, 'check_schemas')) {
				$oMatter->checkSchemas = empty($oMatter->check_schemas) ? [] : json_decode($oMatter->check_schemas);
				unset($oMatter->check_schemas);
			}
			/* entry rule */
			if (property_exists($oMatter, 'rp_config')) {
				if (!empty($oMatter->rp_config)) {
					$oMatter->rpConfig = json_decode($oMatter->rp_config);
				} else {
					$oMatter->rpConfig = new \stdClass;
				}
				unset($oMatter->rp_config);
			}
		}

		return $oMatter;
	}
	/**
	 * 创建一个空的签到活动
	 *
	 * @param string $site site's id
	 * @param string $mission mission's id
	 *
	 */
	public function createByTemplate($oUser, $oSite, $oCustomConfig, $oMission = null) {
		$oNewApp = new \stdClass;
		$appId = uniqid();
		$oNewApp->siteid = $oSite->id;
		$oNewApp->id = $appId;
		$oProto = $oCustomConfig->proto;

		/* 从站点和项目中获得pic定义 */
		if (!empty($oMission)) {
			$oNewApp->summary = empty($oProto->summary) ? $oMission->summary : $oProto->summary;
			$oNewApp->pic = $oMission->pic;
			$oNewApp->mission_id = $oMission->id;
			$oMisEntryRule = $oMission->entry_rule;
		} else {
			$oNewApp->summary = empty($oProto->summary) ? '' : $oProto->summary;
			$oNewApp->pic = $oSite->heading_pic;
		}

		/* 用户指定的属性 */
		$title = empty($oProto->title) ? '新计划活动' : $this->escape($oProto->title);
		$oNewApp->title = $title;
		$oNewApp->summary = $this->escape($oNewApp->summary);

		$oEntryRule = new \stdClass;
		if (isset($oProto->entryRule->scope)) {
			$oScope = $oProto->entryRule->scope;
			$oEntryRule->scope = new \stdClass;
			if (!empty($oScope->member) && $oScope->member === 'Y') {
				$oEntryRule->scope->member = 'Y';
				$oEntryRule->member = new \stdClass;
				if (!empty($oProto->entryRule->mschemas)) {
					foreach ($oProto->entryRule->mschemas as $oMschema) {
						if ($oMschema->id === '_pending') {
							/* 给活动创建通讯录 */
							$oMschemaConfig = new \stdClass;
							$oMschemaConfig->matter_id = $oNewApp->id;
							$oMschemaConfig->matter_type = 'plan';
							$oMschemaConfig->valid = 'Y';
							$oMschemaConfig->title = $oNewApp->title . '-通讯录';
							$oAppMschema = $this->model('site\user\memberschema')->create($oSite, $oUser, $oMschemaConfig);
							$oMschema->id = $oAppMschema->id;
						}
						$oEntryRule->member->{$oMschema->id} = (object) ['entry' => ''];
					}
				}
			}
			if (!empty($oScope->sns) && $oScope->sns === 'Y') {
				$oEntryRule->scope->sns = 'Y';
				$oEntryRule->sns = new \stdClass;
				if (isset($oProto->entryRule->sns)) {
					foreach ($oProto->entryRule->sns as $snsName => $valid) {
						if ($valid) {
							$oEntryRule->sns->{$snsName} = (object) ['entry' => 'Y'];
						}
					}
				}
			}
			if (!empty($oScope->group) && $oScope->group === 'Y') {
				$oEntryRule->scope->group = 'Y';
				$oEntryRule->group = new \stdClass;
				if (!empty($oProto->entryRule->group->id)) {
					$oEntryRule->group->id = $oProto->entryRule->group->id;
				}
			}
		}

		$oNewApp->entry_rule = $this->toJson($oEntryRule);

		$oNewApp->check_schemas = '[]';
		$oNewApp->jump_delayed = 'Y';
		$oNewApp->auto_verify = 'Y';

		$oNewApp = $this->create($oUser, $oNewApp);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');

		return $oNewApp;
	}
}