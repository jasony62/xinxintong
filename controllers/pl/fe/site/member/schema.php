<?php
namespace pl\fe\site\member;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 自定义用户控制器
 */
class schema extends \pl\fe\base {
	//
	private $siteId;
	/**
	 *
	 */
	public function __construct() {
		$siteId = $_GET['site'];
		$this->siteId = $siteId;
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
	public function list_action($valid = null) {
		$modelSchema = $this->model('site\user\memberschema');

		$schemas = $modelSchema->bySite($this->siteId, $valid);

		return new \ResponseData($schemas);
	}
	/**
	 *
	 */
	public function update_action($type, $id = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$nv = $this->getPostJson();

		if (empty($id)) {
			/**
			 * 如果是首次使用内置接口，就创建新的接口定义
			 */
			$code = $this->_pageCreate();
			$i = array(
				'siteid' => $this->siteId,
				'title' => '内置认证',
				'type' => 'inner',
				'valid' => 'N',
				'creater' => $user->id,
				'create_at' => time(),
				'entry_statement' => '无法确认您是否有权限进行该操作，请先完成【<a href="{{authapi}}">用户身份确认</a>】。',
				'acl_statement' => '您的身份识别信息没有放入白名单中，请与系统管理员联系。',
				'notpass_statement' => '您的邮箱还没有验证通过，若未收到验证邮件请联系系统管理员。若需要重发验证邮件，请先完成【<a href="{{authapi}}">用户身份确认</a>】。',
				'url' => TMS_APP_API_PREFIX . "/site/fe/user/member",
				'code_id' => $code->id,
				'page_code_name' => $code->name,
			);
			$i = array_merge($i, (array) $nv);
			$id = $this->model()->insert('xxt_site_member_schema', $i, true);
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
				$nv->url = TMS_APP_API_PREFIX . "/site/fe/user/member";
			}
			$rst = $this->model()->update(
				'xxt_site_member_schema',
				$nv,
				"siteid='$this->siteId' and id='$id'"
			);
		}

		$schema = $this->model('site\user\memberschema')->byId($id);

		return new \ResponseData($schema);
	}
	/**
	 * 填加自定义认证接口
	 * 自定义认证接口只有在本地部署版本中才有效
	 */
	public function create_action() {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$code = $this->_pageCreate();
		$i = array(
			'siteid' => $this->siteId,
			'title' => '',
			'type' => 'inner',
			'valid' => 'N',
			'creater' => $user->id,
			'create_at' => time(),
			'entry_statement' => '无法确认您是否有权限进行该操作，请先完成【<a href="{{authapi}}">用户身份确认</a>】。',
			'acl_statement' => '您的身份识别信息没有放入白名单中，请与系统管理员联系。',
			'notpass_statement' => '您的邮箱还没有验证通过，若未收到验证邮件请联系系统管理员。若需要重发验证邮件，请先完成【<a href="{{authapi}}">用户身份确认</a>】。',
			'url' => TMS_APP_API_PREFIX . "/site/fe/user/member",
			'code_id' => $code->id,
			'page_code_name' => $code->name,
		);
		$id = $this->model()->insert('xxt_site_member_schema', $i, true);

		$q = array('*', 'xxt_site_member_schema', "siteid='$this->siteId' and id='$id'");

		$schema = $this->model()->query_obj_ss($q);

		return new \ResponseData($schema);
	}
	/**
	 * 只有没有被使用的自定义接口才允许被删除
	 */
	public function delete_action($id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$rst = $this->model()->delete('xxt_site_member_schema', "siteid='$this->siteId' and id='$id' and used=0");

		return new \ResponseData($rst);
	}
	/**
	 * 根据模版重置用户认证页面
	 *
	 * @param int $codeId
	 */
	public function pageReset_action($site, $name, $template = 'basic') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelCode = $this->model('code\page');
		$code = $modelCode->lastByName($site, $name);

		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/site/memberschema';
		$data = array(
			'html' => file_get_contents($templateDir . '/' . $template . '.html'),
			'css' => file_get_contents($templateDir . '/' . $template . '.css'),
			'js' => file_get_contents($templateDir . '/' . $template . '.js'),
		);

		$rst = $modelCode->modify($code->id, $data);

		return new \ResponseData($rst);
	}
	/**
	 * 根据模板创建缺省页面
	 */
	private function _pageCreate($template = 'basic') {
		$uid = \TMS_CLIENT::get_client_uid();

		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/site/memberschema';

		$data = array(
			'html' => file_get_contents($templateDir . '/' . $template . '.html'),
			'css' => file_get_contents($templateDir . '/' . $template . '.css'),
			'js' => file_get_contents($templateDir . '/' . $template . '.js'),
		);

		$code = \TMS_APP::model('code\page')->create($this->siteId, $uid, $data);

		return $code;
	}
	/**
	 * 获得用户选择器的页面
	 */
	public function picker_action() {
		\TPL::output('/pl/fe/site/user/picker');
		exit;
	}
}