<?php
namespace pl\fe\matter\wall;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 信息墙
 */
class users extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/wall/frame');
		exit;
	}
	/**
	 * 获得墙内的所有用户
	 */
	public function list_action($id, $site) {
		$q = array(
			'id,wx_openid,yx_openid,qy_openid,join_at,last_msg_at,msg_num,userid,nickname',
			'xxt_wall_enroll',
			"siteid='$site' and wid='$id' and close_at=0",
		);
		$users = $this->model()->query_objs_ss($q);

		return new \ResponseData($users);
	}
	/**
	 * 从记录活动和签到活动导入用户
	 *
	 * @param string $wall
	 * @param string $app
	 */
	public function import_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$sourceApp = null;
		$params = $this->getPostJson();

		if (!empty($params->app)) {
			if ($params->appType === 'enroll') {
				$sourceApp = $this->_importByEnroll($site, $app, $params->app);
			} else if ($params->appType === 'signin') {
				$sourceApp = $this->_importBySignin($site, $app, $params);
			}
		}
		//记录操作日志
		$matter = $this->model('matter\wall')->byId($app, 'id,title,summary,pic');
		$matter->type = 'wall';
		$this->model('matter\log')->matterOp($site, $user, $matter, 'import');

		return new \ResponseData($sourceApp);
	}
	/**
	 * 从记录活动导入数据
	 */
	private function &_importByEnroll($site, $app, $byApp) {
		$sync_at = time();
		$modelWall = $this->model('matter\wall');
		$modelEnl = $this->model('matter\enroll');

		$sourceApp = $modelEnl->byId($byApp, ['fields' => 'data_schemas', 'cascaded' => 'N']);
		/* 导入活动定义 */
		$modelWall->update(
			'xxt_wall',
			[
				'last_sync_at' => $sync_at,
				'source_app' => '{"id":"' . $byApp . '","type":"enroll"}',
				'data_schemas' => $sourceApp->data_schemas,
			],
			"id='$app'"
		);
		/* 清空所有用户 */
		$this->model()->delete('xxt_wall_enroll', "wid='$app'");
		/* 获取所有登记数据 */
		$modelRec = $this->model('matter\enroll\record');
		$q = [
			'*,count(distinct userid)',
			'xxt_enroll_record',
			"aid='$byApp' and state=1 and (wx_openid != '' or yx_openid != '' or qy_openid != '') group by userid",
		];
		$records = $modelRec->query_objs_ss($q);
		/* 导入数据 */
		if (!empty($records)) {
			foreach ($records as $record) {
				//退出其它信息墙
				$this->model()->update(
					'xxt_wall_enroll',
					array('close_at' => $sync_at),
					"siteid='$site' and wid != '{$app}' and (wx_openid='{$record->wx_openid}' or yx_openid='{$record->yx_openid}' or qy_openid='{$record->qy_openid}') "
				);
				$options = array();
				$options['siteid'] = $site;
				$options['wid'] = $app;
				$options['join_at'] = $sync_at;
				$options['userid'] = $record->userid;
				$options['nickname'] = $record->nickname;
				$options['wx_openid'] = $record->wx_openid;
				$options['yx_openid'] = $record->yx_openid;
				$options['qy_openid'] = $record->qy_openid;
				$options['headimgurl'] = $record->headimgurl;
				$options['enroll_key'] = $record->enroll_key;
				$options['data'] = $record->data;

				$this->model()->insert('xxt_wall_enroll', $options, false);
			}
		}

		$num = count($records);
		return $num;
	}
	/**
	 * 从签到活动导入数据
	 * 如果指定了包括报名数据，只需要从报名活动中导入登记项的定义，签到时已经自动包含了报名数据
	 */
	private function &_importBySignin($site, $app, &$params) {
		$byApp = $params->app;
		$sync_at = time();
		$modelWall = $this->model('matter\wall');
		$modelSignin = $this->model('matter\signin');

		$oSourceApp = $modelSignin->byId($byApp, ['fields' => 'entry_rule,data_schemas', 'cascaded' => 'N']);
		$sourceDataSchemas = $oSourceApp->dataSchemas;
		/**
		 * 导入报名数据，需要合并签到和报名的登记项
		 */
		if (isset($params->includeEnroll) && $params->includeEnroll === 'Y') {
			if (!empty($oSourceApp->entryRule->enroll->id)) {
				$modelEnl = $this->model('matter\enroll');
				$oAssocEnlApp = $modelEnl->byId($oSourceApp->entryRule->enroll->id, ['fields' => 'data_schemas', 'cascaded' => 'N']);
				$diff = array_udiff($oAssocEnlApp->dataSchemas, $sourceDataSchemas, create_function('$a,$b', 'return strcmp($a->id,$b->id);'));
				$sourceDataSchemas = array_merge($sourceDataSchemas, $diff);
				$sourceDataSchemas = $modelWall->toJson($sourceDataSchemas);
			}
		}
		/* 导入活动定义 */
		$modelWall->update(
			'xxt_wall',
			[
				'last_sync_at' => $sync_at,
				'source_app' => '{"id":"' . $byApp . '","type":"signin"}',
				'data_schemas' => $sourceDataSchemas,
			],
			['id' => $app]
		);
		/* 清空所有用户 */
		$modelWall->delete('xxt_wall_enroll', ['wid' => $app]);
		/* 获取数据 */
		$modelRec = $this->model('matter\signin\record');
		$q = [
			'*,count(distinct userid)',
			'xxt_signin_record',
			"aid='$byApp' and state=1 and (wx_openid != '' or yx_openid != '' or qy_openid != '') group by userid",
		];
		$records = $modelRec->query_objs_ss($q);
		/* 导入数据 */
		if (!empty($records)) {
			foreach ($records as $oRecord) {
				//退出其它信息墙
				$modelWall->update(
					'xxt_wall_enroll',
					['close_at' => $sync_at],
					"siteid='$site' and wid != '{$app}' and (wx_openid='{$oRecord->wx_openid}' or yx_openid='{$oRecord->yx_openid}' or qy_openid='{$oRecord->qy_openid}') "
				);
				$oWallEnroll = [];
				$oWallEnroll['siteid'] = $site;
				$oWallEnroll['wid'] = $app;
				$oWallEnroll['join_at'] = $sync_at;
				$oWallEnroll['userid'] = $oRecord->userid;
				$oWallEnroll['nickname'] = $oRecord->nickname;
				$oWallEnroll['wx_openid'] = $oRecord->wx_openid;
				$oWallEnroll['yx_openid'] = $oRecord->yx_openid;
				$oWallEnroll['qy_openid'] = $oRecord->qy_openid;
				$oWallEnroll['headimgurl'] = $oRecord->headimgurl;
				$oWallEnroll['enroll_key'] = $oRecord->enroll_key;
				$oWallEnroll['data'] = $oRecord->data;

				$modelWall->insert('xxt_wall_enroll', $oWallEnroll, false);
			}
		}
		$num = count($records);
		return $num;
	}
	/**
	 * 用户导出到记录活动
	 *
	 * @param string $wall
	 * @param string $app
	 */
	public function export_action($id, $app, $onlySpeaker = 'N', $site) {

		$q = array(
			'userid,wx_openid,yx_openid,qy_openid,nickname',
			'xxt_wall_enroll',
			"siteid='$site' and wid='$id' and e.close_at=0 ",
		);
		if ($onlySpeaker === 'Y') {
			$q[2] .= ' and e.last_msg_at<>0';
		}

		$users = $this->model()->query_objs_ss($q);

		return new \ResponseData(count($users));
	}
	/**
	 * 将所有用户退出信息墙
	 */
	public function quit_action($id, $eid = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		if (empty($eid)) {
			/**
			 * 清除所有加入的人
			 */
			$rst = $this->model()->delete('xxt_wall_enroll', "wid='$id'");

			/**
			 *解除关联活动
			 */
			$this->model()->update(
				'xxt_wall',
				array('data_schemas' => '', 'source_app' => ''),
				"id='{$id}'"
			);
		} else {
			/**
			 * 清除某一个用户
			 */
			$rst = $this->model()->delete('xxt_wall_enroll', "wid='$id' and id=$eid ");
		}

		//记录操作日志
		$matter = $this->model('matter\wall')->byId($id, 'siteid,id,title,summary,pic');
		$matter->type = 'wall';
		$this->model('matter\log')->matterOp($matter->siteid, $user, $matter, 'quit');

		return new \ResponseData($rst);
	}
	/**
	 * 从关联活动同步数据
	 *
	 * 同步在最后一次同步之后的数据或已经删除的数据
	 */
	public function syncByApp_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$count = 0;
		$modelWall = $this->model('matter\wall');
		$app = $modelWall->byId($app);
		if (!empty($app->source_app)) {
			$sourceApp = json_decode($app->source_app);
			if ($sourceApp->type === 'enroll') {
				$count = $this->_syncByEnroll($site, $app, $sourceApp->id);
			} else if ($sourceApp->type === 'signin') {
				$count = $this->_syncBySignin($site, $app, $sourceApp->id);
			}
			// 更新同步时间
			$modelWall->update(
				'xxt_wall',
				array('last_sync_at' => time()),
				"id='{$app->id}'"
			);

			//记录操作日志
			$matter = $modelWall->byId($app->id, 'id,title,summary,pic');
			$matter->type = 'wall';
			$this->model('matter\log')->matterOp($site, $user, $matter, 'syncByApp');
		}

		return new \ResponseData($count);
	}
	/**
	 * 从记录活动导入数据
	 *
	 * 同步在最后一次同步之后的数据或已经删除的数据
	 */
	private function _syncByEnroll($siteId, &$objGrp, $byApp) {
		/* 获取变化的登记数据 */
		$modelRec = $this->model('matter\enroll\record');
		$q = array(
			'*,count(distinct userid)',
			'xxt_enroll_record',
			"aid='$byApp' and (enroll_at>{$objGrp->last_sync_at} or state<>1) and (wx_openid != '' or yx_openid != '' or qy_openid != '') group by userid",
		);
		$records = $modelRec->query_objs_ss($q);

		return $this->_syncRecord($siteId, $objGrp, $records, $modelRec);
	}
	/**
	 * 从签到活动导入数据
	 *
	 * 同步在最后一次同步之后的数据或已经删除的数据
	 */
	private function _syncBySignin($siteId, &$objGrp, $byApp) {
		/* 获取数据 */
		$modelRec = $this->model('matter\signin\record');
		$q = array(
			'*,count(distinct userid)',
			'xxt_signin_record',
			"aid='$byApp' and (enroll_at>{$objGrp->last_sync_at} or state<>1) and (wx_openid != '' or yx_openid != '' or qy_openid != '') group by userid",
		);
		$records = $modelRec->query_objs_ss($q);

		return $this->_syncRecord($siteId, $objGrp, $records, $modelRec);
	}
	/**
	 * 同步数据
	 */
	private function _syncRecord($siteId, &$objGrp, &$records, &$modelRec) {
		$sync_at = time();
		if (!empty($records)) {
			foreach ($records as $record) {
				if ($record->state === '1') {
					//退出其它信息墙
					$this->model()->update(
						'xxt_wall_enroll',
						array('close_at' => $sync_at),
						"siteid='$siteId' and wid != '{$objGrp->id}' and (wx_openid='{$record->wx_openid}' or yx_openid='{$record->yx_openid}' or qy_openid='{$record->qy_openid}') "
					);
					$options = array();
					$options['siteid'] = $siteId;
					$options['wid'] = $objGrp->id;
					$options['join_at'] = $sync_at;
					$options['userid'] = $record->userid;
					$options['nickname'] = $record->nickname;
					$options['wx_openid'] = $record->wx_openid;
					$options['yx_openid'] = $record->yx_openid;
					$options['qy_openid'] = $record->qy_openid;
					$options['headimgurl'] = $record->headimgurl;
					$options['enroll_key'] = $record->enroll_key;
					$options['data'] = $record->data;
					//查询用户是否已同步
					$q = [
						'enroll_key',
						'xxt_wall_enroll',
						"wid='{$objGrp->id}' and enroll_key='{$record->enroll_key}'",
					];
					$record = $this->model()->query_obj_ss($q);
					if ($record === false) {
						$this->model()->insert('xxt_wall_enroll', $options, false);
					}
				} else {
					// 删除用户
					$rst = $this->model()->delete(
						'xxt_wall_enroll',
						"wid='{$objGrp->id}' and enroll_key='{$record->enroll_key}'"
					);
				}
			}
		}

		return count($records);
	}
	/**
	 *手动导入用户
	 */
	public function importSns_action($site, $type, $page = 1, $size = 20) {
		$params = $this->getPostJson();
		$users = array();
		if (isset($params->dept) && !empty($params->dept) && $type === 'qy') {
			/**
			 *筛选导入的用户
			 */
			$name = $this->model()->escape($params->dept);
			$q = array(
				'fullpath',
				'xxt_site_member_department',
				"siteid = '$site' and name like '%" . $name . "%'",
			);
			// $total = 0;
			if ($depts = $this->model()->query_objs_ss($q)) {
				foreach ($depts as $dept) {
					$dept = explode(',', $dept->fullpath);
					$fullpath = json_encode($dept);
					// $result = $this->userList($site, $type, $page, $size, array('choose'=>$fullpath));
					$result = $this->userList($site, $type, 1, 1000, array('choose' => $fullpath));
					if ($result) {
						foreach ($result->users as $user) {
							$users['fans'][] = $user;
						}
						// $total += $result->total;
					}
				}
			}
			$users['choose'] = $name;
			// $users['total'] = $total;
		} else {
			$result = $this->userList($site, $type, $page, $size);
			if ($result) {
				$users['fans'] = $result->users;
				$users['total'] = $result->total;
			}
		}

		return new \ResponseData($users);
	}
	/**
	 *
	 */
	public function userList($site, $type, $page = 1, $size = 20, $options = []) {
		$result = new \stdClass;
		$q = array(
			'openid,nickname,headimgurl',
			'xxt_site_' . $type . 'fan',
			"siteid = '{$site}' and subscribe_at>0 and unsubscribe_at=0 and forbidden='N'",
		);
		$q2['o'] = 'subscribe_at';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;

		if ($type === 'qy') {
			$q[0] .= ",depts";
			$q2['o'] = 'depts';
		}
		//企业号部门筛选
		$choose = isset($options['choose']) ? $options['choose'] : '';
		if ($type === 'qy' && !empty($choose)) {
			$q[2] .= " and depts like '%" . $choose . "%'";
		}

		if ($users = $this->model()->query_objs_ss($q, $q2)) {
			if ($type === 'qy') {
				//加入部门信息
				foreach ($users as $user) {
					$depts = json_decode($user->depts);
					if (!empty($depts)) {
						$deptNames = array();
						foreach ($depts as $dept) {
							$dept2 = implode($dept, ',');
							$p = array(
								'name',
								'xxt_site_member_department',
								"siteid = '{$site}' and fullpath = '{$dept2}'",
							);
							$deptName = $this->model()->query_obj_ss($p);
							if ($deptName) {
								$deptNames[] = $deptName->name;
							}
						}
						$user->deptNames = implode($deptNames, ',');
					}
				}
			}
			$q[0] = 'count(*)';
			$total = (int) $this->model()->query_val_ss($q);
			$result->users = $users;
			$result->total = $total;
		} else {
			return $users;
		}

		return $result;
	}
	/**
	 * 将选中用户加入信息墙
	 */
	public function userJoin_action($site, $app, $type) {
		$params = $this->getPostJson();
		$user2 = new \stdClass;
		$modelSite = $this->model('site\user\account');
		$modelWall = $this->model('matter\wall');
		$joinReply = $modelWall->byId($app, 'join_reply');
		$num = 0;

		$yxProxy = $wxProxy = $qyProxy = null;
		foreach ($params as $user) {
			switch ($type) {
			case 'wx':
				$user2->wx_openid = $user->openid;
				break;
			case 'yx':
				$user2->yx_openid = $user->openid;
				break;
			case 'qy':
				$user2->qy_openid = $user->openid;
				break;
			}
			$user2->nickname = $user->nickname;
			$user2->headimgurl = $user->headimgurl;

			// if ($uid = $modelSite->byOpenid($site, $type, $user->openid, array('fields' => 'uid'))) {
			// 	$user2->userid = $uid->uid;
			// } else {
			// 	$user2->userid = '';
			// }
			// added by yangyue: 一个openid可能对应多个userid

			$user2->userid = '';
			//加入信息墙
			$reply = $this->model('matter\wall')->join($site, $app, $user2, 'import');
			if (false === $reply[0]) {
				return new \ResponseError($reply[1]);
			}
			if ($reply[1] === $joinReply->join_reply) {
				$num++;

				/*发送消息通知*/
				$message = array(
					"msgtype" => "text",
					"text" => array(
						"content" => $reply[1],
					),
				);
				if ($type === 'yx') {
					if ($yxProxy === null) {
						$yxConfig = $this->model('sns\yx')->bySite($site);
						if ($yxConfig && $yxConfig->joined === 'Y') {
							$yxProxy = $this->model('sns\yx\proxy', $yxConfig);
						} else {
							$yxProxy = false;
						}
					}
					if ($yxProxy !== false) {
						if ($yxConfig->can_p2p === 'Y') {
							$rst = $yxProxy->messageSend($message, array($user->openid));
						} else {
							$rst = $yxProxy->messageCustomSend($message, $user->openid);
						}
					}
				}
				if ($type === 'wx') {
					if ($wxProxy === null) {
						$wxConfig = $this->model('sns\wx')->bySite($site);
						if ($wxConfig && $wxConfig->joined === 'Y') {
							$wxProxy = $this->model('sns\wx\proxy', $wxConfig);
						} else {
							$wxProxy = false;
						}
					}
					if ($wxProxy !== false) {
						$rst = $wxProxy->messageCustomSend($message, $user->openid);
					}
				}
				if ($type === 'qy') {
					if ($qyProxy === null) {
						$qyConfig = $this->model('sns\qy')->bySite($site);
						if ($qyConfig && $qyConfig->joined === 'Y') {
							$qyProxy = $this->model('sns\qy\proxy', $qyConfig);
						} else {
							$qyProxy = false;
						}
					}
					if ($qyProxy !== false) {
						$message['touser'] = $user->openid;
						$rst = $qyProxy->messageSend($message, $user->openid);
					}
				}
			}
		}
		return new \ResponseData($num);
	}

}