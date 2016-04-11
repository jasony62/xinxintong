<?php
namespace pl\fe;

class main extends \TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 * 用户登录后的首页
	 */
	public function index_action($ver = null) {
		if ($ver === '1') {
			$this->view_action('/pl/fe/main');
		} else {
			\TPL::output('/pl/fe/main2');
			exit;
		}
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
}