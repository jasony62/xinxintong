<?php
namespace pl\fe\matter;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 素材控制器基类
 */
class base extends \pl\fe\base {
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
	public function passWallUser($path) {
		// $modelWay = \TMS_APP::M('site\fe\way');
		// if (($user = $modelWay->getCookieRegUser()) === false) {
		// 	return new \ResponseTimeout();
		// }
		
		var_dump(explode('/', strstr($path, 'matter'))[1], $_GET);
		

		$pass = false;
		// $modelSite = $this->model('site\admin');
		// $site = $modelSite->escape($site);
		// if ($siteUser = $modelSite->byUid($site, $user->unionid)) {
		// 	$pass = true;
		// }

		// /*检查素材是否属于项目*/
		// if($pass === false && !empty($matter->mission_id)){
		// 	$q = [
		// 		'id',
		// 		'xxt_mission_acl',
		// 		['mission_id' => $matter->mission_id, 'coworker' => $user->unionid, 'state' => 1],
		// 	];
		// 	if($missionUser = $modelSite->query_obj_ss($q)){
		// 		$pass = true;
		// 	}
		// }

		return $pass;
	}
}