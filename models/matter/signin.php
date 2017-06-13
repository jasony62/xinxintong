<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 * 签到活动
 */
class signin_model extends app_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_signin';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'signin';
	}
	/**
	 *
	 */
	public function getEntryUrl($siteId, $id, $roundId = null) {
		$url = 'http://' . APP_HTTP_HOST;
		$url .= '/rest/site/fe/matter/signin';
		if ($siteId === 'platform') {
			$app = $this->byId($id, ['cascaded' => 'N']);
			$url .= "?site={$app->siteid}&app=" . $id;
		} else {
			$url .= "?site={$siteId}&app=" . $id;
		}

		if (!empty($roundId)) {
			$url .= '&round=' . $roundId;
		}

		return $url;
	}
	/**
	 * 签到活动的汇总展示链接
	 */
	public function getOpUrl($siteId, $id) {
		$url = 'http://' . APP_HTTP_HOST;
		$url .= '/rest/site/op/matter/signin';
		$url .= "?site={$siteId}&app=" . $id;

		return $url;
	}
	/**
	 *
	 * @param string $appId
	 * @param $options array []
	 */
	public function &byId($appId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';
		$q = [
			$fields,
			'xxt_signin',
			["id" => $appId],
		];
		if (isset($options['where'])) {
			foreach ($options['where'] as $key => $value) {
				$q[2][$key] = $value;
			}
		}

		if ($app = $this->query_obj_ss($q)) {
			$app->type = 'signin';
			if (isset($app->siteid) && isset($app->id)) {
				$app->entryUrl = $this->getEntryUrl($app->siteid, $app->id);
			}
			if (isset($app->entry_rule)) {
				$app->entry_rule = json_decode($app->entry_rule);
			}
			if ($cascaded === 'Y') {
				/* 页面 */
				$app->pages = \TMS_APP::M('matter\signin\page')->byApp($appId);
				/* 轮次 */
				$app->rounds = \TMS_APP::M('matter\signin\round')->byApp($appId);
			}
		}

		return $app;
	}
	/**
	 * 返回签到活动列表
	 */
	public function &bySite($siteId, $page = null, $size = null, $onlySns = 'N', $options = []) {
		$result = new \stdClass;
		$q = [
			"*,'signin' type",
			'xxt_signin',
			"state<>0 and siteid='$siteId'",
		];
		if (!empty($options['byTitle'])) {
			$q[2] .= " and title like '%" . $this->escape($options['byTitle']) . "%'";
		}
		if ($onlySns === 'Y') {
			$q[2] .= " and entry_rule like '%\"scope\":\"sns\"%'";
		}
		$q2['o'] = 'modify_at desc';
		if ($page && $size) {
			$q2['r']['o'] = ($page - 1) * $size;
			$q2['r']['l'] = $size;
		}
		$result->apps = $this->query_objs_ss($q, $q2);
		if ($page && $size) {
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result->total = $total;
		} else {
			$result->total = count($result->apps);
		}

		return $result;
	}
	/**
	 * 返回签到活动列表
	 */
	public function &byMission($mission, $options = [], $page = null, $size = null) {
		$mission = $this->escape($mission);
		$result = new \stdClass;
		$q = [
			"*,'signin' type",
			'xxt_signin',
			"state<>0 and mission_id='$mission'",
		];
		if (isset($options['where'])) {
			foreach ($options['where'] as $key => $value) {
				$key = $this->escape($key);
				$value = $this->escape($value);
				$q[2] .= " and " . $key . " = '" . $value . "'";
			}
		}
		if (!empty($options['byTitle'])) {
			$q[2] .= " and title like '%" . $this->escape($options['byTitle']) . "%'";
		}
		$q2['o'] = 'modify_at desc';
		if ($page && $size) {
			$q2['r']['o'] = ($page - 1) * $size;
			$q2['r']['l'] = $size;
		}
		$result->apps = $this->query_objs_ss($q, $q2);
		if ($page && $size) {
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result->total = $total;
		} else {
			$result->total = count($result->apps);
		}

		return $result;
	}
	/**
	 * 返回和登记活动关联的签到活动
	 */
	public function &byEnrollApp($enrollAppId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';
		$mapRounds = isset($options['mapRounds']) ? $options['mapRounds'] : 'N';

		$q = [
			$fields,
			'xxt_signin',
			"state<>0 and enroll_app_id='" . $this->escape($enrollAppId) . "'",
		];
		$q2['o'] = 'create_at asc';

		$apps = $this->query_objs_ss($q, $q2);
		if (count($apps) && $cascaded === 'Y') {
			$modelRnd = \TMS_APP::M('matter\signin\round');
			foreach ($apps as &$app) {
				$options = $mapRounds === 'Y' ? ['mapRounds' => 'Y'] : [];
				$rounds = $modelRnd->byApp($app->id, $options);
				$app->rounds = $rounds;
			}
		}

		return $apps;
	}
	/**
	 * 更新登记活动标签
	 */
	public function updateTags($aid, $tags) {
		if (empty($tags)) {
			return false;
		}
		if (is_array($tags)) {
			$tags = implode(',', $tags);
		}

		$options = array('fields' => 'tags', 'cascaded' => 'N');
		$app = $this->byId($aid, $options);
		if (empty($app->tags)) {
			$this->update('xxt_signin', array('tags' => $tags), "id='$aid'");
		} else {
			$existent = explode(',', $app->tags);
			$checked = explode(',', $tags);
			$updated = array();
			foreach ($checked as $c) {
				if (!in_array($c, $existent)) {
					$updated[] = $c;
				}
			}
			if (count($updated)) {
				$updated = array_merge($existent, $updated);
				$updated = implode(',', $updated);
				$this->update('xxt_signin', array('tags' => $updated), "id='$aid'");
			}
		}

		return true;
	}
	/**
	 * 活动报名名单
	 *
	 * 1、如果活动仅限会员报名，那么要叠加会员信息
	 * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
	 *
	 * $mpid
	 * $aid
	 * $options
	 * --creater openid
	 * --visitor openid
	 * --page
	 * --size
	 * --rid 轮次id
	 * --kw 检索关键词
	 * --by 检索字段
	 *
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 */
	public function participants($siteId, $appId, $options = null, $criteria = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
		}

		$w = "state=1 and aid='$appId' and userid<>''";

		// 指定了登记记录过滤条件
		if (!empty($criteria->record)) {
			$whereByRecord = '';
			if (!empty($criteria->record->verified)) {
				$whereByRecord .= " and verified='{$criteria->record->verified}'";
			}
			$w .= $whereByRecord;
		}

		// 指定了记录标签
		if (!empty($criteria->tags)) {
			$whereByTag = '';
			foreach ($criteria->tags as $tag) {
				$whereByTag .= " and concat(',',tags,',') like '%,$tag,%'";
			}
			$w .= $whereByTag;
		}

		// 指定了登记数据过滤条件
		if (isset($criteria->data)) {
			$whereByData = '';
			foreach ($criteria->data as $k => $v) {
				if (!empty($v)) {
					$whereByData .= ' and (';
					$whereByData .= 'data like \'%"' . $k . '":"' . $v . '"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . '"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . ',%"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"' . $v . ',%"%\'';
					$whereByData .= ')';
				}
			}
			$w .= $whereByData;
		}

		// 获得填写的登记数据
		$q = [
			'userid',
			"xxt_signin_record",
			$w,
		];
		$participants = $this->query_vals_ss($q);

		return $participants;
	}
	/**
	 *
	 */
	public function &opData(&$app) {
		$mdoelRec = $this->model('matter\signin\record');
		$summary = $mdoelRec->summary($app->siteid, $app->id);

		return $summary;
	}
	/**
	 * 获得参加登记活动的用户的昵称
	 *
	 * @param object $oApp
	 * @param object $oUser [uid,nickname]
	 */
	public function getUserNickname(&$oApp, $oUser) {
		$nickname = '';
		$entryRule = $oApp->entry_rule;
		if (isset($entryRule->scope) && $entryRule->scope === 'member') {
			foreach ($entryRule->member as $schemaId => $rule) {
				$modelMem = $this->model('site\user\member');
				if (empty($oUser->unionid)) {
					$aMembers = $modelMem->byUser($oUser->uid, ['schemas' => $schemaId]);
					if (count($aMembers) === 1) {
						$oMember = $aMembers[0];
						if ($oMember->verified === 'Y') {
							$nickname = empty($oMember->name) ? $oMember->identity : $oMember->name;
							break;
						}
					}
				} else {
					$modelAcnt = $this->model('site\user\account');
					$aUnionUsers = $modelAcnt->byUnionid($oUser->unionid, ['siteid' => $oApp->siteid, 'fields' => 'uid']);
					foreach ($aUnionUsers as $oUnionUser) {
						$aMembers = $modelMem->byUser($oUnionUser->uid, ['schemas' => $schemaId]);
						if (count($aMembers) === 1) {
							$oMember = $aMembers[0];
							if ($oMember->verified === 'Y') {
								$nickname = empty($oMember->name) ? $oMember->identity : $oMember->name;
								break;
							}
						}
					}
					if (!empty($nickname)) {
						break;
					}
				}
			}
		} else if (isset($entryRule->scope) && $entryRule->scope === 'sns') {
			foreach ($entryRule->sns as $snsName => $rule) {
				if ($snsName === 'wx') {
					$modelWx = $this->model('sns\wx');
					if (($wxConfig = $modelWx->bySite($oApp->siteid)) && $wxConfig->joined === 'Y') {
						$snsSiteId = $oApp->siteid;
					} else {
						$snsSiteId = 'platform';
					}
				} else {
					$snsSiteId = $oApp->siteid;
				}
				$modelAcnt = $this->model('site\user\account');
				$siteUser = $modelAcnt->byId($oUser->uid);
				$modelSnsUser = $this->model('sns\\' . $snsName . '\fan');
				if ($snsUser = $modelSnsUser->byOpenid($snsSiteId, $siteUser->{$snsName . '_openid'})) {
					$nickname = $snsUser->nickname;
					break;
				}
			}
		} else if (empty($entryRule->scope) || $entryRule->scope === 'none') {
			/* 不限制用户访问来源 */
			$nickname = empty($oUser->nickname) ? '' : $oUser->nickname;
		}

		return $nickname;
	}
}