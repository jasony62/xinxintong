<?php
namespace pl\fe\site;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 团队投稿
 */
class contribute extends \pl\fe\base {
	/**
	 * 指定团队收到的投稿
	 *
	 * @param string $site site'id
	 */
	public function list_action($site, $page = 1, $size = 10) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$result = new \stdClass;

		$q = [
			'*',
			'xxt_site_contribute',
			['siteid' => $site],
		];
		$q2 = [
			'o' => 'create_at desc',
			'r' => ['o' => ($page - 1) * $size, $size, 'l' => $size],
		];
		$matters = $model->query_objs_ss($q, $q2);

		$result->matters = $matters;
		if (empty($matters)) {
			$result->total = 0;
		} else {
			$q[0] = 'count(*)';
			$result->total = $model->query_val_ss($q);
		}

		return new \ResponseData($result);
	}
	/**
	 * 进行投稿
	 *
	 * @param string $site 接收投稿的团队id
	 *
	 */
	public function do_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$aMatters = $this->getPostJson();
		if (empty($aMatters)) {
			return new \ResponseError('没有指定投稿的内容');
		}
		$modelSite = $this->model('site');
		$oSite = $modelSite->byId($site);
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}

		$current = time();
		$log = [
			'siteid' => $oSite->id,
			'creater' => $user->id,
			'creater_name' => $modelSite->escape($user->name),
			'create_at' => $current,
		];
		foreach ($aMatters as $oMatter) {
			$modelMat = $this->model('matter\\' . $oMatter->type);
			$oMatter = $modelMat->byId($oMatter->id, ['cascaded' => 'N']);
			if (false === $oMatter) {
				continue;
			}
			$q = [
				'id',
				'xxt_site_contribute',
				['siteid' => $oSite->id, 'matter_id' => $oMatter->id, 'matter_type' => $oMatter->type],
			];
			if (false === $modelMat->query_obj_ss($q)) {
				$log['from_siteid'] = $oMatter->siteid;
				$log['matter_id'] = $oMatter->id;
				$log['matter_type'] = $oMatter->type;
				$log['matter_title'] = $modelMat->escape($oMatter->title);
				$log['matter_summary'] = $modelMat->escape($oMatter->summary);
				$log['matter_pic'] = $oMatter->pic;
				$modelMat->insert('xxt_site_contribute', $log, false);
			}
		}

		return new \ResponseData('ok');
	}
}