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
			} else if(isset($nv->qy_ab)){
				$this->model()->update('xxt_site_member_schema',['qy_ab'=>'N'],"siteid='$this->siteId' and id!='$id'");
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
	/**
	 * 将内部组织结构数据全量导入到企业号通讯录
	 *
	 * $site
	 * $authid
	 */
	public function import2Qy_action($site, $authid) {
		return new \ResponseError('not support');
	}
	/**
	 * 将内部组织结构数据增量导入到企业号通讯录
	 *
	 * $site
	 * $authid
	 */
	public function sync2Qy_action($site, $authid) {
		return new \ResponseError('not support');
	}
	/**
	 * 从企业号通讯录同步用户数据
	 *
	 * $authid
	 * $pdid 父部门id,若pdid不指定，默认获取有权限的部门
	 *
	 */
	public function syncFromQy_action($site, $authid, $pdid = null) {
		$qyConfig = $this->model('sns\qy')->bySite($site);
		if (!$qyConfig || $qyConfig->joined === 'N') {
			return new \ResponseError('未与企业号连接，无法同步通讯录');
		}
		$timestamp = time(); // 进行同步操作的时间戳
		$qyproxy = $this->model('sns\qy\proxy', $qyConfig);
		$model = $this->model();
		$modelDept = $this->model('site\user\department');
		/**
		 * 同步部门数据
		 */
		$mapDeptR2L = array(); // 部门的远程ID和本地ID的映射
		$result = $qyproxy->departmentList($pdid);
		if ($result[0] === false) {
			return new \ResponseError($result[1]);
		}

		$rootDepts = array(); // 根部门
		$rdepts = $result[1]->department;
		foreach ($rdepts as $rdept) {
			$pid = $rdept->parentid == 0 ? 0 : isset($mapDeptR2L[$rdept->parentid]['id']) ? $mapDeptR2L[$rdept->parentid]['id'] : 0;
			if ($pid === 0) {
				$rootDepts[] = $rdept;
			}
			$rdeptName = $rdept->name;
			unset($rdept->name);
			/**
			 * 如果已经同步过，更新数据和时间戳；否则创建新本地数据
			 */
			$q = array(
				'id,fullpath,sync_at',
				'xxt_site_member_department',
				"siteid='$site' and extattr like '%\"id\":$rdept->id,%'",
			);
			if (!($ldept = $model->query_obj_ss($q))) {
				$ldept = $modelDept->create($site, $authid, $pid, null);
			}

			/**
			 * 更新fullpath
			 * fullpath包含节点自身的id
			 */
			if ($pid == 0) {
				$parentfullpath = "$ldept->id";
			} else {
				$qp = array(
					'fullpath',
					'xxt_site_member_department',
					"siteid='$site' and id=$pid", //获得pid的fullpatj，组合成新的fullpath
				);
				$parentfullpath = $model->query_val_ss($qp);
				$parentfullpath .= ",$ldept->id"; //本地的id
			}
			$i = array(
				'pid' => $pid,
				'sync_at' => $timestamp,
				'name' => $rdeptName,
				'fullpath' => $parentfullpath,
				'extattr' => json_encode($rdept),
			);
			$model->update(
				'xxt_site_member_department',
				$i,
				"siteid='$site' and id=$ldept->id"
			);
			$mapDeptR2L[$rdept->id] = array('id' => $ldept->id, 'path' => $parentfullpath);
		}
		/**
		 * 清空同步不存在的部门
		 */
		$this->model()->delete(
			'xxt_site_member_department',
			"siteid='$site' and sync_at<" . $timestamp
		);
		/**
		 * 同步部门下的用户
		 */
		$fan=\TMS_APP::M('sns\qy\fan');
		foreach ($rootDepts as $rootDept) {
			$result = $qyproxy->userList($rootDept->id, 1);
			if ($result[0] === false) {
				return new \ResponseError($result[1]);
			}
			$users = $result[1]->userlist;
			foreach ($users as $user) {
				$q = array(
					'sync_at',
					'xxt_site_qyfan',
					"siteid='$site' and openid='$user->userid'",
				);
				if (!($luser = $model->query_obj_ss($q))) {
					$fan->createQyFan($site, $user, $authid, $timestamp, $mapDeptR2L);
				} else if ($luser->sync_at < $timestamp) {
					$fan->updateQyFan($site, $luser, $user, $authid, $timestamp, $mapDeptR2L);
				}
			}
		}
		/**
		 * 清空没有同步的粉丝数据
		 */
		$model->delete(
			'xxt_site_qyfan',
			"siteid='$site' and sync_at<" . $timestamp
		);
		/**
		 * 同步标签
		 */
		$result = $qyproxy->tagList();
		if ($result[0] === false) {
			return new \ResponseError($result[1]);
		}
		$tags = $result[1]->taglist;
		foreach ($tags as $tag) {
			$q = array(
				'id,sync_at',
				'xxt_site_member_tag',
				"siteid='$site' and extattr like '{\"tagid\":$tag->tagid}%'",
			);
			if (!($ltag = $model->query_obj_ss($q))) {
				$t = array(
					'siteid' => $site,
					'sync_at' => $timestamp,
					'name' => $tag->tagname,
					'schema_id' => $authid,
					'extattr' => json_encode(array('tagid' => $tag->tagid)),
				);
				$memberTagId = $model->insert('xxt_site_member_tag', $t, true);
			} else {
				$memberTagId = $ltag->id;
				$t = array(
					'sync_at' => $timestamp,
					'name' => $tag->tagname,
				);
				$this->model()->update(
					'xxt_site_member_tag',
					$t,
					"siteid='$site' and id=$ltag->id"
				);
			}

			/**
			 * 建立标签和成员、部门的关联
			 */
			$result = $qyproxy->tagUserList($tag->tagid);
			if ($result[0] === false) {
				return new \ResponseError($result[1]);
			}
			$tagUsers = $result[1]->userlist;
			foreach ($tagUsers as $user) {
				$q = array(
					'sync_at,tags',
					'xxt_site_qyfan',
					"siteid='$site' and openid='$user->userid'",
				);
				if ($fans = $model->query_obj_ss($q)) {
					if (empty($fans->tags)) {
						$fans->tags = $memberTagId;
					} else {
						$fans->tags .= ',' . $memberTagId;
					}
					$model->update(
						'xxt_site_qyfan',
						array('tags' => $fans->tags),
						"siteid='$site' and openid='$user->userid'"
					);
				}
			}
		}
		/**
		 * 清空已有标签
		 */
		$model->delete(
			'xxt_site_member_tag',
			"siteid='$site' and sync_at<" . $timestamp
		);

		$rst = array(
			isset($rdepts) ? count($rdepts) : 0,
			isset($users) ? count($users) : 0,
			isset($tags) ? count($tags) : 0,
			$timestamp,
		);

		return new \ResponseData($rst);
	}

	//获取同步日志
	public function syncLog_action($site, $type = '', $page, $size) {
		if ($type == '' || $type == 'syncFromQy') {
			$typePost = $this->getPostJson();
			if ($typePost->syncType == 'department') {
				$p = array('*', 'xxt_site_member_department', "siteid = '$site'");
			} elseif ($typePost->syncType == 'tag') {
				$p = array('*', 'xxt_site_member_tag', "siteid = '$site'");
			} else {
				$p = array('*', 'xxt_site_qyfan', "siteid = '$site'");
			}
		} else {
			return new \ResponseData("暂无");
		}

		$p2['r']['o'] = ($page - 1) * $size;
		$p2['r']['l'] = $size;
		$p2['o'] = 'id desc';
		$result = array();
		if ($sync = $this->model()->query_objs_ss($p, $p2)) {
			$result['data'] = $sync;
			$p[0] = 'count(*)';
			$total = (int) $this->model()->query_val_ss($p);
			$result['total'] = $total;
		} else {
			$result['data'] = array();
			$result['total'] = 0;
		}

		return new \ResponseData($result);
	}
}