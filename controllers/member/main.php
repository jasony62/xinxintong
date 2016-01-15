<?php
namespace member;

require_once dirname(dirname(__FILE__)) . '/member_base.php';
/**
 * member
 */
class main extends \member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 获得当前用户的信息
	 *
	 * $mpid 必须是属于一个公众号的用户
	 * $authid 必须指定用户是通过那个接口进行的身份认证
	 *
	 */
	public function index_action($mpid, $authid) {
		$this->getVisitorId($mpid);

		$aAuthapis[] = $authid;

		$members = $this->authenticate($mpid, $aAuthapis);
		$mid = $members[0]->mid;

		$member = $this->model('user/member')->byId($mid);
		return new \ResponseData($member);
	}
	/**
	 * 进入选择认证接口页
	 *
	 * 如果被访问的页面支持多个认证接口，要求用户选择一种认证接口
	 */
	public function authoptions_action($mpid, $authids, $openid = null) {
		$params = "mpid=$mpid";
		if (!empty($openid)) {
			$params .= "&openid=$openid";
		}

		$aAuthapis = array();
		$aAuthids = explode(',', $authids);
		foreach ($aAuthids as $authid) {
			$authapi = $this->model('user/authapi')->byId($authid, 'name,url');
			$authapi->url .= "?authid=$authid&$params";
			$aAuthapis[] = $authapi;
		}

		\TPL::assign('authapis', $aAuthapis);

		$this->view_action('/member/authoptions');
	}
	/**
	 * 获得定义的认证接口
	 *
	 * 返回当前公众号和它的父账号的
	 *
	 * $valid
	 */
	public function authapis_action($mpid = null) {
		empty($mpid) && $mpid = $_SESSION['mpid'];

		empty($mpid) && die('can not get mpid.');

		$modelMpa = $this->model('mp\mpaccount');
		$modelAuth = $this->model('user/authapi');

		$pmp = $modelMpa->byId($mpid, 'parent_mpid');
		if (!empty($pmp->parent_mpid)) {
			$papis = $modelAuth->byMpid($pmp->parent_mpid);
		}

		$apis = $modelAuth->byMpid($mpid);

		!empty($papis) && $apis = array_merge($papis, $apis);

		return new \ResponseData($apis);
	}
	/**
	 * 获得指定父节点下的部门
	 *
	 * $authid
	 * $pid
	 */
	public function departments_action($authid, $pid = 0) {
		empty($mpid) && $mpid = $_SESSION['mpid'];

		empty($mpid) && die('can not get mpid.');

		$depts = $this->model('user/department')->byMpid($mpid, $authid, $pid);

		return new \ResponseData($depts);
	}
	/**
	 * 获得所有标签
	 *
	 * $authid 每个认证接口下可以定义标签
	 *
	 * todo 如何排序？
	 */
	public function tags_action($authid) {
		empty($mpid) && $mpid = $_SESSION['mpid'];

		empty($mpid) && die('can not get mpid.');

		$tags = $this->model('user/tag')->byMpid($mpid, $authid);

		return new \ResponseData($tags);
	}
	/**
	 * all members.
	 *
	 * $mpid
	 * $authid
	 * $dept
	 * $tag
	 *
	 * return member list|total|itemsSetting
	 */
	public function members_action($authid, $page = 1, $size = 30, $kw = null, $by = null, $dept = null, $tag = null, $contain = '') {
		$contain = explode(',', $contain);

		$w = "m.authapi_id=$authid and m.forbidden='N'";

		if (!empty($kw) && !empty($by)) {
			$w .= " and m.$by like '%$kw%'";
		}

		if (!empty($dept)) {
			$w .= " and m.depts like '%\"$dept\"%'";
		}

		if (!empty($tag)) {
			$w .= " and concat(',',m.tags,',') like '%,$tag,%'";
		}

		$q = array(
			'm.*',
			'xxt_member m',
			$w,
		);
		$q2['o'] = 'm.create_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($members = $this->model()->query_objs_ss($q, $q2)) {
			$result['members'] = $members;
			if (in_array('total', $contain)) {
				$q[0] = 'count(*)';
				$total = (int) $this->model()->query_val_ss($q);
				$result['total'] = $total;
			}
			if (in_array('memberAttrs', $contain)) {
				/**
				 * 0-5 注册用户的基本信息
				 */
				$setting = $this->model('user/authapi')->byId($authid, 'attr_mobile,attr_email,attr_name,extattr');
				/**
				 * 返回属性设置信息
				 */
				$result['setting'] = $setting;
			}
			return new \ResponseData($result);
		}

		return new \ResponseData(array());
	}
}