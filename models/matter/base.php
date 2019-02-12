<?php
namespace matter;
/**
 * 素材基类
 */
class base_model extends \TMS_MODEL {
	/**
	 * byId方法中的id字段
	 */
	protected function id() {
		return 'id';
	}
	/**
	 * 素材类型名称
	 */
	public function getTypeName() {
		$cls = get_class($this);
		$cls = str_replace('matter\\', '', $cls);
		$cls = explode('_', $cls);
		if (count($cls) === 2) {
			return $cls[0];
		}
		throw new \Exception();
	}
	/**
	 * 返回素材的分类，doc或者app
	 */
	public function getTypeCategory() {
		$matterType = $this->getTypeName();
		$map = [
			'article' => 'doc',
			'news' => 'doc',
			'channel' => 'doc',
			'link' => 'doc',
			'custom' => 'doc',
			'enroll' => 'app',
			'signin' => 'app',
			'group' => 'app',
		];

		return isset($map[$matterType]) ? $map[$matterType] : '';
	}
	/**
	 * 根据类型和ID获得素材
	 */
	public function getCardInfoById($type, $id) {
		$table = 'xxt_' . $type;
		$q = ['id,title,summary,pic', $table, ["id" => $id]];
		if ($matter = $this->query_obj_ss($q)) {
			$matter->type = $type;
		}

		return $matter;
	}
	/**
	 * 根据类型和ID获得素材基本信息，mpid,id和title
	 */
	public function getMatterInfoById($type, $id) {
		switch ($type) {
		case 'text':
			$q = ['id,title', 'xxt_text', ["id" => $id]];
			break;
		case 'mschema':
			$q = ['id,title', 'xxt_site_member_schema', ["id" => $id]];
			break;
		default:
			$table = 'xxt_' . $type;
			$q = ['id,title', $table, ["id" => $id]];
		}

		if ($matter = $this->query_obj_ss($q)) {
			$matter->type = $type;
		}

		return $matter;
	}
	/**
	 * 获得指定素材
	 */
	public function &byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			$this->table(),
			[$this->id() => $id],
		];
		if ($oMatter = $this->query_obj_ss($q)) {
			$oMatter->type = $this->getTypeName();
			/* entry rule */
			if (isset($oMatter->entry_rule)) {
				$oMatter->entry_rule = json_decode($oMatter->entry_rule);
			}
			if (property_exists($oMatter, 'config')) {
				$oMatter->config = empty($oMatter->config) ? new \stdClass : json_decode($oMatter->config);
			}
		}

		return $oMatter;
	}
	/**
	 * 获得素材的邀请链接
	 * 只返回平台生成的邀请链接
	 */
	public function getInviteUrl($id, $siteId = null) {
		if (empty($siteId)) {
			$oMatter = $this->byId($id, ['fields' => 'siteid']);
		} else {
			$oMatter = (object) ['id' => $id, 'siteid' => $siteId, 'type' => $this->getTypeName()];
		}
		if ($oMatter) {
			$oMatter->id = $id;
			$oCreator = new \stdClass;
			$oCreator->id = $oMatter->siteid;
			$oCreator->name = '';
			$oCreator->type = 'S';

			$modelInv = $this->model('invite');
			$oInvite = $modelInv->byMatter($oMatter, $oCreator, ['fields' => 'id,state,code,expire_at']);
			if ($oInvite) {
				$entryUrl = $modelInv->getEntryUrl($oInvite);
			} else {
				$entryUrl = false;
			}
		} else {
			$entryUrl = false;
		}

		return $entryUrl;
	}
	/**
	 * 新建素材
	 * 1、记录和项目的关系
	 * 2、记录和团队的关系
	 *
	 */
	public function create($oUser, $oNewMatter) {
		/* 记录操作人信息 */
		$oNewMatter->creater = $oNewMatter->modifier = $oUser->id;
		$oNewMatter->creater_name = $oNewMatter->modifier_name = $this->escape($oUser->name);
		$oNewMatter->create_at = $oNewMatter->modify_at = time();

		if (empty($oNewMatter->id)) {
			$oNewMatter->id = $this->insert($this->table(), $oNewMatter, true);
		} else {
			$this->insert($this->table(), $oNewMatter, false);
		}
		$oNewMatter->type = $this->getTypeName();

		/* 记录和任务的关系 */
		if (isset($oNewMatter->mission_id)) {
			$modelMis = $this->model('matter\mission');
			$modelMis->addMatter($oUser, $oNewMatter->siteid, $oNewMatter->mission_id, $oNewMatter);
		}
		/* 记录和团队的关系 */
		$modelSite = $this->model('site');
		$modelSite->addMatter($oUser, $oNewMatter, $this->getTypeCategory());

		return $oNewMatter;
	}
	/**
	 * 更新素材信息并记录
	 *
	 * @param object $oUser 修改人
	 * @param object $oMatter 被修改的素材
	 * @param object $oUpdated 被修改的内容
	 *
	 */
	public function modify($oUser, $oMatter, $oUpdated) {
		$current = time();

		/* 记录修改日志 */
		$oUpdated->modifier = $oUser->id;
		$oUpdated->modifier_name = $this->escape($oUser->name);
		$oUpdated->modify_at = $current;

		$rst = $this->update(
			$this->table(),
			$oUpdated,
			["id" => $oMatter->id]
		);

		if ($rst) {
			foreach ($oUpdated as $k => $v) {
				$oMatter->{$k} = $v;
			}
			if (!empty($oMatter->mission_id)) {
				// 更新所在项目信息
				$this->model('matter\mission')->updateMatter($oMatter->mission_id, $oMatter);
			}
			/* 更新团队中的记录 */
			$modelSite = $this->model('site');
			$modelSite->updateMatter($oMatter);

			return $oMatter;
		}

		return false;
	}
	/**
	 * 检查素材进入规则
	 */
	public function scanEntryRule($oRule) {
		$oCheckedRule = clone $oRule;
		if ($this->getDeepValue($oRule, 'scope.member') === 'Y') {
			if (empty($oRule->member) || empty((array) $oRule->member)) {
				return [false, '进入规则不完整，没有指定作为进入条件的通信录'];
			}
		}
		if ($this->getDeepValue($oRule, 'scope.group') === 'Y') {
			if (empty($oRule->group->id)) {
				return [false, '进入规则不完整，没有指定作为进入条件的分组活动'];
			}
		}
		if ($this->getDeepValue($oRule, 'scope.enroll') === 'Y') {
			if (empty($oRule->enroll->id)) {
				return [false, '进入规则不完整，没有指定作为进入条件的记录活动'];
			}
		}

		return [true, $oCheckedRule];
	}
	/**
	 * 删除素材
	 */
	public function remove($oUser, $oMatter, $mode = 'Recycle') {
		/* 从团队中去除 */
		$modelSite = $this->model('site');
		$modelSite->removeMatter($oMatter);
		/* 从项目中去除 */
		if (!empty($oMatter->mission_id)) {
			$this->model('matter\mission')->removeMatter($oMatter->mission_id, $oMatter);
		}

		if ($mode === 'D') {
			$rst = $this->delete(
				$this->table(),
				["id" => $oMatter->id]
			);
			$this->model('matter\log')->matterOp($oMatter->siteid, $oUser, $oMatter, 'D');
		} else {
			$rst = $this->update(
				$this->table(),
				['state' => 0],
				["id" => $oMatter->id]
			);
			$this->model('matter\log')->matterOp($oMatter->siteid, $oUser, $oMatter, 'Recycle');
		}

		return $rst;
	}
	/**
	 * 恢复被删除的素材
	 */
	public function restore($oUser, $oMatter) {
		/* 恢复数据 */
		$rst = $this->update(
			$this->table(),
			['state' => 1],
			["id" => $oMatter->id]
		);

		/* 记录和项目的关系 */
		if (!empty($oMatter->mission_id)) {
			$modelMis = $this->model('matter\mission');
			$modelMis->addMatter($oUser, $oMatter->siteid, $oMatter->mission_id, $oMatter);
		}

		/* 记录和团队的关系 */
		$modelSite = $this->model('site');
		$modelSite->addMatter($oUser, $oMatter, $this->getTypeCategory());

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oMatter->siteid, $oUser, $oMatter, 'Restore');

		return new \ResponseData($rst);
	}
}