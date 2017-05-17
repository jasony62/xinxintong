<?php
namespace pl\fe\site\user;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 站点用户管理控制器
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/user');
		exit;
	}
	/**
	 *
	 */
	public function member_action() {
		\TPL::output('/pl/fe/site/user');
		exit;
	}
	/**
	 * 用户访问详情列表
	 */
	public function readList_action($site = null, $uid, $page = 1, $size = 12) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$q = [
			'*',
			'xxt_log_matter_read',
			['userid' => $uid]
		];
		!empty($site) && $q[2]['siteid'] = $site;
		$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		$q2['o'] = ['read_at desc'];

		$matters = $model->query_objs_ss($q, $q2);

		$result = new \stdClass;
		$result->matters = $matters;
		if (empty($matters)) {
			$result->total = 0;
		} else {
			$q[0] = 'count(*)';
			$result->total = $model->query_val_ss($q);
		}

		return new \responseData($result);
	}
	/**
	 * 获得指定用户在指定站点参与的活动
	 *
	 * @param string $site site'id
	 * @param string $uid
	 */
	public function actList_action($site = null, $uid, $page = 1, $size = 12) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelLog = $this->model('matter\log');
		$q = [
			'matter_id,matter_type,matter_title,operate_at',
			'xxt_log_user_matter',
			"userid='" . $uid . "' and user_last_op='Y' and operation='submit' and matter_type in ('enroll','signin')",
		];
		!empty($site) && $q[2] .= " and siteid = '". $modelLog->escape($site) ."'";
		$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		$q2['o'] = ['operate_at desc'];

		$logs = $modelLog->query_objs_ss($q, $q2);
		$result = new \stdClass;
		$result->apps = $logs;
		if (empty($logs)) {
			$result->total = 0;
		} else {
			$q[0] = 'count(*)';
			$result->total = $modelLog->query_val_ss($q);
		}

		return new \ResponseData($result);
	}
	/**
	 * 返回指定用户收藏的素材,增加了素材的标题、头图、摘要
	 *
	 */
	public function favList_action($site = null, $unionid = '', $page = 1, $size = 10) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$q = array(
			'id,favor_at,matter_id,matter_type,matter_title',
			'xxt_site_favor',
			['unionid' => $unionid]
		);
		!empty($site) && $q[2]['siteid'] = $site;
		$q2 = array(
			'o' => 'favor_at desc',
			'r' => array('o' => ($page - 1) * $size, $size, 'l' => $size),
		);

		$matters = $model->query_objs_ss($q, $q2);

		foreach ($matters as $k => $v) {
			if ($v->matter_type == 'custom') {
				$type = 'article';
			} else {
				$type = $v->matter_type;
			}
			$d = $this->model()->query_obj_ss(['id,title,summary,pic', 'xxt_' . $type, "siteid='$site' and id='$v->matter_id'"]);
			$v->data = $d;
			$b[$k] = $v;
		}
		if (isset($b)) {
			$matters = (object) $b;
		}
		$result = new \stdClass;
		$result->matters = $matters;
		if (empty($matters)) {
			$result->total = 0;
		} else {
			$q[0] = 'count(*)';
			$result->total = $model->query_val_ss($q);
		}

		return new \ResponseData($result);
	}
}