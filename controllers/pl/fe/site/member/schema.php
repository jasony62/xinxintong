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
	 * 获得通讯录定义
	 *
	 * @param string $valid
	 * @param int $mission_id 逗号分隔的项目id，团队通讯录的项目id为0，“0,123”代表团队通讯录和项目123的通讯录
	 *
	 */
	public function get_action($mschema) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelSchema = $this->model('site\user\memberschema');

		$oMschema = $modelSchema->byId($mschema);
		if ($oMschema) {
			if ($oMschema->matter_type === 'mission' && !empty($oMschema->matter_id)) {
				$oMschema->mission = $this->model('matter\mission')->byId($oMschema->matter_id);
			}
		}

		return new \ResponseData($oMschema);
	}
	/**
	 * 返回指定通讯录的概况信息
	 *
	 * @param string $mschema 逗号分隔的通讯录id
	 */
	public function overview_action($mschema) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelMs = $this->model('site\user\memberschema');
		$result = new \stdClass;
		$schemaIds = explode(',', $mschema);
		foreach ($schemaIds as $schemaId) {
			$result->{$schemaId} = $modelMs->overview($schemaId);
		}

		return new \ResponseData($result);
	}
	/**
	 * 获得通讯录定义
	 *
	 * @param string $valid
	 * @param string $matter 逗号分隔的素材id和type，例如：123,mission
	 * @param string $onlyMatter
	 *
	 */
	public function list_action($valid = null, $matter = null, $onlyMatter = 'N') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelSchema = $this->model('site\user\memberschema');

		$options = [];
		$options['onlyMatter'] = $onlyMatter;

		if (isset($matter)) {
			$oMatter = new \stdClass;
			list($oMatter->id, $oMatter->type) = explode(',', $matter);
			$options['matter'] = $oMatter;
		}

		$schemas = $modelSchema->bySite($this->siteId, $valid, $options);

		return new \ResponseData($schemas);
	}
	/**
	 * 获得此通讯录有权导入的通讯录
	 * @return [type] [description]
	 */
	public function listImportSchema_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSchema = $this->model('site\user\memberschema');
		$schemas = $modelSchema->importSchema($site, $id);

		return new \ResponseData($schemas);
	}
	/**
	 * 导入选中通讯录
	 * $id 要导入的通讯录id
	 * $rounds 进度批次
	 */
	public function importSchema_action($site, $id, $rounds = 0) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$schemas = $this->getPostJson();
		if (empty($schemas)) {
			return new \ResponseError('请选择要导入的通讯录');
		}

		$model = $this->model();
		$model->setOnlyWriteDbConn(true);
		//查询被导入的通讯录中已有的用户
		$q = [
			'userid',
			'xxt_site_member',
			['schema_id' => $id],
		];
		$usersOld = $model->query_vals_ss($q);

		//获取所有即将导入的用户
		$schemas = '(' . implode(',', $schemas) . ')';
		$q = [
			'userid,unionid,create_at,identity,name,mobile,mobile_verified,email,email_verified,extattr,depts,tags,verified,forbidden,invite_code',
			'xxt_site_member',
			"forbidden='N' and schema_id in $schemas",
		];
		$q2 = ['o' => 'create_at desc,id desc'];
		$usersAll = $model->query_objs_ss($q, $q2);
		if (empty($usersAll)) {
			return new \ResponseError('没有要导入的用户');
		}

		//去除重复的userid，如果有重复的留下时间最大的
		$usersAll2 = [];
		$usersAllNew = []; //去重后的所有用户
		foreach ($usersAll as $user) {
			if (!in_array($user->userid, $usersAll2)) {
				$usersAll2[] = $user->userid;
				$usersAllNew[] = $user;
			}
		}

		if ($rounds == 0) {
			//留下通讯录中导入之前已有的重复用户
			foreach ($usersOld as $key => $userO) {
				if (!in_array($userO, $usersAll2)) {
					unset($usersOld[$key]);
				}
			}

			//从通讯录中删除重复的userid
			$site = $model->escape($site);
			$id = $model->escape($id);
			if (!empty($usersOld)) {
				$usersOld = "('" . implode("','", $usersOld) . "')";
				$model->delete('xxt_site_member', "siteid = '$site' and schema_id = $id and userid in $usersOld");
			}
		}

		//导入数据
		$create_at = time();
		$column = "siteid,schema_id,modify_at,userid,unionid,create_at,identity,name,mobile,mobile_verified,email,email_verified,extattr,depts,tags,verified,forbidden,invite_code";

		//分批次插入数据每批插入50条数据
		$usersGroup = array_chunk($usersAllNew, 50);
		$groupLength = count($usersGroup);
		if ($rounds > 0 && $rounds < $groupLength) {
			$i = $rounds;
		} elseif ($rounds >= $groupLength) {
			$importGroup = new \stdClass;
			$importGroup->group = $rounds;
			$importGroup->plan = count($usersAllNew);
			$importGroup->total = count($usersAllNew);
			$importGroup->state = 'end';
			return new \ResponseData($importGroup);
		} else {
			$i = 0;
		}
		$groups = $usersGroup[$i];
		$value = "";
		foreach ($groups as $group) {
			$group = (array) $model->escape($group);
			$groupValue = array_values($group);
			$value .= ",('$site',$id,$create_at,'" . implode("','", $groupValue) . "')";
		}
		$value = substr($value, 1);

		$model->insert("insert into xxt_site_member ($column) values $value");

		$plan = (int) $i * 50 + count($usersGroup[$i]);
		$total = count($usersAllNew);
		$importGroup = new \stdClass;
		$importGroup->group = $i;
		$importGroup->plan = $plan;
		$importGroup->total = $total;
		if ($plan == $total) {
			$importGroup->state = 'end';
		} else {
			$importGroup->state = 'continue';
		}

		return new \ResponseData($importGroup);
	}
	/**
	 * 填加自定义联系人接口
	 * 自定义联系人接口只有在本地部署版本中才有效
	 */
	public function create_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oSite = $this->model('site')->byId($site, ['fields' => 'id']);
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}

		$oConfig = $this->getPostJson();

		$modelMs = $this->model('site\user\memberschema')->setOnlyWriteDbConn(true);
		$oNewMschema = $modelMs->create($oSite, $oUser, $oConfig);

		return new \ResponseData($oNewMschema);
	}
	/**
	 * 更新定义
	 */
	public function update_action($id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		if (count(get_object_vars($oPosted)) === 0) {
			return new \ParameterError();
		}

		$modelMs = $this->model('site\user\memberschema')->setOnlyWriteDbConn(true);
		$oUpdated = new \stdClass;
		foreach ($oPosted as $prop => $val) {
			switch ($prop) {
			case 'extAttrs':
				$oUpdated->ext_attrs = $modelMs->escape($modelMs->toJson($val));
				break;
			case 'extattr':
				foreach ($val as $attr) {
					$attr->id = urlencode($attr->id);
					$attr->label = urlencode($attr->label);
				}
				$oUpdated->extattr = urldecode(json_encode($oPosted->extattr));
				break;
			case 'qy_ab':
				$oUpdated->qy_ab = $val;
				/* 将同一个站点下的其他通讯录设置为非企业号通讯录 */
				$modelMs->update('xxt_site_member_schema', ['qy_ab' => 'N'], "siteid='$this->siteId' and id!='$id'");
				break;
			default:
				$oUpdated->{$prop} = $this->escape($val);
			}
		}

		$rst = $modelMs->update(
			'xxt_site_member_schema',
			$oUpdated,
			['siteid' => $this->siteId, 'id' => $id]
		);
		$oMschema = $modelMs->byId($id);

		/* 更新项目关联信息 */
		if ($oMschema->matter_type === 'mission') {
			$this->model('matter\mission')->updateMatter($oMschema->matter_id, $oMschema);
		}

		return new \ResponseData($oMschema);
	}
	/**
	 * 只有没有被使用的自定义接口才允许被删除
	 */
	public function delete_action($id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$id = $this->escape($id);
		$modelMs = $this->model('site\user\memberschema');
		$oMschema = $modelMs->byId($id);
		if (false === $oMschema) {
			return new \ObjectNotFoundError();
		}

		/* 更新项目关联信息 */
		if ($oMschema->matter_type === 'mission' && !empty($oMschema->matter_id)) {
			$this->model('matter\mission')->removeMatter($oMschema->matter_id, $oMschema);
		}

		$q = ['count(*)', 'xxt_site_member', ['schema_id' => $oMschema->id]];
		if ((int) $modelMs->query_val_ss($q)) {
			$rst = $modelMs->update(
				'xxt_site_member_schema',
				['valid' => 'N'],
				['id' => $oMschema->id]
			);
			$this->model('matter\log')->matterOp($oMschema->siteid, $oUser, $oMschema, 'Recycle');
		} else {
			$rst = $modelMs->delete(
				'xxt_site_member_invite',
				['schema_id' => $oMschema->id]
			);
			$rst = $modelMs->delete(
				'xxt_site_member_schema',
				['id' => $oMschema->id]
			);
			$this->model('matter\log')->matterOp($oMschema->siteid, $oUser, $oMschema, 'D');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 恢复停用的通讯录
	 */
	public function restore_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelMs = $this->model('site\user\memberschema');
		if (false === ($oMschema = $modelMs->byId($id))) {
			return new \ObjectNotFoundError('数据已经被彻底删除，无法恢复');
		}
		$rst = $modelMs->restore($oUser, $oMschema);

		return new \ResponseData($rst);
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
	public function import2Qy_action($site) {
		return new \ResponseError('not support');
	}
	/**
	 * 将内部组织结构数据增量导入到企业号通讯录
	 *
	 * $site
	 * $authid
	 */
	public function sync2Qy_action($site) {
		return new \ResponseError('not support');
	}
	/**
	 * 从企业号通讯录同步用户数据
	 *
	 * @param $string $site
	 * @param int $pdid 父部门id,若pdid不指定，默认获取有权限的部门
	 *
	 */
	public function syncFromQy_action($site, $pdid = null) {
		$qyConfig = $this->model('sns\qy')->bySite($site);
		if (!$qyConfig || $qyConfig->joined === 'N') {
			return new \ResponseError('未与企业号连接，无法同步通讯录');
		}

		$schema = $this->model('site\user\memberschema')->qyabSchemaBySite($site, ['fields' => 'id']);
		if ($schema === false) {
			return new \ResponseError('没有设置企业号同步使用的自定义用户，请设置后再同步！');
		}
		$authid = $schema->id;

		$timestamp = time(); // 进行同步操作的时间戳
		$qyproxy = $this->model('sns\qy\proxy', $qyConfig);
		$modelDept = $this->model('site\user\department');
		//同步操作应该是用写链接
		$modelDept->setOnlyWriteDbConn(true);
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
			if (!($ldept = $modelDept->query_obj_ss($q))) {
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
				$parentfullpath = $modelDept->query_val_ss($qp);
				$parentfullpath .= ",$ldept->id"; //本地的id
			}
			$i = array(
				'pid' => $pid,
				'sync_at' => $timestamp,
				'name' => $rdeptName,
				'fullpath' => $parentfullpath,
				'extattr' => json_encode($rdept),
			);
			$modelDept->update(
				'xxt_site_member_department',
				$i,
				"siteid='$site' and id=$ldept->id"
			);
			$mapDeptR2L[$rdept->id] = array('id' => $ldept->id, 'path' => $parentfullpath);
		}
		/**
		 * 清空同步不存在的部门
		 */
		$modelDept->delete(
			'xxt_site_member_department',
			"siteid='$site' and sync_at<" . $timestamp
		);
		/**
		 * 同步部门下的用户
		 */
		$fan = \TMS_APP::M('sns\qy\fan');
		foreach ($rootDepts as $rootDept) {
			$result = $qyproxy->userList($rootDept->id, 1);
			if ($result[0] === false) {
				return new \ResponseError($result[1]);
			}
			$oUsers = $result[1]->userlist;
			foreach ($oUsers as $oUser) {
				$q = array(
					'sync_at',
					'xxt_site_qyfan',
					"siteid='$site' and openid='$oUser->userid'",
				);
				if (!($luser = $modelDept->query_obj_ss($q))) {
					$fan->createQyFan($site, $oUser, $authid, $timestamp, $mapDeptR2L);
				} else if ($luser->sync_at < $timestamp) {
					$fan->updateQyFan($site, $luser, $oUser, $authid, $timestamp, $mapDeptR2L);
				}
			}
		}
		/**
		 * 清空没有同步的粉丝数据
		 */
		$modelDept->delete(
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
			if (!($ltag = $modelDept->query_obj_ss($q))) {
				$t = array(
					'siteid' => $site,
					'sync_at' => $timestamp,
					'name' => $tag->tagname,
					'schema_id' => $authid,
					'extattr' => json_encode(array('tagid' => $tag->tagid)),
				);
				$memberTagId = $modelDept->insert('xxt_site_member_tag', $t, true);
			} else {
				$memberTagId = $ltag->id;
				$t = array(
					'sync_at' => $timestamp,
					'name' => $tag->tagname,
				);
				$modelDept->update(
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
			foreach ($tagUsers as $oUser) {
				$q = array(
					'sync_at,tags',
					'xxt_site_qyfan',
					"siteid='$site' and openid='$oUser->userid'",
				);
				if ($fans = $modelDept->query_obj_ss($q)) {
					if (empty($fans->tags)) {
						$fans->tags = $memberTagId;
					} else {
						$fans->tags .= ',' . $memberTagId;
					}
					$modelDept->update(
						'xxt_site_qyfan',
						array('tags' => $fans->tags),
						"siteid='$site' and openid='$oUser->userid'"
					);
				}
			}
		}
		/**
		 * 清空已有标签
		 */
		$modelDept->delete(
			'xxt_site_member_tag',
			"siteid='$site' and sync_at<" . $timestamp
		);

		$rst = array(
			isset($rdepts) ? count($rdepts) : 0,
			isset($oUsers) ? count($oUsers) : 0,
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
	/**
	 * 应用的微信二维码
	 *
	 * @param string $site
	 * @param string $app
	 *
	 */
	public function wxQrcode_action($site, $mschema) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelQrcode = $this->model('sns\wx\call\qrcode');

		$qrcodes = $modelQrcode->byMatter('mschema', $mschema);

		return new \ResponseData($qrcodes);
	}
}