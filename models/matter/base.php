<?php
namespace matter;
/**
 * 素材基类
 */
class base_model extends \TMS_MODEL {
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
			'contribute' => 'doc',
			'enroll' => 'app',
			'signin' => 'app',
			'group' => 'app',
			'lottery' => 'app',
			'wall' => 'app',
		];

		return isset($map[$matterType]) ? $map[$matterType] : '';
	}
	/**
	 * 根据类型和ID获得素材
	 */
	public function getCardInfoById($type, $id) {
		switch ($type) {
		case 'joinwall':
			$q = ['id,title,summary,pic', 'xxt_wall', ["id" => $id]];
			break;
		default:
			$table = 'xxt_' . $type;
			$q = ['id,title,summary,pic', $table, ["id" => $id]];
		}
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
		case 'joinwall':
			$q = ['id,title', 'xxt_wall', ["id" => $id]];
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
			["id" => $id],
		];
		if ($matter = $this->query_obj_ss($q)) {
			$matter->type = $this->getTypeName();
		}

		return $matter;
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
		$oNewMatter->creater_src = $oNewMatter->modifier_src = 'A';
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
	public function modify($oUser, &$oMatter, $oUpdated) {
		$current = time();

		/* 记录修改日志 */
		$oUpdated->modifier = $oUser->id;
		$oUpdated->modifier_src = $oUser->src;
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
	 * 删除素材
	 */
	public function remove($oUser, $oMatter, $mode = 'Recycle') {
		/* 从团队中去除 */
		$modelSite = $this->model('site');
		$modelSite->removeMatter($oMatter);
		/* 从项目中去除 */
		if (!empty($oMatter->mission_id)) {
			$this->model('matter\mission')->removeMatter($oMatter->id, $this->getTypeName());
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