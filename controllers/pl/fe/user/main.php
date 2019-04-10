<?php
namespace pl\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台用户管理
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'get';

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/user/frame');
		exit;
	}
	/**
	 *
	 */
	public function member_action() {
		\TPL::output('/pl/fe/user');
		exit;
	}
	/**
	 * 用户访问详情列表
	 */
	public function readList_action($site, $uid, $startAt = '', $endAt = '', $page = 1, $size = 12) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelLog = $this->model('matter\log');
		$options = [];
		$options['byUserId'] = $uid;
		$options['groupby'] = 'r.matter_type,r.matter_id';
		$options['shareby'] = 'N';
		$options['orderby'] = 'readAt desc';

		if (!empty($page) && !empty($size)) {
			$options['paging'] = ['page' => $page, 'size' => $size];
		}
		if (!empty($startAt)) {
			$options['start'] = $startAt;
		}
		if (!empty($endAt)) {
			$options['end'] = $endAt;
		}

		$data = $modelLog->operateStat($site, '', '', $options);
		if ($data->total > 0) {
			foreach ($data->logs as $log) {
				$app = $this->model('matter\\' . $log->matter_type)->byId($log->matter_id, ['fields' => 'id,title', 'cascaded' => 'N']);
				if ($app) {
					$log->matter_title = $app->title;
				}
			}
		}

		return new \ResponseData($data);
	}
	/**
	 *
	 */
	public function userDetailLogs_action($matterId, $matterType, $page = 1, $size = 30) {
		$modelLog = $this->model('matter\log');

		$filter = $this->getPostJson();
		$options = [];
		if (empty($filter->byUserId)) {
			return new \ResponseError('未指定用户');
		}
		if (empty($filter->byOp)) {
			return new \ResponseError('未指定用户行为');
		}
		$options['byUserId'] = $modelLog->escape($filter->byUserId);
		$options['byOp'] = $modelLog->escape($filter->byOp);

		if (!empty($filter->start)) {
			$options['start'] = $modelLog->escape($filter->start);
		}
		if (!empty($filter->end)) {
			$options['end'] = $modelLog->escape($filter->end);
		}

		$logs = $modelLog->userMatterAction($matterId, $matterType, $options, $page, $size);

		return new \ResponseData($logs);
	}
	/**
	 * 获得指定用户在指定站点参与的活动
	 *
	 * @param string $site site'id
	 * @param string $uid
	 */
	public function appList_action($site = null, $uid, $page = 1, $size = 12) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$modelLog = $this->model('matter\log');
		$q = [
			'matter_id,matter_type,matter_title,operate_at',
			'xxt_log_user_matter',
			"userid='" . $uid . "' and user_last_op='Y' and operation='submit' and matter_type in ('enroll','signin')",
		];
		!empty($site) && $q[2] .= " and siteid = '" . $modelLog->escape($site) . "'";
		$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		$q2['o'] = ['operate_at desc'];

		$logs = $modelLog->query_objs_ss($q, $q2);
		$result = new \stdClass;
		$result->apps = $logs;
		if (empty($logs)) {
			$result->total = 0;
		} else {
			$oUser = (object) ['uid' => $uid];
			foreach ($logs as $log) {
				switch ($log->matter_type) {
				case 'enroll':
					if (!isset($modelEnlUsr)) {
						$modelEnlUsr = $this->model('matter\enroll\user');
					}
					$oApp = (object) ['id' => $log->matter_id];
					$log->act = $modelEnlUsr->reportByUser($oApp, $oUser);
					break;
				case 'signin':
					if (!isset($modelSig)) {
						$modelSig = $this->model('matter\signin');
					}
					$oApp = (object) ['id' => $log->matter_id];
					$log->act = $modelSig->reportByUser($oApp, $oUser);
					break;
				}
			}
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
			['unionid' => $unionid],
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
	/**
	 * 获得当前用户信息
	 */
	public function get_action() {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseData(false);
		}

		$oAccount = $this->model('account')->byId($loginUser->id, ['fields' => 'email,nickname']);

		return new \ResponseData($oAccount);
	}
	/**
	 * 获得当前用户信息
	 */
	public function getGroup_action() {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseData(false);
		}

		$group = $this->model('account')->getGroupByUser($loginUser->id);

		return new \ResponseData($group);
	}
	/**
	 * 修改当前用户的口令
	 */
	public function changePwd_action() {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$data = $this->getPostJson();
		$modelAcnt = $this->model('account');
		$account = $modelAcnt->byId($loginUser->id);
		/**
		 * check old password
		 */
		$old_pwd = $data->opwd;
		$result = $modelAcnt->validate($account->email, $old_pwd);
		if ($result->err_code != 0) {
			return $result;
		}
		/**
		 * set new password
		 */
		$new_pwd = $data->npwd;
		$modelAcnt->change_password($account->email, $new_pwd, $account->salt);

		return new \ResponseData($account->uid);
	}
	/**
	 * 修改当前用户的昵称
	 */
	public function changeNickname_action() {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$data = $this->getPostJson();
		$modelWay = $this->model('site\fe\way');

		$rst = $modelWay->update(
			'account',
			['nickname' => $modelWay->escape($data->nickname)],
			['uid' => $loginUser->id]
		);

		$cookieRegUser = $modelWay->getCookieRegUser();
		if ($cookieRegUser) {
			$cookieRegUser->nickname = $data->nickname;
			$modelWay->setCookieRegUser($cookieRegUser);
		}

		return new \ResponseData($rst);
	}
}