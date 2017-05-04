<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户收藏
 */
class favor extends \site\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/favor/main');
		exit;
	}
	/**
	 * 返回当前用户收藏的素材,增加了素材的标题、头图、摘要
	 *
	 */
	public function list_action($page = 1, $size = 10) {
		if (!isset($this->who->unionid)) {
			return new \ResponseError('仅限登录用户操作');
		}
		$model = $this->model();
		$loginUserid = $this->who->unionid;
		$q = [
			'id,favor_at,matter_id,matter_type,matter_title',
			'xxt_site_favor',
			['unionid' => $loginUserid],
		];
		// 指定团队下的访问记录
		if (!empty($this->siteId) && $this->siteId !== 'platform') {
			$q[2]['siteid'] = $this->siteId;
		}
		$q2 = [
			'o' => 'favor_at desc',
			'r' => ['o' => ($page - 1) * $size, $size, 'l' => $size],
		];
		$logs = $model->query_objs_ss($q, $q2);

		$result = new \stdClass;
		if (count($logs)) {
			foreach ($logs as $log) {
				$matter = $this->model('matter\\' . $log->matter_type)->byId($log->matter_id, ['fields' => 'siteid,title,summary,pic']);
				$log->data = $matter;
				$result->matters[] = $log;
			}
			$q[0] = 'count(*)';
			$result->total = $model->query_val_ss($q);
		} else {
			$result->matters = [];
			$result->total = 0;
		}

		return new \ResponseData($result);
	}
	/**
	 * 加入收藏
	 */
	public function add_action($id, $type) {
		$modelMat = $this->model('matter\\' . $type);
		$oMatter = $modelMat->byId($id, ['cascaded' => 'N']);
		if (false === $oMatter) {
			return new \ObjectNotFoundError();
		}
		if (!isset($this->who->unionid)) {
			return new \ResponseError('仅限登录用户操作');
		}

		$loginUserid = $this->who->unionid;

		$q = [
			'id',
			'xxt_site_favor',
			['siteid' => $oMatter->siteid, 'unionid' => $loginUserid, 'matter_id' => $id, 'matter_type' => $type],
		];
		if (false === $modelMat->query_obj_ss($q)) {
			$log = [
				'siteid' => $oMatter->siteid,
				'unionid' => $loginUserid,
				'nickname' => empty($this->who->nickname) ? '' : $modelMat->escape($this->who->nickname),
				'favor_at' => time(),
				'matter_id' => $oMatter->id,
				'matter_type' => $oMatter->type,
				'matter_title' => $modelMat->escape($oMatter->title),
			];

			$id = $modelMat->insert('xxt_site_favor', $log, true);

			return new \ResponseData($id);
		} else {
			return new \ResponseError('已经收藏过', 101);
		}
	}
	/**
	 * 检查用户是否收藏了指定素材
	 */
	public function byUser_action($id, $type) {
		if (!isset($this->who->unionid)) {
			return new \ResponseError('仅限登录用户操作');
		}

		$model = $this->model();
		$loginUserid = $this->who->unionid;
		$q = [
			'id,favor_at',
			'xxt_site_favor',
			['siteid' => $this->siteId, 'unionid' => $loginUserid, 'matter_id' => $id, 'matter_type' => $type],
		];
		$log = $model->query_obj_ss($q);

		return new \ResponseData($log);
	}
	/**
	 * 取消收藏
	 */
	public function remove_action($id, $type) {
		if (!isset($this->who->unionid)) {
			return new \ResponseError('仅限登录用户操作');
		}
		$loginUserid = $this->who->unionid;

		$rst = $this->model()->delete(
			'xxt_site_favor',
			['siteid' => $this->siteId, 'unionid' => $loginUserid, 'matter_id' => $id, 'matter_type' => $type]
		);

		return new \ResponseData($rst);
	}
}