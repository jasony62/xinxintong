<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动用户
 */
class user extends base {
	/**
	 * 返回当前用户任务完成的情况
	 */
	public function task_action($app) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->who;
		$options = [];
		if ($oActiveRound = $this->model('matter\enroll\round')->getActive($oApp)) {
			$options['rid'] = $oActiveRound->rid;
		}
		$oEnrollee = $this->model('matter\enroll\user')->byId($oApp, $oUser->uid, $options);

		return new \ResponseData($oEnrollee);
	}
	/**
	 *
	 * 列出填写人名单列表
	 *
	 */
	public function list_action($site, $app, $owner = 'U', $schema_id, $page = 1, $size = 30) {
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->who;
		//参与者列表
		$modelRnd = $this->model('matter\enroll\round');
		$rnd = $modelRnd->getActive($oApp);
		$rid = !empty($rnd) ? $rnd->rid : '';
		//全部或分组
		switch ($owner) {
		case 'G':
			$modelUsr = $this->model('matter\enroll\user');
			$options = ['fields' => 'group_id'];
			$oEnrollee = $modelUsr->byId($oApp, $oUser->uid, $options);
			$group_id = isset($oEnrollee->group_id) ? $oEnrollee->group_id : '';
			break;
		default:
			break;
		}
		//设定范围
		$entry = $oApp->entry_rule->scope;
		$q1 = [
			'*',
			'xxt_enroll_user',
			['siteid' => $site, 'aid' => $app, 'rid' => $rid],
		];
		isset($group_id) && $q1[2]['group_id'] = $group_id;
		$q2['o'] = "id asc";
		$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		$users = $modelEnl->query_objs_ss($q1, $q2);
		foreach ($users as &$user) {
			//添加分组信息
			$dataSchemas = $oApp->dataSchemas;
			foreach ($dataSchemas as $value) {
				if ($value->id == '_round_id') {
					$ops = $value->ops;
				}
			}
			if (isset($ops) && $user->group_id) {
				foreach ($ops as $p) {
					if ($user->group_id == $p->v) {
						$user->group = $p;
					}
				}
			}
			switch ($entry) {
			case 'member':
				//通信录的信息
				if (empty($schema_id)) {
					return new \ResponseError('传入的通信录ID参数不能为空！');
				}

				$addressbook = $modelEnl->query_obj_ss([
					'*',
					'xxt_site_member',
					['siteid' => $site, 'userid' => $user->userid, 'schema_id' => $schema_id],
				]);

				if ($addressbook) {
					if (isset($schema_id)) {
						$schema = $modelEnl->query_obj_ss(['id,title', 'xxt_site_member_schema', ['id' => $schema_id]]);
					}
					$extattr = json_decode($addressbook->extattr);
					$addressbook->schema_title = $schema->title;
					$addressbook->enroll_num = $user->enroll_num;
					$addressbook->remark_other_num = $user->remark_other_num;
					$addressbook->like_other_num = $user->like_other_num;
				}
				$user->mschema = $addressbook;
				break;
			case 'sns':
				//公众号的信息
				$sns = $modelEnl->query_obj_ss([
					'assoc_id,wx_openid,yx_openid,qy_openid,uname,headimgurl,ufrom,uid,unionid,nickname',
					'xxt_site_account',
					['siteid' => $site, 'uid' => $user->userid],
				]);
				$user->sns = $sns;
				break;
			default:
				# code...
				break;
			}
		}

		$result = new \stdClass;
		$result->records = $users;
		$q1[0] = 'count(*)';
		$result->total = $modelEnl->query_val_ss($q1);

		return new \ResponseData($result);
	}
}