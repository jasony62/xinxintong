<?php
namespace site\fe\matter;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 *
 */
class base extends \site\fe\base {
	/**
	 * 打开页面时设置客户端的标识，用户在后续的操作中判断调用是否来源于之前的客户端
	 */
	protected function _setAgentEnter($matterId) {
		/* user */
		$user = $this->who;
		/* set key */
		$_SESSION['AGENTENTER_' . $matterId . '_' . $user->uid] = time();
	}
	/**
	 * 判断调用是否来源于之前的客户端
	 */
	protected function _isAgentEnter($matterId) {
		/* user */
		$user = $this->who;
		/* set key */
		return isset($_SESSION['AGENTENTER_' . $matterId . '_' . $user->uid]);
	}
	/**
	 * 跳转到用户认证页
	 */
	protected function gotoMember($siteId, $aMemberSchemas, $userid, $targetUrl = null) {
		is_string($aMemberSchemas) && $aMemberSchemas = explode(',', $aMemberSchemas);
		/**
		 * 如果不是注册用户，要求先进行认证
		 */
		if (count($aMemberSchemas) === 1) {
			$schema = $this->model('site\user\memberschema')->byId($aMemberSchemas[0], ['fields' => 'id,url']);
			strpos($schema->url, 'http') === false && $authUrl = 'http://' . APP_HTTP_HOST;
			$authUrl .= $schema->url;
			$authUrl .= "?site=$siteId";
			$authUrl .= "&schema=" . $aMemberSchemas[0];
		} else {
			/**
			 * 让用户选择通过那个认证接口进行认证
			 */
			$authUrl = 'http://' . APP_HTTP_HOST . '/rest/site/fe/user/memberschema';
			$authUrl .= "?site=$siteId";
			$authUrl .= "&schema=" . implode(',', $aMemberSchemas);
		}
		/**
		 * 返回身份认证页
		 */
		if ($targetUrl === false) {
			/**
			 * 直接返回认证地址
			 * todo angular无法自动执行初始化，所以只能返回URL，由前端加载页面
			 */
			$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
			header($protocol . ' 401 Unauthorized');
			die("$authUrl");
		} else {
			/**
			 * 跳转到认证接口
			 */
			if (empty($targetUrl)) {
				$targetUrl = $this->getRequestUrl();
			}
			/**
			 * 将跳转信息保存在cookie中
			 */
			$targetUrl = $this->model()->encrypt($targetUrl, 'ENCODE', $siteId);
			$this->mySetCookie("_{$siteId}_mauth_t", $targetUrl, time() + 300);
			$this->redirect($authUrl);
		}
	}
	/**
	 *
	 */
	private function __upgradeOldMembers($siteId, $userid, $openid) {
		$model = $this->model();
		$members = array();
		$q = array(
			'authed_identity,authapi_id',
			'xxt_member',
			"mpid='$siteId' and forbidden='N' and openid='$openid'",
		);
		if ($memberOlds = $model->query_objs_ss($q)) {
			foreach ($memberOlds as $memberOld) {
				$q = array(
					'*',
					'xxt_site_member',
					"siteid='{$siteId}' and schema_id='{$memberOld->authapi_id}' and identity='{$memberOld->authed_identity}' and forbidden='N'",
				);
				if ($member = $model->query_obj_ss($q)) {
					$model->update('xxt_site_member', array('userid' => $userid), "id='{$member->id}'");
					$member->userid = $userid;
					$members[] = $member;
				}
			}
		}

		return $members;
	}
	/**
	 * 访问控制设置
	 *
	 * 检查当前用户是否为认证用户
	 * 检查当前用户是否在白名单中
	 *
	 * 如果用户没有认证，跳转到认证页
	 *
	 */
	protected function accessControl($siteId, $objId, $memberSchemas, $userid, &$obj, $targetUrl = null) {
		$model = $this->model();
		$siteId = $model->escape($siteId);
		$objId = $model->escape($objId);
		$aMemberSchemas = explode(',', $memberSchemas);
		$members = array();
		foreach ($aMemberSchemas as $memberSchema) {
			if (isset($this->who->members->{$memberSchema})) {
				$members[] = $this->who->members->{$memberSchema};
			}
		}
		if (empty($members)) {
			$members = $this->model('site\user\member')->byUser($userid, ['schemas' => $memberSchemas]);
		}
		//如果是企业号的用户访问
		if (isset($this->who->sns->qy) && empty($members)) {
			//根据openid查询粉丝表
			$openid = $this->who->sns->qy->openid;
			$p = array(
				'siteid,openid,nickname,mobile,email,sync_at',
				'xxt_site_qyfan',
				"siteid='$siteId' and openid='$openid' and subscribe_at > 0 and unsubscribe_at = 0 ",
			);
			$qySnsUser = $model->query_obj_ss($p);
			if ($qySnsUser) {
				$members['qy'] = $qySnsUser;
			}

		}
		if (empty($members)) {
			if ($this->userAgent() === 'wx') {
				if (isset($this->who->sns->wx)) {
					$openid = $this->who->sns->wx->openid;
				} else if (isset($this->who->sns->qy)) {
					$openid = $this->who->sns->qy->openid;
				}
			} else if ($this->userAgent() === 'yx') {
				if (isset($this->who->sns->yx)) {
					$openid = $this->who->sns->yx->openid;
				}
			}
			if (isset($openid)) {
				$members = $this->__upgradeOldMembers($siteId, $userid, $openid);
			}
		}
		if (empty($members)) {
			/**
			 * 如果不是认证用户，先进行认证
			 */
			$this->gotoMember($siteId, $aMemberSchemas, $userid, $targetUrl);
		} else {
			$passed = false;
			//如果时从企业号进入的用户不需要认证
			if (isset($members['qy'])) {
				$passed = true;
			} else {
				foreach ($members as $member) {
					if ($this->canAccessObj($siteId, $objId, $member, $memberSchemas, $obj)) {
						/**
						 * 检查用户是否通过了验证
						 */
						$q = array(
							'verified',
							'xxt_site_member',
							"siteid='$siteId' and id='$member->id'",
						);
						if ('Y' !== $model->query_val_ss($q)) {
							$r = $this->model('site\user\memberschema')->getNotpassStatement($member->schema_id, $siteId);
							$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
							header($protocol . ' 401 Unauthorized');
							\TPL::assign('title', '访问控制未通过');
							\TPL::assign('body', $r);
							\TPL::output('error');
							exit;
						}
						$passed = true;
						break;
					}
				}
				!$passed && $this->gotoOutAcl($siteId, $member->schema_id);

				return $member;
			}
		}
	}
	/**
	 *
	 */
	private function gotoOutAcl($mpid, $authid) {
		$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
		header($protocol . ' 401 Unauthorized');
		$r = $this->model('user/authapi')->getAclStatement($authid, $mpid);
		TPL::assign('title', '访问控制未通过');
		TPL::assign('body', $r);
		TPL::output('error');
		exit;
	}
	/*
	* 获取我的分享信息
	*/
	protected function getMyShareInfo($oUser, $matterType, $matterId, $orderBy = 'read', $page = null, $size = null) {
		$model = $this->model()->setOnlyWriteDbConn(true);

		$q = [];
		$q2 = [];
		switch ($orderBy) {
			case 'shareF':
				$q[0] = 's.userid,count(*) shareF_sum,a.nickname,a.headimgurl';
				$q[1] = 'xxt_log_matter_share s,xxt_site_account a';
				$q[2] = "s.matter_id = '{$matterId}' and s.matter_type = '{$matterType}' and s.matter_shareby like '" . $oUser->uid . "_%' and s.share_to ='F' and s.userid = a.uid";

				$q2['g'] = 's.userid';
				$q2['o'] = 'shareF_sum desc,s.share_at desc';

				if (!empty($page) && !empty($size)) {
					$q2['r']['o'] = ($page - 1) * $size;
					$q2['r']['l'] = $size;
				}
				
				$users = $model->query_objs_ss($q, $q2);
				$q[0] = "count(distinct s.userid)";
				$total = (int) $model->query_val_ss($q);

				break;
			case 'shareT':
				$q[0] = 's.userid,count(*) shareT_sum,a.nickname,a.headimgurl';
				$q[1] = 'xxt_log_matter_share s,xxt_site_account a';
				$q[2] = "s.matter_id = '{$matterId}' and s.matter_type = '{$matterType}' and s.matter_shareby like '" . $oUser->uid . "_%' and s.share_to ='T' and s.userid = a.uid";

				$q2['g'] = 's.userid';
				$q2['o'] = 'shareT_sum desc,s.share_at desc';

				if (!empty($page) && !empty($size)) {
					$q2['r']['o'] = ($page - 1) * $size;
					$q2['r']['l'] = $size;
				}
				
				$users = $model->query_objs_ss($q, $q2);
				$q[0] = "count(distinct s.userid)";
				$total = (int) $model->query_val_ss($q);

				break;
			case 'attractRead':
				$q = "select r.userid,(select count(*) from xxt_log_matter_read r1 where r1.matter_id='" . $model->escape($matterId) . "' and r1.matter_type='" . $model->escape($matterType) . "' and r1.matter_shareby like CONCAT(r.userid,'_%')) as attractRead_sum,a.nickname,a.headimgurl from xxt_log_matter_read r,xxt_site_account a where r.matter_id = '{$matterId}' and r.matter_type = '{$matterType}' and r.matter_shareby like '" . $oUser->uid . "_%' and r.userid = a.uid group by r.userid order by attractRead_sum desc,r.read_at desc";
				
				if (!empty($page) && !empty($size)) {
					$q .= " limit " . ($page - 1) * $size . "," . $size;
				}

				$users = $model->query_objs($q);
				$q = "select count(distinct r.userid) from xxt_log_matter_read r,xxt_site_account a where r.matter_id = '{$matterId}' and r.matter_type = '{$matterType}' and r.matter_shareby like '" . $oUser->uid . "_%' and r.userid = a.uid";
				$total = (int) $model->query_value($q);
				
				break;
			default:
				$q[0] = 'r.userid,count(*) as read_sum,a.nickname,a.headimgurl';
				$q[1] = 'xxt_log_matter_read r,xxt_site_account a';
				$q[2] = "r.matter_id = '{$matterId}' and r.matter_type = '{$matterType}' and r.matter_shareby like '" . $oUser->uid . "_%' and r.userid = a.uid";

				$q2['g'] = 'r.userid';
				$q2['o'] = 'read_sum desc,r.read_at desc';

				if (!empty($page) && !empty($size)) {
					$q2['r']['o'] = ($page - 1) * $size;
					$q2['r']['l'] = $size;
				}
				
				$users = $model->query_objs_ss($q, $q2);
				$q[0] = "count(distinct r.userid)";
				$total = (int) $model->query_val_ss($q);

				break;
		}

		if($users){
			foreach ($users as $user) {
				if (!isset($user->read_sum)) {
					$q = [
						'count(*) read_sum',
						'xxt_log_matter_read',
						[]
					];
					$q[2]["matter_id"] = $matterId;
					$q[2]["matter_type"] = $matterType;
					$q[2]["matter_shareby"] = (object) ['op' => 'like', 'pat' => $oUser->uid . '_%'];
					$q[2]["userid"] = $user->userid;

					$rst = $model->query_obj_ss($q);
					if ($rst) {
						$user->read_sum = $rst->read_sum;
					} else {
						$user->read_sum = 0;
					}
				}
				if (!isset($user->shareF_sum)) {
					$q = [
						'count(*) shareF_sum',
						'xxt_log_matter_share',
						[]
					];
					$q[2]["matter_id"] = $matterId;
					$q[2]["matter_type"] = $matterType;
					$q[2]["matter_shareby"] = (object) ['op' => 'like', 'pat' => $oUser->uid . '_%'];
					$q[2]["userid"] = $user->userid;
					$q[2]["share_to"] = 'F';

					$rst = $model->query_obj_ss($q);
					if ($rst) {
						$user->shareF_sum = $rst->shareF_sum;
					} else {
						$user->shareF_sum = 0;
					}
				}
				if (!isset($user->shareT_sum)) {
					$q = [
						'count(*) shareT_sum',
						'xxt_log_matter_share',
						[]
					];
					$q[2]["matter_id"] = $matterId;
					$q[2]["matter_type"] = $matterType;
					$q[2]["matter_shareby"] = (object) ['op' => 'like', 'pat' => $oUser->uid . '_%'];
					$q[2]["userid"] = $user->userid;
					$q[2]["share_to"] = 'T';

					$rst = $model->query_obj_ss($q);
					if ($rst) {
						$user->shareT_sum = $rst->shareT_sum;
					} else {
						$user->shareT_sum = 0;
					}
				}
				/*ta带来的阅读数是否加上ta自己的阅读数？？？*/
				if (!isset($user->attractRead_sum)) {
					$q = [
						'count(*) attractRead_sum',
						'xxt_log_matter_read',
						[]
					];
					$q[2]["matter_id"] = $matterId;
					$q[2]["matter_type"] = $matterType;
					$q[2]["matter_shareby"] = (object) ['op' => 'like', 'pat' => $user->userid . '_%'];

					$rst = $model->query_obj_ss($q);
					if ($rst) {
						$user->attractRead_sum = $rst->attractRead_sum;
					} else {
						$user->attractRead_sum = 0;
					}
				}
			}
		}


		$data = new \stdClass;
		$data->users = $users;
		$data->total = $total;

		return $data;
	}
}