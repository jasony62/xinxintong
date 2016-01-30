<?php
namespace mp;

require_once dirname(__FILE__) . "/mp_controller.php";
/**
 *
 */
class mpaccount extends mp_controller {
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		/**
		 * entries
		 */
		$entries = array();
		$pmodel = $this->model('mp\permission');
		$pmodel->can('mpsetting_setting', 'read') && $entries[] = array('name' => 'main', 'title' => '基本信息', 'entry' => '');
		$pmodel->can('mpsetting_feature', 'read') && $entries[] = array('name' => 'feature', 'title' => '定制功能', 'entry' => '');
		$pmodel->can('mpsetting_customapi', 'read') && $entries[] = array('name' => 'customapi', 'title' => '自定义接口', 'entry' => '');
		$entries[] = array('name' => 'coin', 'title' => '积分规则', 'entry' => '');
		$pmodel->can('mpsetting_administrator', 'read') && $entries[] = array('name' => 'administrator', 'title' => '系统管理员', 'entry' => '');
		$entries[] = array('name' => 'syslog', 'title' => '系统日志', 'entry' => '');

		\TPL::assign('mpsetting_view_entries', $entries);
	}
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';
		$rule_action['actions'][] = 'apis'; // todo ????

		return $rule_action;
	}
	/**
	 * 提供给公众平台进行对接的访问入口
	 */
	private function apiurl() {
		$url = 'http://';
		$url .= $_SERVER['HTTP_HOST'];
		$url .= '/rest/mi/api';
		return $url;
	}
	/**
	 * 获得当前用户对公众号的操作权限
	 */
	private function getUserPermissions() {
		$uid = \TMS_CLIENT::get_client_uid();

		$perms = $this->model('mp\permission')->hasMpRight(
			$this->mpid,
			array('mpsetting_setting', 'mpsetting_feature', 'mpsetting_customapi', 'mpsetting_permission', 'mpsetting_administrator'),
			array('create', 'read', 'update', 'delete'),
			$uid
		);

		return $perms;
	}
	/**
	 *
	 */
	public function index_action() {
		$modelMpa = $this->model('mp\mpaccount');
		$mpa = $modelMpa->byId($this->mpid);
		$creater = $this->model('account')->byId($mpa->creater);
		$mpa->creater_name = $creater->nickname;
		if ($mpa->asparent === 'N') {
			/**
			 * 实体账号
			 */
			$API_URL = $this->apiurl();
			$mpa->yx_url = "$API_URL?mpid=$this->mpid&src=yx";
			$mpa->wx_url = "$API_URL?mpid=$this->mpid&src=wx";
			$mpa->qy_url = "$API_URL?mpid=$this->mpid&src=qy";
			if (!empty($mpa->parent_mpid)) {
				/**
				 * 有父账号
				 */
				$pmp = $modelMpa->byId($mpa->parent_mpid, 'name');
				$mpa->parentname = $pmp->name;
			}
		}

		if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
			return new \ResponseData($mpa);
		} else {
			$perms = $this->getUserPermissions();
			$params = array(
				'mpaccount' => $mpa,
			);
			$apis = $modelMpa->getApis($this->mpid);
			isset($apis) && $params['apis'] = $apis;

			\TPL::assign('params', $params);

			if ($perms === true || $perms['mpsetting_setting']['update_p'] === 'Y') {
				$this->view_action('/mp/mpaccount/main');
			} else {
				$this->view_action('/mp/mpaccount/read/main');
			}
		}
	}
	/**
	 *
	 */
	public function get_action() {
		if (empty($this->mpid)) {
			return new \ResponseTimeout();
		}
		$modelMpa = $this->model('mp\mpaccount');
		$mpa = $modelMpa->byId($this->mpid);
		$creater = $this->model('account')->byId($mpa->creater);
		$mpa->creater_name = $creater->nickname;
		if ($mpa->asparent === 'N') {
			/**
			 * 实体账号
			 */
			$API_URL = $this->apiurl();
			$mpa->yx_url = "$API_URL?mpid=$this->mpid&src=yx";
			$mpa->wx_url = "$API_URL?mpid=$this->mpid&src=wx";
			$mpa->qy_url = "$API_URL?mpid=$this->mpid&src=qy";
			if (!empty($mpa->parent_mpid)) {
				/**
				 * 有父账号
				 */
				$pmp = $modelMpa->byId($mpa->parent_mpid, 'name');
				$mpa->parentname = $pmp->name;
			}
		}
		/**
		 * 环境变量
		 */
		$mpa->_env = new \stdClass;
		$mpa->_env->SAE = defined('SAE_TMP_PATH');

		return new \ResponseData($mpa);
	}
	/**
	 * 当前公众号的所有子公众号
	 */
	public function childmps_action() {
		$q = array(
			'mpid,name,mpsrc,create_at,yx_joined,wx_joined,qy_joined',
			'xxt_mpaccount a',
			"parent_mpid='$this->mpid' and state=1",
		);
		$q2 = array('o' => 'name');

		$mps = $this->model()->query_objs_ss($q, $q2);

		return new \ResponseData($mps);
	}
	/**
	 *
	 */
	public function feature_action() {
		$perms = $this->getUserPermissions();
		if ($perms === true || $perms['mpsetting_feature']['update_p'] === 'Y') {
			$this->view_action('/mp/mpaccount/feature');
		} else {
			$this->view_action('/mp/mpaccount/read/feature');
		}
	}
	/**
	 *
	 */
	public function customapi_action() {
		$perms = $this->getUserPermissions();

		if ($perms === true || $perms['mpsetting_customapi']['update_p'] === 'Y') {
			$this->view_action('/mp/mpaccount/customapi');
		} else {
			$this->view_action('/mp/mpaccount/read/customapi');
		}
	}
	/**
	 *
	 */
	public function permission_action() {
		$perms = $this->getUserPermissions();

		if ($perms === true || $perms['mpsetting_permission']['update_p'] == 'Y') {
			$this->view_action('/mp/mpaccount/permission');
		} else {
			$this->view_action('/mp/mpaccount/read/permission');
		}
	}
	/**
	 * 设置积分规则
	 */
	public function coin_action() {
		$this->view_action('/mp/mpaccount/coin');
	}
	/**
	 *
	 */
	public function syslog_action() {
		$this->view_action('/mp/mpaccount/syslog');
	}
	/**
	 *
	 */
	public function administrator_action() {
		$q = array(
			'a.uid,a.authed_id,a.email',
			'xxt_mpadministrator m, account a',
			"m.mpid='$this->mpid' and m.uid=a.uid",
		);
		$admins = $this->model()->query_objs_ss($q);
		foreach ($admins as &$a) {
			if (empty($a->authed_id)) {
				$a->authed_id = $a->email;
			}
		}

		$params['administrators'] = $admins;
		\TPL::assign('params', $params);

		$perms = $this->getUserPermissions();
		if ($perms === true || $perms['mpsetting_administrator']['update_p'] == 'Y') {
			$this->view_action('/mp/mpaccount/administrator');
		} else {
			$this->view_action('/mp/mpaccount/read/administrator');
		}
	}
	/**
	 * 更新账号配置信息
	 */
	public function update_action() {
		$nv = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_mpaccount',
			(array) $nv,
			"mpid='$this->mpid'"
		);
		/**
		 * 如果修改了token，需要重新重新进行验证
		 */
		if (isset($nv->token)) {
			$rst = $this->model()->update(
				'xxt_mpaccount',
				array('yx_joined' => 'N', 'wx_joined' => 'N', 'qy_joined' => 'N'),
				"mpid='$this->mpid'"
			);
		}

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function checkJoin_action() {
		$mpa = $this->model('mp\mpaccount')->byId($this->mpid);

		return new \ResponseData($mpa->{$mpa->mpsrc . '_joined'});
	}
	/**
	 * 获得当前用户的权限
	 */
	public function mypermissions_action() {
		$perms = $this->getUserPermission();

		return new \ResponseData($perms);
	}
	/**
	 * 获得高级接口定义
	 */
	public function apis_action() {
		$modelMpa = $this->model('mp\mpaccount');

		$apis = $modelMpa->getApis($this->mpid);

		return new \ResponseData($apis);
	}
	/**
	 *
	 */
	public function updateApi_action() {
		$nv = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_mpsetting',
			(array) $nv,
			"mpid='$this->mpid'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 设置的系统管理员
	 */
	public function admins_action() {
		$q = array(
			'a.uid,a.authed_id,a.email',
			'xxt_mpadministrator m, account a',
			"m.mpid='$this->mpid' and m.uid=a.uid",
		);
		$admins = $this->model()->query_objs_ss($q);
		foreach ($admins as &$a) {
			if (empty($a->authed_id)) {
				$a->authed_id = $a->email;
			}
		}

		return new \ResponseData($admins);
	}
	/**
	 * 添加系统管理员
	 */
	public function addAdmin_action($authedid = null, $authapp = '', $autoreg = 'N') {
		if (empty($authedid) && defined('TMS_APP_ADDON_EXTERNAL_ORG')) {
			return new \ResponseData(array('externalOrg' => TMS_APP_ADDON_EXTERNAL_ORG));
		}

		$model = $this->model('account');
		$account = $model->getAccountByAuthedId($authedid);

		if (!$account) {
			if ($autoreg !== 'Y') {
				return new \ResponseError('指定的账号不是注册账号，请先注册！');
			} else {
				$account = $model->authed_from($authedid, $authapp, '0.0.0.0', $authedid);
			}
		}
		/**
		 * exist?
		 */
		$q = array(
			'count(*)',
			'xxt_mpadministrator',
			"mpid='$this->mpid' and uid='$account->uid'",
		);
		if ((int) $this->model()->query_val_ss($q) > 0) {
			return new \ResponseError('该账号已经是系统管理员，不能重复添加！');
		}

		$uid = \TMS_CLIENT::get_client_uid();
		$this->model()->insert(
			'xxt_mpadministrator',
			array(
				'mpid' => $this->mpid,
				'uid' => $account->uid,
				'creater' => $uid,
				'create_at' => time(),
			),
			false
		);

		return new \ResponseData(array('uid' => $account->uid, 'authed_id' => $authedid));
	}
	/**
	 * 删除一个系统管理员
	 */
	public function removeAdmin_action($uid) {
		$rst = $this->model()->delete(
			'xxt_mpadministrator',
			"mpid='$this->mpid' and uid='$uid'"
		);
		return new \ResponseData($rst);
	}
	/**
	 * 生成当前公众号的父账号
	 *
	 * 1、生成一个新的父账号
	 * 2、将当前账号设置为父账号的子账号
	 * 3、将当前账号的素材迁移到父账号
	 * 4、回复数据迁移
	 * 5、活动数据迁移（不迁移）
	 * 6、访问控制列表数据要迁移吗？（不迁移）
	 */
	public function genParent_action() {
		$mpa = $this->model('mp\mpaccount')->byId($this->mpid);
		/**
		 * 1、生成一个新的父账号
		 */
		if (empty($mpa->parent_mpid)) {
			$d['name'] = $mpa->name . '（父账号）';
			$d['asparent'] = 'Y';

			$pmpid = $this->model('mp\mpaccount')->create($d);
			/**
			 * 2、将当前账号设置为父账号的子账号
			 */
			$rst = $this->model()->update(
				'xxt_mpaccount',
				array('parent_mpid' => $pmpid),
				"mpid='$this->mpid'"
			);
		} else {
			$pmpid = $mpa->parent_mpid;
		}
		/**
		 * 3、将当前账号的素材迁移到父账号
		 */
		$rst = $this->model()->update(
			'xxt_tag',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		$rst = $this->model()->update(
			'xxt_article',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		$rst = $this->model()->update(
			'xxt_article_tag',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		$rst = $this->model()->update(
			'xxt_text',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		$rst = $this->model()->update(
			'xxt_news',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		// xxt_news_matter
		$rst = $this->model()->update(
			'xxt_link',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		// xxt_link_param
		$rst = $this->model()->update(
			'xxt_channel',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		/**
		 * 通讯录迁移
		 */
		$rst = $this->model()->update(
			'xxt_addressbook',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		$rst = $this->model()->update(
			'xxt_ab_dept',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		$rst = $this->model()->update(
			'xxt_ab_person',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		$rst = $this->model()->update(
			'xxt_ab_person_dept',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		$rst = $this->model()->update(
			'xxt_ab_title',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		/**
		 * 回复响应事件迁移
		 */
		$rst = $this->model()->update(
			'xxt_call_text',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		$rst = $this->model()->update(
			'xxt_call_menu',
			array('mpid' => $pmpid, 'pversion' => -1),
			"mpid='$this->mpid'"
		);
		/**
		 * 活动数据迁移
		 */
		$rst = $this->model()->update(
			'xxt_enroll',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		$rst = $this->model()->update(
			'xxt_enroll_receiver',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		//
		$rst = $this->model()->update(
			'xxt_lottery',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		$rst = $this->model()->update(
			'xxt_lottery_award',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		$rst = $this->model()->update(
			'xxt_lottery_plate',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		$rst = $this->model()->update(
			'xxt_lottery_task',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);
		//
		$rst = $this->model()->update(
			'xxt_wall',
			array('mpid' => $pmpid),
			"mpid='$this->mpid'"
		);

		return new \ResponseData($pmpid);
	}
	/**
	 *
	 */
	public function removeMp_action($mpid, $code) {
		if ($code !== 'p0o9i8u7') {
			return new \ResponseError('failed');
		}

		$rst = $this->model()->update(
			'xxt_mpaccount',
			array('parent_mpid' => ''),
			"parent_mpid='$mpid'"
		);

		$this->model()->delete('xxt_mpaccount', "mpid='$mpid'");
		$this->model()->delete('xxt_mpsetting', "mpid='$mpid'");
		$this->model()->delete('xxt_mprelay', "mpid='$mpid'");
		$this->model()->delete('xxt_mpadministrator', "mpid='$mpid'");
		$this->model()->delete('xxt_mppermission', "mpid='$mpid'");

		$this->model()->delete('xxt_tag', "mpid='$mpid'");
		$this->model()->delete('xxt_article_remark', "article_id in (select id from xxt_article where mpid='$mpid')");
		$this->model()->delete('xxt_article_score', "article_id in (select id from xxt_article where mpid='$mpid')");
		$this->model()->delete('xxt_article', "mpid='$mpid'");
		$this->model()->delete('xxt_article_tag', "mpid='$mpid'");
		$this->model()->delete('xxt_text', "mpid='$mpid'");
		$this->model()->delete('xxt_news_matter', "news_id in (select id from xxt_news where mpid='$mpid')");
		$this->model()->delete('xxt_news', "mpid='$mpid'");
		$this->model()->delete('xxt_link_param', "link_id in (select id from xxt_link where mpid='$mpid')");
		$this->model()->delete('xxt_link', "mpid='$mpid'");
		$this->model()->delete('xxt_channel_matter', "channel_id in (select id from xxt_channel where mpid='$mpid')");
		$this->model()->delete('xxt_channel', "mpid='$mpid'");
		$this->model()->delete('xxt_addressbook', "mpid='$mpid'");
		$this->model()->delete('xxt_ab_dept', "mpid='$mpid'");
		$this->model()->delete('xxt_ab_person', "mpid='$mpid'");
		$this->model()->delete('xxt_ab_person_dept', "mpid='$mpid'");
		$this->model()->delete('xxt_ab_title', "mpid='$mpid'");
		$this->model()->delete('xxt_tmplmsg_param', "tmplmsg_id in (select id from xxt_tmplmsg where mpid='$mpid')");
		$this->model()->delete('xxt_tmplmsg', "mpid='$mpid'");

		$this->model()->delete('xxt_call_text', "mpid='$mpid'");
		$this->model()->delete('xxt_call_qrcode', "mpid='$mpid'");
		$this->model()->delete('xxt_call_other', "mpid='$mpid'");
		$this->model()->delete('xxt_call_menu', "mpid='$mpid'");
		$this->model()->delete('xxt_call_acl', "mpid='$mpid'");

		$this->model()->delete('xxt_enroll_lottery_round', "aid in (select aid from xxt_enroll where mpid='$mpid')");
		$this->model()->delete('xxt_enroll_lottery', "aid in (select aid from xxt_enroll where mpid='$mpid')");
		$this->model()->delete('xxt_enroll', "mpid='$mpid'");
		$this->model()->delete('xxt_enroll_receiver', "mpid='$mpid'");
		$this->model()->delete('xxt_enroll_round', "mpid='$mpid'");
		$this->model()->delete('xxt_enroll_page', "mpid='$mpid'");
		$this->model()->delete('xxt_enroll_record_data', "enroll_key in (select enroll_key from xxt_enroll_record where mpid='$mpid')");
		$this->model()->delete('xxt_enroll_record_remark', "enroll_key in (select enroll_key from xxt_enroll_record where mpid='$mpid')");
		$this->model()->delete('xxt_enroll_record_score', "enroll_key in (select enroll_key from xxt_enroll_record where mpid='$mpid')");
		$this->model()->delete('xxt_enroll_record', "mpid='$mpid'");

		$this->model()->delete('xxt_lottery_task_log', "lid in (select lid from xxt_lottery where mpid='$mpid')");
		$this->model()->delete('xxt_lottery_task', "lid in (select lid from xxt_lottery where mpid='$mpid')");
		$this->model()->delete('xxt_lottery', "mpid='$mpid'");
		$this->model()->delete('xxt_lottery_award', "mpid='$mpid'");
		$this->model()->delete('xxt_lottery_plate', "mpid='$mpid'");
		$this->model()->delete('xxt_lottery_log', "mpid='$mpid'");

		$this->model()->delete('xxt_wall', "mpid='$mpid'");
		$this->model()->delete('xxt_wall_enroll', "mpid='$mpid'");
		$this->model()->delete('xxt_wall_log', "mpid='$mpid'");

		$this->model()->delete('xxt_matter_acl', "mpid='$mpid'");

		$this->model()->delete('xxt_log', "mpid='$mpid'");
		$this->model()->delete('xxt_log_mpreceive', "mpid='$mpid'");
		$this->model()->delete('xxt_log_mpsend', "mpid='$mpid'");
		$this->model()->delete('xxt_log_matter_read', "mpid='$mpid'");
		$this->model()->delete('xxt_log_matter_share', "mpid='$mpid'");
		$this->model()->delete('xxt_log_tmplmsg', "tmplmsg_id in (select id from xxt_tmplmsg where mpid='$mpid')");
		$this->model()->delete('xxt_log_user_action', "mpid='$mpid'");
		$this->model()->delete('xxt_log_matter_action', "mpid='$mpid'");

		$this->model()->delete('xxt_visitor', "mpid='$mpid'");
		$this->model()->delete('xxt_fans', "mpid='$mpid'");
		$this->model()->delete('xxt_fansgroup', "mpid='$mpid'");
		$this->model()->delete('xxt_member', "mpid='$mpid'");
		$this->model()->delete('xxt_member_authapi', "mpid='$mpid'");
		$this->model()->delete('xxt_member_card', "mpid='$mpid'");
		$this->model()->delete('xxt_member_tag', "mpid='$mpid'");
		//$this->model()->delete('xxt_access_token', "mpid='$mpid'");

		return new \ResponseData($mpid);
	}
}