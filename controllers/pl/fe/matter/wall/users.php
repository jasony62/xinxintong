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
			'wx_openid,yx_openid,qy_openid,join_at,last_msg_at,msg_num,userid,nickname',
			'xxt_wall_enroll',
			"siteid='$site' and wid='$id' and close_at=0",
		);
		$users = $this->model()->query_objs_ss($q);

		return new \ResponseData($users);
	}
	/**
	 * 从登记活动和签到活动导入用户
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
	 * 从登记活动导入数据
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
				//退出其它讨论组
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

				$this->model()->insert('xxt_wall_enroll',$options,false);
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

		$sourceApp = $modelSignin->byId($byApp, ['fields' => 'data_schemas,enroll_app_id', 'cascaded' => 'N']);
		$sourceDataSchemas = $sourceApp->data_schemas;
		/**
		 * 导入报名数据，需要合并签到和报名的登记项
		 */
		if (isset($params->includeEnroll) && $params->includeEnroll === 'Y') {
			if (!empty($sourceApp->enroll_app_id)) {
				$modelEnl = $this->model('matter\enroll');
				$enrollApp = $modelEnl->byId($sourceApp->enroll_app_id, ['fields' => 'data_schemas', 'cascaded' => 'N']);
				$enrollDataSchemas = json_decode($enrollApp->data_schemas);
				$sourceDataSchemas = json_decode($sourceDataSchemas);
				$diff = array_udiff($enrollDataSchemas, $sourceDataSchemas, create_function('$a,$b', 'return strcmp($a->id,$b->id);'));
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
			"id='$app'"
		);
		/* 清空所有用户 */
		$this->model()->delete('xxt_wall_enroll', "wid='$app'");
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
			foreach ($records as $record) {
				//退出其它讨论组
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

				$this->model()->insert('xxt_wall_enroll',$options,false);
			}
		}
		$num = count($records);
		return $num;
	}
	/**
	 * 用户导出到登记活动
	 *
	 * @param string $wall
	 * @param string $app
	 */
	public function export_action($id, $app, $onlySpeaker = 'N',$site) {

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
	public function quit_action($id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/**
		 * 清除所有加入的人
		 */
		$rst = $this->model()->delete('xxt_wall_enroll', "wid='$id'");
		
		/**
		*解除关联活动
		*/
		$this->model()->update(
				'xxt_wall',
				array('data_schemas' => '','source_app' => ''),
				"id='{$id}'"
			);

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
	 * 从登记活动导入数据
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
					//退出其它讨论组
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
						$this->model()->insert('xxt_wall_enroll',$options,false);
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

}