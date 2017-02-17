<?php
namespace site\op\matter\enroll;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 *
 */
class record extends \site\op\base {
	/**
	 *
	 */
	public function list_action($site, $app, $page = 1, $size = 30, $tags = null, $orderby = null) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		// 登记数据过滤条件
		$criteria = $this->getPostJson();
		//
		$options = array(
			'page' => $page,
			'size' => $size,
			'orderby' => $orderby,
		);

		$app = $this->model('matter\enroll')->byId($app);
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->find($site, $app, $options, $criteria);

		return new \ResponseData($result);
	}
	/**
	 * 更新登记记录
	 *
	 * @param string $app
	 * @param $ek record's key
	 */
	public function update_action($site, $app, $ek) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$record = $this->getPostJson();
		$model = $this->model();
		$current = time();

		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$schemas = json_decode($app->data_schemas);
		$schemasById = [];
		foreach ($schemas as $schema) {
			$schemasById[$schema->id] = $schema;
		}

		//
		$model->update(
			'xxt_enroll_record',
			['enroll_at' => $current],
			"enroll_key='$ek'"
		);
		foreach ($record as $k => $v) {
			if (in_array($k, ['verified', 'tags', 'comment'])) {
				$model->update(
					'xxt_enroll_record',
					[$k => $v],
					"enroll_key='$ek'"
				);
				// 更新记录的标签时，要同步更新活动的标签，实现标签在整个活动中有效
				if ($k === 'tags') {
					$this->model('matter\enroll')->updateTags($app->id, $v);
				}
				if ($k === 'verified' && $v === 'Y') {
					$this->_whenVerifyRecord($app, $ek);
				}
			} else if ($k === 'data' and is_object($v)) {
				$dbData = new \stdClass;
				foreach ($v as $cn => $cv) {
					$schema = $schemasById[$cn];
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
						$dbData->{$cn} = $cv;
					} else if ($schema->type === 'score') {
						$dbData->{$cn} = $cv;
						$cv = json_encode($cv);
					} else if (is_string($cv)) {
						$cv = $model->escape($cv);
						$dbData->{$cn} = $cv;
					} else if (is_object($cv) || is_array($cv)) {
						/*多选题*/
						$cv = implode(',', array_keys(array_filter((array) $cv, function ($i) {return $i;})));
						$dbData->{$cn} = $cv;
					}
					/*检查数据项是否存在，如果不存在就先创建一条*/
					$q = array(
						'count(*)',
						'xxt_enroll_record_data',
						"enroll_key='$ek' and name='$cn'",
					);
					if (1 === (int) $model->query_val_ss($q)) {
						$model->update(
							'xxt_enroll_record_data',
							array('value' => $cv),
							"enroll_key='$ek' and name='$cn'"
						);
					} else {
						$cd = [
							'aid' => $app->id,
							'enroll_key' => $ek,
							'name' => $cn,
							'value' => $cv,
						];
						$model->insert('xxt_enroll_record_data', $cd, false);
					}
				}
				//
				$record->data = $dbData;
				$dbData = $model->toJson($dbData);
				$model->update(
					'xxt_enroll_record',
					['data' => $dbData],
					"enroll_key='$ek'"
				);
			}
		}
		// 记录操作日志
		//$app->type = 'enroll';
		//$this->model('matter\log')->matterOp($site, $user, $app, 'update', $record);

		return new \ResponseData($record);
	}
	/**
	 * 验证通过时，如果登记记录有对应的签到记录，且签到记录没有验证通过，那么验证通过
	 */
	private function _whenVerifyRecord(&$app, $enrollKey) {
		if ($app->mission_id) {
			$model = $this->model('matter\signin\record');
			$q = [
				'id',
				'xxt_signin',
				"enroll_app_id='{$app->id}'",
			];
			$signinApps = $model->query_objs_ss($q);
			if (count($signinApps)) {
				$enrollRecord = $this->model('matter\enroll\record')->byId(
					$enrollKey, ['fields' => 'userid,data', 'cascaded' => 'N']
				);
				if (!empty($enrollRecord->data)) {
					$enrollData = json_decode($enrollRecord->data);
					foreach ($signinApps as $signinApp) {
						// 更新对应的签到记录
						$q = [
							'*',
							'xxt_signin_record',
							"state=1 and verified='N' and aid='$signinApp->id' and userid='$enrollRecord->userid'",
						];
						$signinRecords = $model->query_objs_ss($q);
						if (count($signinRecords)) {
							foreach ($signinRecords as $signinRecord) {
								if (empty($signinRecord->data)) {
									continue;
								}
								$signinData = json_decode($signinRecord->data);
								if ($signinData === null) {
									$signinData = new \stdClass;
								}
								foreach ($enrollData as $k => $v) {
									$signinData->{$k} = $v;
								}
								// 更新数据
								$model->delete('xxt_signin_record_data', "enroll_key='$signinRecord->enroll_key'");
								foreach ($signinData as $k => $v) {
									$ic = [
										'aid' => $app->id,
										'enroll_key' => $signinRecord->enroll_key,
										'name' => $k,
										'value' => $v,
									];
									$model->insert('xxt_signin_record_data', $ic, false);
								}
								// 验证通过
								$model->update(
									'xxt_signin_record',
									[
										'verified' => 'Y',
										'verified_enroll_key' => $enrollKey,
										'data' => $model->toJson($signinData),
									],
									"enroll_key='$signinRecord->enroll_key'"
								);
							}
						}
					}
				}
			}
		}

		return false;
	}
	/**
	 * 指定记录通过审核
	 */
	public function batchVerify_action($site, $app) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$posted = $this->getPostJson();
		$eks = $posted->eks;

		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);

		$model = $this->model();
		foreach ($eks as $ek) {
			$rst = $model->update(
				'xxt_enroll_record',
				['verified' => 'Y'],
				"enroll_key='$ek'"
			);
			// 进行后续处理
			$this->_whenVerifyRecord($app, $ek);
		}

		// 记录操作日志
		//$app->type = 'enroll';
		//$this->model('matter\log')->matterOp($site, $user, $app, 'verify.batch', $eks);

		return new \ResponseData('ok');
	}
	/**
	 * 返回指定登记项的活动登记名单
	 *
	 */
	public function list4Schema_action($site, $app, $schema, $page = 1, $size = 10) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		// 登记数据过滤条件
		$criteria = $this->getPostJson();

		// 登记记录过滤条件
		$options = [
			'page' => $page,
			'size' => $size,
		];

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$enrollApp = $modelApp->byId($app);

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->list4Schema($site, $enrollApp, $schema, $options);

		return new \ResponseData($result);
	}
	/**
	 * 删除一条登记信息
	 */
	public function remove_action($site, $app, $ek) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$rst = $this->model('matter\enroll\record')->remove($app, $ek);

		// 记录操作日志
		// $app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		// $app->type = 'enroll';
		// $this->model('matter\log')->matterOp($site, $user, $app, 'remove', $key);

		return new \ResponseData($rst);
	}
}