<?php
namespace pl\fe\matter\link;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/**
 *
 */
class main extends \pl\fe\matter\main_base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/link/frame');
		exit;
	}
	/**
	 *
	 */
	public function setting_action() {
		\TPL::output('/pl/fe/matter/link/frame');
		exit;
	}
	/**
	 *
	 */
	public function get_action($id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();
		$options = $this->getPostJson();

		$oLink = $this->model('matter\link')->byIdWithParams($id);
		/* 指定分组活动访问 */
		if (isset($oLink->entry_rule->scope->group) && $oLink->entry_rule->scope->group === 'Y') {
			if (isset($oLink->entry_rule->group)) {
				!is_object($oLink->entry_rule->group) && $oLink->entry_rule->group = (object) $oLink->entry_rule->group;
				$oRuleApp = $oLink->entry_rule->group;
				if (!empty($oRuleApp->id)) {
					$oGroupApp = $this->model('matter\group')->byId($oRuleApp->id, ['fields' => 'title', 'cascaded' => 'N']);
					if ($oGroupApp) {
						$oRuleApp->title = $oGroupApp->title;
						if (!empty($oRuleApp->team->id)) {
							$oGrpTeam = $this->model('matter\group\team')->byId($oRuleApp->team->id, ['fields' => 'title']);
							if ($oGrpTeam) {
								$oRuleApp->team->title = $oGrpTeam->title;
							}
						}
					}
				}
			}
		}

		return new \ResponseData($oLink);
	}
	/**
	 *
	 */
	public function list_action($site, $cascade = 'Y') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();
		$oOptions = $this->getPostJson();
		/**
		 * get links
		 */
		$q = [
			"*",
			'xxt_link l',
			"siteid='$site' and state=1",
		];
		if (!empty($oOptions->byTitle)) {
			$q[2] .= " and title like '%" . $model->escape($oOptions->byTitle) . "%'";
		}
		if (!empty($oOptions->byTags)) {
			foreach ($oOptions->byTags as $tag) {
				$q[2] .= " and matter_mg_tag like '%" . $model->escape($tag->id) . "%'";
			}
		}
		if (isset($oOptions->byStar) && $oOptions->byStar === 'Y') {
			$q[2] .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='article' and t.matter_id=l.id and userid='{$oUser->id}')";
		}
		$q2['o'] = 'create_at desc';
		$links = $model->query_objs_ss($q, $q2);
		/**
		 * get params and channels
		 */
		if ($cascade === 'Y') {
			$modelChn = $this->model('matter\channel');
			foreach ($links as $l) {
				/**
				 * params
				 */
				$q = array('id,pname,pvalue',
					'xxt_link_param',
					"link_id=$l->id");
				$l->params = $model->query_objs_ss($q);
				/**
				 * channels
				 */
				$l->channels = $modelChn->byMatter($l->id, 'link');
				/**
				 * acl
				 */
				$l->type = 'link';
			}
		}

		return new \ResponseData(['docs' => $links, 'total' => count($links)]);
	}
	/**
	 *
	 */
	public function cascade_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/**
		 * params
		 */
		$q = array(
			'id,pname,pvalue',
			'xxt_link_param',
			"link_id='$id'",
		);
		$l['params'] = $this->model()->query_objs_ss($q);
		/**
		 * channels
		 */
		$l['channels'] = $this->model('matter\channel')->byMatter($id, 'link');

		return new \ResponseData($l);
	}
	/**
	 * 创建外部链接素材
	 */
	public function create_action($site = null, $mission = null, $title = '新链接') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelLink = $this->model('matter\link');
		$oLink = new \stdClass;
		/*从站点或项目获取的定义*/
		if (empty($mission)) {
			$oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
			if (false === $oSite) {
				return new \ObjectNotFoundError();
			}
			$oLink->siteid = $oSite->id;
			$oLink->pic = $oSite->heading_pic; //使用站点的缺省头图
			$oLink->summary = '';
		} else {
			$modelMis = $this->model('matter\mission');
			$oMission = $modelMis->byId($mission);
			$oLink->siteid = $oMission->siteid;
			$oLink->summary = $modelLink->escape($oMission->summary);
			$oLink->pic = $oMission->pic;
			$oLink->mission_id = $oMission->id;
		}

		$oLink->title = $modelLink->escape($title);

		$oLink = $modelLink->create($oUser, $oLink);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oLink->siteid, $oUser, $oLink, 'C');

		return new \ResponseData($oLink);
	}
	/**
	 * 更新链接属性
	 */
	public function update_action($id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelLink = $this->model('matter\link');
		$oLink = $modelLink->byId($id);
		if (false === $oLink) {
			return new \ObjectNotFoundError();
		}
		/**
		 * 处理数据
		 */
		$oUpdated = $this->getPostJson();
		foreach ($oUpdated as $n => $v) {
			if (in_array($n, ['title'])) {
				$oUpdated->{$n} = $modelLink->escape($v);
			} else if ($n === 'entry_rule') {
				if ($v->scope === 'group') {
					if (isset($v->group->title)) {
						unset($v->group->title);
					}
					if (isset($v->group->team->title)) {
						unset($v->group->team->title);
					}
				}
				$oUpdated->entry_rule = $modelLink->escape($modelLink->toJson($v));
			} else if ($n === 'config') {
				$oUpdated->config = $modelLink->escape($modelLink->toJson($v));
			}

			$oLink->{$n} = $v;
		}

		if ($oLink = $modelLink->modify($oUser, $oLink, $oUpdated)) {
			$this->model('matter\log')->matterOp($oLink->siteid, $oUser, $oLink, 'U');
		}

		return new \ResponseData($oLink);
	}
	/**
	 * 删除链接
	 */
	public function remove_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelLink = $this->model('matter\link');
		$oLink = $modelLink->byId($id);
		if (false === $oLink) {
			return new \ObjectNotFoundError();
		}

		$rst = $modelLink->remove($oUser, $oLink);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * @param $linkid link's id
	 */
	public function paramAdd_action($site, $linkid) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$p['link_id'] = $linkid;

		$id = $this->model()->insert('xxt_link_param', $p);

		return new \ResponseData($id);
	}
	/**
	 *
	 * 更新参数定义
	 *
	 * 因为参数的属性之间存在关联，因此要整体更新
	 *
	 * @param $id parameter's id
	 */
	public function paramUpd_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$p = $this->getPostJson();

		!empty($p->pvalue) && $p->pvalue = urldecode($p->pvalue);

		$rst = $this->model()->update(
			'xxt_link_param',
			$p,
			"id=$id"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * @param $id parameter's id
	 */
	public function removeParam_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$rst = $this->model()->delete('xxt_link_param', "id=$id");

		return new \ResponseData($rst);
	}
}