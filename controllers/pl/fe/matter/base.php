<?php
namespace pl\fe\matter;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 素材控制器基类
 */
class base extends \pl\fe\base {
	/**
	 * 获得素材的类型
	 */
	protected function getMatterType() {
		$cls = get_class($this);
		$cls = str_replace('pl\fe\matter\\', '', $cls);
		$cls = explode('\\', $cls);
		if (count($cls) === 2) {
			return $cls[0];
		}
		throw new \Exception();
	}
	/**
	 * 设置访问白名单
	 *
	 * @param int $id 规则ID
	 */
	public function setAcl_action($site, $id = null) {
		if (empty($id)) {
			die('parameters invalid.');
		}

		$acl = $this->getPostJson();
		if (isset($acl->id)) {
			$u['identity'] = $acl->identity;
			empty($acl->idsrc) && $u['label'] = $acl->identity;
			$rst = $this->model()->update('xxt_matter_acl', $u, "id=$acl->id");
			return new \ResponseData($rst);
		} else {
			$i['siteid'] = $site;
			$i['matter_type'] = $this->getMatterType();
			$i['matter_id'] = $id;
			$i['identity'] = $acl->identity;
			$i['idsrc'] = $acl->idsrc;
			$i['label'] = isset($acl->label) ? $acl->label : '';
			$i['id'] = $this->model()->insert('xxt_matter_acl', $i, true);

			return new \ResponseData($i);
		}
	}
	/**
	 * 删除访问控制列表
	 *
	 * @param int $acl 规则ID
	 */
	public function removeAcl_action($site, $acl) {
		$rst = $this->model()->delete(
			'xxt_matter_acl',
			"siteid='$site' and id=$acl"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 素材的阅读日志
	 */
	public function readGet_action($id, $page = 1, $size = 30) {
		$model = $this->model('log');

		$type = $this->getMatterType();

		$reads = $model->getMatterRead($type, $id, $page, $size);

		return new \ResponseData($reads);
	}
	/**
	 * 素材访问控制
	 */
	public function accessControlUser($matterType, $matterId) {
		if (false === ($oUser = $this->accountUser())) {
			return [false, '未登陆'];
		}

		$aOptions = ['cascaded' => 'N', 'fields' => 'siteid,id,title,mission_id'];
		$oMatter = $this->model('matter\\' . $matterType)->byId($matterId, $aOptions);
		if (!$oMatter) {
			return [false, '指定的素材不存在'];
		}

		$siteid = $oMatter->siteid;
		$modelSiteAdmin = $this->model('site\admin');
		$oSiteAdmin = $modelSiteAdmin->byUid($siteid, $oUser->id);
		if ($oSiteAdmin !== false) {
			return [true, $oUser];
		}

		/*检查此素材是否在项目中*/
		if ($matterType !== 'mission' && !empty($oMatter->mission_id)) {
			$mission_id = $oMatter->mission_id;
		} else if ($matterType === 'mission') {
			$mission_id = $matterId;
		}
		if (isset($mission_id)) {
			$oMissionUser = $this->model('matter\mission\acl')->byCoworker($mission_id, $oUser->id, ['fields' => 'id']);
			if ($oMissionUser) {
				return [true, $oUser];
			}
		}

		return [false, '访问控制未通过'];
	}
}