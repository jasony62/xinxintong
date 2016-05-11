<?php
namespace mp;

require_once dirname(__FILE__) . "/mp_controller.php";
/**
 *
 */
class authapi extends mp_controller {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 * 获得定义的认证接口
	 *
	 * 返回当前公众号和它的父账号的
	 *
	 * $own
	 * $valid
	 * $cascaded
	 */
	public function get_action($own = 'N', $valid = null) {
		$modelAuth = $this->model('user/authapi');

		$apis = $modelAuth->byMpid($this->mpid, $valid, 'N');

		return new \ResponseData($apis);
	}
	/**
	 * 获得定义的认证接口
	 *
	 * 返回当前公众号和它的父账号的
	 *
	 * $own
	 * $valid
	 * $cascaded
	 */
	public function list_action($own = 'N', $valid = null) {
		$modelAuth = $this->model('user/authapi');

		$apis = $modelAuth->byMpid($this->mpid, $valid, $own);

		return new \ResponseData($apis);
	}
	/**
	 *
	 */
	public function update_action($type, $id = null) {
		$uid = \TMS_CLIENT::get_client_uid();

		$nv = $this->getPostJson();

		if (empty($id)) {
			/**
			 * 如果是首次使用内置接口，就创建新的接口定义
			 */
			$codeId = $this->_pageCreate();
			$i = array(
				'mpid' => $this->mpid,
				'name' => '内置认证',
				'type' => 'inner',
				'valid' => 'N',
				'creater' => $uid,
				'create_at' => time(),
				'entry_statement' => '无法确认您是否有权限进行该操作，请先完成【<a href="{{authapi}}">用户身份确认</a>】。',
				'acl_statement' => '您的身份识别信息没有放入白名单中，请与系统管理员联系。',
				'notpass_statement' => '您的邮箱还没有验证通过，若未收到验证邮件请联系系统管理员。若需要重发验证邮件，请先完成【<a href="{{authapi}}">用户身份确认</a>】。',
				'url' => TMS_APP_API_PREFIX . "/member/auth",
				'auth_code_id' => $codeId,
			);
			$i = array_merge($i, (array) $nv);
			$id = $this->model()->insert('xxt_member_authapi', $i, true);
		} else {
			/**
			 * 更新已有的认证接口定义
			 */
			if (isset($nv->entry_statement)) {
				$nv->entry_statement = $this->model()->escape(urldecode($nv->entry_statement));
			} else if (isset($nv->acl_statement)) {
				$nv->acl_statement = $this->model()->escape(urldecode($nv->acl_statement));
			} else if (isset($nv->notpass_statement)) {
				$nv->notpass_statement = $this->model()->escape(urldecode($nv->notpass_statement));
			} else if (isset($nv->extattr)) {
				foreach ($nv->extattr as &$attr) {
					$attr->id = urlencode($attr->id);
					$attr->label = urlencode($attr->label);
				}
				$nv->extattr = urldecode(json_encode($nv->extattr));
			} else if (isset($nv->type) && $nv->type === 'inner') {
				$nv->url = TMS_APP_API_PREFIX . "/member/auth";
			}
			$rst = $this->model()->update(
				'xxt_member_authapi',
				(array) $nv,
				"mpid='$this->mpid' and authid='$id'"
			);
		}

		$api = $this->model('user/authapi')->byId($id);

		return new \ResponseData($api);
	}
	/**
	 * 填加自定义认证接口
	 * 自定义认证接口只有在本地部署版本中才有效
	 */
	public function create_action() {
		$uid = \TMS_CLIENT::get_client_uid();
		$codeId = $this->_pageCreate();
		$i = array(
			'mpid' => $this->mpid,
			'name' => '',
			'type' => 'inner',
			'valid' => 'N',
			'creater' => $uid,
			'create_at' => time(),
			'entry_statement' => '无法确认您是否有权限进行该操作，请先完成【<a href="{{authapi}}">用户身份确认</a>】。',
			'acl_statement' => '您的身份识别信息没有放入白名单中，请与系统管理员联系。',
			'notpass_statement' => '您的邮箱还没有验证通过，若未收到验证邮件请联系系统管理员。若需要重发验证邮件，请先完成【<a href="{{authapi}}">用户身份确认</a>】。',
			'url' => TMS_APP_API_PREFIX . "/member/auth",
		);
		$id = $this->model()->insert('xxt_member_authapi', $i, true);

		$q = array('*', 'xxt_member_authapi', "mpid='$this->mpid' and authid='$id'");

		$api = $this->model()->query_obj_ss($q);

		return new \ResponseData($api);
	}
	/**
	 * 根据模版重置用户认证页面
	 *
	 * @param int $codeId
	 */
	public function pageReset_action($codeId, $template = 'basic') {
		$uid = \TMS_CLIENT::get_client_uid();

		$templateDir = dirname(__FILE__) . '/_template/auth';
		$data = array(
			'html' => file_get_contents($templateDir . '/' . $template . '.html'),
			'css' => file_get_contents($templateDir . '/' . $template . '.css'),
			'js' => file_get_contents($templateDir . '/' . $template . '.js'),
		);

		$rst = \TMS_APP::model('code\page')->modify($codeId, $data);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	private function _pageCreate($template = 'basic') {
		$uid = \TMS_CLIENT::get_client_uid();

		$templateDir = dirname(__FILE__) . '/_template/auth';
		$data = array(
			'html' => file_get_contents($templateDir . '/' . $template . '.html'),
			'css' => file_get_contents($templateDir . '/' . $template . '.css'),
			'js' => file_get_contents($templateDir . '/' . $template . '.js'),
		);

		$code = \TMS_APP::model('code\page')->create($uid, $data);

		return $code->id;
	}
	/**
	 * 只有没有被使用的自定义接口才允许被删除
	 */
	public function delete_action($id) {
		$rst = $this->model()->delete('xxt_member_authapi', "mpid='$this->mpid' and authid='$id' and used=0");

		return new \ResponseData($rst);
	}
	/**
	 * 更新内置用户注册设置
	 */
	public function updateUserauth_action($authid) {
		$nv = (array) $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_member_authapi',
			$nv,
			"authid=$authid and mpid='$this->mpid'"
		);

		return new \ResponseData($rst);
	}
}