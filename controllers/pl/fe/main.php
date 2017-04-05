<?php
namespace pl\fe;

require_once dirname(__FILE__) . '/base.php';
/**
 * 登录用户的入口页面
 */
class main extends \pl\fe\base {
	/**
	 * 用户个人工作台
	 */
	public function index_action($ver = null) {
		if ($ver === '2') {
			\TPL::output('/pl/fe/main2');
			exit;
		} else if ($ver === '1') {
			$this->view_action('/pl/fe/main');
		} else {
			\TPL::output('/pl/fe/console/frame');
			exit;
		}
	}
	/**
	 * 列出站点最近操作的素材
	 */
	public function recent_action($page = 1, $size = 30, $matterType = null, $scenario = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelLog = $this->model('matter\log');

		// 分页参数
		$p = new \stdClass;
		$p->at = $page;
		$p->size = $size;

		$options = [
			'page' => $p,
		];
		// 类型参数
		!empty($matterType) && $options['matterType'] = $matterType;
		// 活动场景
		$scenario !== null && $options['scenario'] = $scenario;

		$matters = $modelLog->recentMattersByUser($user, $options);

		return new \ResponseData($matters);
	}
	/**
	 * 当前用户可见的所有公众号
	 */
	public function mpaccounts_action($pmpid = null, $asparent = 'N') {
		/**
		 * 当前用户是公众号的创建人或者被授权人
		 */
		$uid = \TMS_CLIENT::get_client_uid();

		$w = "a.asparent='$asparent' and a.state=1 and (a.creater='$uid'
            or exists(
                select 1
                from xxt_mpadministrator ma
                where a.mpid=ma.mpid and ma.uid='$uid'
            )
            or exists(
                select 1
                from xxt_mppermission p
                where a.mpid=p.mpid and p.uid='$uid'
            ))" . (empty($pmpid) ? '' : " and parent_mpid='$pmpid'");
		$q = array(
			'parent_mpid,mpid,asparent,name,create_at,yx_joined,wx_joined,qy_joined',
			'xxt_mpaccount a',
			$w,
		);
		$q2 = array('o' => 'create_at desc');

		$mps = $this->model()->query_objs_ss($q, $q2);

		return new \ResponseData($mps);
	}
	/**
	 * create an mp account basic information.
	 *
	 * $name
	 * $pmpid parent mp id.
	 * $asparent
	 */
	public function createmp_action($name = '新公众账号', $pmpid = '', $asparent = 'N') {
		if ($asparent === 'Y') {
			$d['token'] = uniqid();
		}

		$d['name'] = $name;
		$d['asparent'] = $asparent;
		$d['parent_mpid'] = $pmpid;
		$mpid = $this->model('mp\mpaccount')->create($d);

		return new \ResponseData($mpid);
	}
	/**
	 * 删除公众号
	 *
	 * 不删除数据，只是打标记
	 *
	 * 1、如果是子公众号，在已经开通的情况下不允许删除
	 * 2、如果是父公众号，在子账号已经开通的情况下不允许删除，否则将账号群及其下的子账号群一并删除
	 */
	public function removemp_action($mpid) {
		$act = $this->model('mp\mpaccount')->byId($mpid);
		if ($act->asparent === 'N') {
			if ($act->yx_joined === 'Y' || $act->wx_joined === 'Y') {
				return new \ResponseError('公众号已经开通，不允许删除！');
			}

		} else {
			$q = array(
				'count(*)',
				'xxt_mpaccount',
				"parent_mpid='$mpid' and (yx_joined='Y' or wx_joined='Y')",
			);
			if ((int) $this->model()->query_val_ss($q) > 0) {
				return new \ResponseError('公众号群下的子公众号已经开通，不允许删除！');
			}

		}
		/**
		 * 做标记
		 */
		$rst = $this->model()->update(
			'xxt_mpaccount',
			array('state' => 0),
			"mpid='$mpid' or parent_mpid='$mpid'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 个人工作台素材置顶
	 *
	 * @param int $id 是日志记录的ID
	 */
	public function top_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		//检查管理员的权限
		$model = $this->model('matter\log');
		$data = $model->query_val_ss([
			'urole',
			'xxt_site_admin',
			['siteid' => $site, 'uid' => $user->id],
		]);

		if (empty($data)) {
			return new \ResponseError('当前管理员没有该素材的操作权限！');
		}

		$one = $model->query_obj_ss([
			'matter_id,matter_type,matter_title',
			'xxt_log_matter_op',
			['siteid' => $site, 'id' => $id],
		]);

		if (empty($one)) {
			return new \ResponseError('找不到素材的操作记录！');
		}
		//获取当前用户的置顶素材
		$matter = $model->query_obj_ss([
			'id tid,matter_id id,matter_type type,matter_title title',
			'xxt_account_topmatter',
			['siteid' => $site, 'userid' => $user->id, 'matter_id' => $one->matter_id, 'matter_type' => $one->matter_type],
		]);

		if ($matter) {
			return new \ResponseError('已置顶！', 101);
		} else {
			$d['siteid'] = $site;
			$d['userid'] = $user->id;
			$d['top'] = '1';
			$d['top_at'] = time();
			$d['matter_id'] = $one->matter_id;
			$d['matter_type'] = $one->matter_type;
			$d['matter_title'] = $one->matter_title;

			$rst = $model->insert('xxt_account_topmatter', $d);
		}

		return new \ResponseData($rst);
	}
	/**
	 * 置顶列表
	 */
	public function topList_action($page = 1, $size = 12) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$p = [
			't.*,l.matter_summary,l.matter_pic,l.matter_scenario',
			'xxt_account_topmatter t,xxt_log_matter_op l',
			"t.siteid=l.siteid and t.matter_id=l.matter_id and t.matter_type=l.matter_type and l.user_last_op='Y' and t.userid='$user->id'",
		];
		$p2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		$p2['o'] = ['top_at desc'];

		$matters = $model->query_objs_ss($p, $p2);

		$result = new \stdClass;
		$result->matters = $matters;

		if (empty($matters)) {
			$result->total = 0;
		} else {
			$p[0] = 'count(*)';
			$result->total = $model->query_val_ss($p);
		}

		return new \ResponseData($result);
	}
	/**
	 * 删除置顶
	 */
	public function delTop_action($site, $id, $type) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model()->delete(
			'xxt_account_topmatter',
			"siteid='$site' and userid='$user->id' and matter_id='$id' and matter_type='$type'"
		);

		return new \ResponseData($rst);
	}
}