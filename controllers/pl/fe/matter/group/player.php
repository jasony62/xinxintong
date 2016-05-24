<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 分组活动控制器
 */
class player extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/group/frame');
		exit;
	}
	/**
	 *
	 */
	public function list_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelGrp = $this->model('matter\group');
		$modelPlayer = $this->model('matter\group\player');

		$app = $modelGrp->byId($app);
		$result = $modelPlayer->find($site, $app);

		return new \ResponseData($result);
	}
	/**
	 * 分组用户数量
	 */
	public function count_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$q = array(
			'count(*)',
			"xxt_group_player",
			"siteid='$site' and aid='$app' and state=1",
		);

		$cnt = $this->model()->query_val_ss($q);

		return new \ResponseData($cnt);
	}
	/**
	 * 从其他活动导入数据
	 */
	public function importByApp_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		if (!empty($posted->app)) {
			if ($posted->appType === 'registration') {
				$sourceApp = $this->_importByEnroll($site, $app, $posted->app);
			} else if ($posted->appType === 'signin') {
				$sourceApp = $this->_importBySignin($site, $app, $posted->app);
			}
		}

		return new \ResponseData('ok');
	}
	/**
	 * 从关联活动同步数据
	 */
	public function syncByApp_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$count = 0;
		$modelGrp = $this->model('matter\group');
		$app = $modelGrp->byId($app, array('cascaded' => 'N'));
		if (!empty($app->source_app)) {
			$sourceApp = json_decode($app->source_app);
			if ($sourceApp->type === 'enroll') {
				$count = $this->_syncByEnroll($site, $app, $sourceApp->id);
			} else if ($sourceApp->type === 'signin') {
				$count = $this->_syncBySignin($site, $app, $sourceApp->id);
			}
			/* 更新同步时间 */
			$modelGrp->update(
				'xxt_group',
				array('last_sync_at' => time()),
				"id='{$app->id}'"
			);
		}

		return new \ResponseData($count);
	}
	/**
	 * 从登记活动导入数据
	 */
	private function &_importByEnroll($site, $app, $byApp, $sync = 'N') {
		$modelGrp = $this->model('matter\group');
		$modelPlayer = $this->model('matter\group\player');

		$sourceApp = $this->model('matter\enroll')->byId($byApp, array('fields' => 'data_schemas', 'cascaded' => 'N'));
		/* 导入活动定义 */
		$modelGrp->update(
			'xxt_group',
			array(
				'last_sync_at' => time(),
				'source_app' => '{"id":"' . $byApp . '","type":"enroll"}',
				'data_schemas' => $sourceApp->data_schemas,
			),
			"id='$app'"
		);
		/* 清空已有分组数据 */
		$modelPlayer->clean($app, true);
		/* 获取所有登记数据 */
		$modelRec = $this->model('matter\enroll\record');
		$q = array(
			'enroll_key',
			'xxt_enroll_record',
			"aid='$byApp' and state=1",
		);
		$eks = $modelRec->query_vals_ss($q);
		/* 导入数据 */
		if (!empty($eks)) {
			$objGrp = $modelGrp->byId($app, array('cascaded' => 'N'));
			$options = array('cascaded' => 'Y');
			foreach ($eks as $ek) {
				$record = $modelRec->byId($ek, $options);
				$user = new \stdClass;
				$user->uid = $record->userid;
				$user->nickname = $record->nickname;
				$modelPlayer->enroll($site, $objGrp, $user, array('enroll_key' => $ek, 'enroll_at' => $record->enroll_at));
				$modelPlayer->setData($user, $site, $objGrp, $ek, $record->data);
			}
		}

		return $sourceApp;
	}
	/**
	 * 从登记活动导入数据
	 */
	private function &_importBySignin($site, $app, $byApp, $sync = 'N') {
		$modelGrp = $this->model('matter\group');
		$modelPlayer = $this->model('matter\group\player');

		$sourceApp = $this->model('matter\signin')->byId($byApp, array('fields' => 'data_schemas', 'cascaded' => 'N'));
		/* 导入活动定义 */
		$modelGrp->update(
			'xxt_group',
			array(
				'last_sync_at' => time(),
				'source_app' => '{"id":"' . $byApp . '","type":"signin"}',
				'data_schemas' => $sourceApp->data_schemas,
			),
			"id='$app'"
		);
		/* 清空已有数据 */
		$modelPlayer->clean($app, true);
		/* 获取数据 */
		$modelRec = $this->model('matter\signin\record');
		$q = array(
			'enroll_key',
			'xxt_signin_record',
			"aid='$byApp' and state=1",
		);
		$eks = $modelRec->query_vals_ss($q);
		/* 导入数据 */
		if (!empty($eks)) {
			$objGrp = $modelGrp->byId($app, array('cascaded' => 'N'));
			$options = array('cascaded' => 'Y');
			foreach ($eks as $ek) {
				$record = $modelRec->byId($ek, $options);
				$user = new \stdClass;
				$user->uid = $record->userid;
				$user->nickname = $record->nickname;
				$modelPlayer->enroll($site, $objGrp, $user, array('enroll_key' => $ek, 'enroll_at' => $record->enroll_at));
				$modelPlayer->setData($user, $site, $objGrp, $ek, $record->data);
			}
		}

		return $sourceApp;
	}
	/**
	 * 从登记活动导入数据
	 */
	private function _syncByEnroll($siteId, &$objGrp, $byApp) {
		/* 获取变化的登记数据 */
		$modelRec = $this->model('matter\enroll\record');
		$q = array(
			'enroll_key,state',
			'xxt_enroll_record',
			"aid='$byApp' and enroll_at>{$objGrp->last_sync_at}",
		);
		$records = $modelRec->query_objs_ss($q);

		return $this->_syncRecord($siteId, $objGrp, $records, $modelRec);
	}
	/**
	 * 从签到活动导入数据
	 */
	private function _syncBySignin($siteId, &$objGrp, $byApp) {
		/* 获取数据 */
		$modelRec = $this->model('matter\signin\record');
		$q = array(
			'enroll_key,state',
			'xxt_signin_record',
			"aid='$byApp' and enroll_at>{$objGrp->last_sync_at}",
		);
		$records = $modelRec->query_objs_ss($q);

		return $this->_syncRecord($siteId, $objGrp, $records, $modelRec);
	}
	/**
	 * 同步数据
	 */
	private function _syncRecord($siteId, &$objGrp, &$records, &$modelRec) {
		$modelPlayer = $this->model('matter\group\player');
		if (!empty($records)) {
			$options = array('cascaded' => 'Y');
			foreach ($records as $record) {
				if ($record->state === '1') {
					$record = $modelRec->byId($record->enroll_key, $options);
					$user = new \stdClass;
					$user->uid = $record->userid;
					$user->nickname = $record->nickname;
					if ($modelPlayer->byId($record->enroll_key, array('cascaded' => 'N'))) {
						/*已经同步过的用户*/
						$modelPlayer->setData($user, $siteId, $objGrp, $record->enroll_key, $record->data);
					} else {
						/*新用户*/
						$modelPlayer->enroll($siteId, $objGrp, $user, array('enroll_key' => $record->enroll_key, 'enroll_at' => $record->enroll_at));
						$modelPlayer->setData($user, $siteId, $objGrp, $record->enroll_key, $record->data);
					}
				} else {
					/*删除用户*/
					$modelPlayer->remove($objGrp->id, $record->enroll_key, true);
				}
			}
		}

		return count($records);
	}
	/**
	 * 手工添加登记信息
	 *
	 * @param string $aid
	 */
	public function add_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$current = time();
		$modelPlayer = $this->model('matter\group\player');
		$ek = $modelPlayer->genKey($site, $app);
		/**
		 * 登记记录
		 */
		$player = array();
		$player['aid'] = $app;
		$player['siteid'] = $site;
		$player['enroll_key'] = $ek;
		$player['enroll_at'] = $current;
		if (!empty($posted->round_id)) {
			$modelRnd = $this->model('matter\group\round');
			$round = $modelRnd->byId($posted->round_id);
			$player['round_id'] = $posted->round_id;
			$player['round_title'] = $round->title;
		}
		$id = $modelPlayer->insert('xxt_group_player', $player, true);
		$player['id'] = $id;
		/**
		 * 登记数据
		 */
		if (isset($posted->data)) {
			foreach ($posted->data as $n => $v) {
				if (is_array($v) && isset($v[0]->imgSrc)) {
					/* 上传图片 */
					$vv = array();
					$fsuser = $this->model('fs/user', $site);
					foreach ($v as $img) {
						if (preg_match("/^data:.+base64/", $img->imgSrc)) {
							$rst = $fsuser->storeImg($img);
							if (false === $rst[0]) {
								return new \ResponseError($rst[1]);
							}
							$vv[] = $rst[1];
						} else {
							$vv[] = $img->imgSrc;
						}
					}
					$v = implode(',', $vv);
				} else if (is_string($v)) {
					$v = $modelPlayer->escape($v);
				} else if (is_object($v) || is_array($c = v)) {
					/*多选题*/
					$v = implode(',', array_keys(array_filter((array) $v, function ($i) {return $i;})));
				}
				$cd = array(
					'aid' => $app,
					'enroll_key' => $ek,
					'name' => $n,
					'value' => $v,
				);
				$modelPlayer->insert('xxt_group_player_data', $cd, false);
				$player['data'][$n] = $v;
			}
		}

		return new \ResponseData($player);
	}
	/**
	 * 更新登记记录
	 *
	 * @param string $site
	 * @param string $app
	 * @param $ek record's key
	 */
	public function update_action($site, $app, $ek) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$record = $this->getPostJson();
		$model = $this->model();

		foreach ($record as $k => $v) {
			if ($k === 'round_id') {
				if (empty($v)) {
					$model->update(
						'xxt_group_player',
						array(
							'round_id' => 0,
							'round_title' => '',
						),
						"aid='$app' and enroll_key='$ek'"
					);
					$record->round_title = '';
				} else {
					$modelRnd = $this->model('matter\group\round');
					if ($round = $modelRnd->byId($v)) {
						$model->update(
							'xxt_group_player',
							array(
								'round_id' => $v,
								'round_title' => $round->title,
							),
							"aid='$app' and enroll_key='$ek'"
						);
						$record->round_title = $round->title;
					}
				}
			} else if ($k === 'data' and is_object($v)) {
				foreach ($v as $cn => $cv) {
					if (is_array($cv) && isset($cv[0]->imgSrc)) {
						/* 上传图片 */
						$vv = array();
						$fsuser = $this->model('fs/user', $site);
						foreach ($cv as $img) {
							if (preg_match("/^data:.+base64/", $img->imgSrc)) {
								$rst = $fsuser->storeImg($img);
								if (false === $rst[0]) {
									return new \ResponseError($rst[1]);
								}
								$vv[] = $rst[1];
							} else {
								$vv[] = $img->imgSrc;
							}
						}
						$cv = implode(',', $vv);
					} else if (is_string($cv)) {
						$cv = $model->escape($cv);
					} else if (is_object($cv) || is_array($cv)) {
						/*多选题*/
						$cv = implode(',', array_keys(array_filter((array) $cv, function ($i) {return $i;})));
					}
					/*检查数据项是否存在，如果不存在就先创建一条*/
					$q = array(
						'count(*)',
						'xxt_group_player_data',
						"enroll_key='$ek' and name='$cn'",
					);
					if (1 === (int) $model->query_val_ss($q)) {
						$model->update(
							'xxt_group_player_data',
							array('value' => $cv),
							"enroll_key='$ek' and name='$cn'"
						);
					} else {
						$cd = array(
							'aid' => $app,
							'enroll_key' => $ek,
							'name' => $cn,
							'value' => $cv,
						);
						$model->insert('xxt_group_player_data', $cd, false);
					}
					$record->data->{$cn} = $cv;
				}
			}
		}

		return new \ResponseData($record);
	}
	/**
	 * 未分组的人
	 */
	public function pendingsGet_action($app, $rid = null) {
		$result = $this->model('matter\group\player')->pendings($app);

		return new \ResponseData($result);
	}
	/**
	 * 清空一条登记信息
	 */
	public function remove_action($site, $app, $ek, $keepData = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\group\player')->remove($app, $ek, $keepData === 'N');

		return new \ResponseData($rst);
	}
	/**
	 * 清空登记信息
	 */
	public function empty_action($site, $app, $keepData = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\group\player')->clean($app, $keepData === 'N');

		return new \ResponseData($rst);
	}
}