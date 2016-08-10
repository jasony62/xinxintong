<?php
namespace pl\fe\matter\signin;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 *
 */
class record extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/signin/frame');
		exit;
	}
	/**
	 * 活动登记名单
	 *
	 */
	public function list_action($site, $app, $page = 1, $size = 30, $signinStartAt = null, $signinEndAt = null, $rid = null, $orderby = null, $contain = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记数据过滤条件
		$criteria = $this->getPostJson();

		/*应用*/
		$modelApp = $this->model('matter\signin');
		$app = $modelApp->byId($app);
		/*参数*/
		$options = [
			'page' => $page,
			'size' => $size,
			'signinStartAt' => $signinStartAt,
			'signinEndAt' => $signinEndAt,
			'orderby' => $orderby,
			'contain' => $contain,
		];
		!empty($rid) && $rid !== 'ALL' && $options['rid'] = $rid;

		$mdoelRec = $this->model('matter\signin\record');
		$result = $mdoelRec->find($site, $app, $options, $criteria);

		return new \ResponseData($result);
	}
	/**
	 * 关联的报名名单
	 */
	public function listByEnroll_action($site, $app, $page = 1, $size = 30, $rid = null, $orderby = null, $contain = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		// 登记数据过滤条件
		$criteria = $this->getPostJson();

		// 登记记录过滤条件
		$options = array(
			'page' => $page,
			'size' => $size,
			'rid' => $rid,
			'orderby' => $orderby,
			'contain' => $contain,
		);

		// 签到应用
		$modelApp = $this->model('matter\signin');
		$app = $modelApp->byId($app);

		if (empty($app->enroll_app_id)) {
			return new \ResponseError('参数错误，没有指定关联的报名活动');
		}
		// 和签到在同一个项目阶段的报名
		if (!empty($app->mission_phase_id)) {
			if (!isset($criteria->data)) {
				$criteria->data = new \stdClass;
			}
			$criteria->data->phase = $app->mission_phase_id;
		}
		// 查询结果
		$enrollApp = $this->model('matter\enroll')->byId($app->enroll_app_id);
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->find($site, $enrollApp, $options, $criteria);

		if ($result->total > 0) {
			foreach ($result->records as &$record) {
				$q = [
					'enroll_at,signin_at,signin_num,data',
					'xxt_signin_record',
					"aid='{$app->id}' and verified_enroll_key='$record->enroll_key'",
				];
				if ($signinRecord = $modelApp->query_obj_ss($q)) {
					$signinRecord->data = json_decode($signinRecord->data);
					$record->_signinRecord = $signinRecord;
				}
			}
		}

		return new \ResponseData($result);
	}
	/**
	 * 登记情况汇总信息
	 */
	public function summary_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$mdoelRec = $this->model('matter\signin\record');
		$summary = $mdoelRec->summary($site, $app);

		return new \ResponseData($summary);
	}
	/**
	 * 将数据导出到另一个活动
	 */
	public function exportByData_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$posted = $this->getPostJson();
		$filter = $posted->filter;
		$target = $posted->target;
		$includeData = isset($posted->includeData) ? $posted->includeData : 'N';

		if (!empty($target)) {
			/*更新应用标签*/
			$modelApp = $this->model('matter\signin');
			/*给符合条件的记录打标签*/
			$modelRec = $this->model('matter\signin\record');
			$q = [
				'distinct enroll_key',
				'xxt_signin_record_data',
				"aid='$app' and state=1",
			];
			$eks = null;
			foreach ($filter as $k => $v) {
				$w = "(name='$k' and ";
				$w .= "concat(',',value,',') like '%,$v,%'";
				$w .= ')';
				$q2 = $q;
				$q2[2] .= ' and ' . $w;
				$eks2 = $modelRec->query_vals_ss($q2);
				$eks = ($eks === null) ? $eks2 : array_intersect($eks, $eks2);
			}
			if (!empty($eks)) {
				$objApp = $modelApp->byId($target, ['cascade' => 'N']);
				$options = ['cascaded' => $includeData];
				foreach ($eks as $ek) {
					$record = $modelRec->byId($ek, $options);
					$user = new \stdClass;
					$user->nickname = $record->nickname;
					$newek = $modelRec->add($site, $objApp, $user);
					if ($includeData === 'Y') {
						$modelRec->setData($user, $site, $objApp, $newek, $record->data);
					}
				}
			}
		}

		return new \ResponseData('ok');
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
		$modelRec = $this->model('matter\signin\record');
		$ek = $modelRec->genKey($site, $app);

		$r = [];
		$r['aid'] = $app;
		$r['siteid'] = $site;
		$r['enroll_key'] = $ek;
		$r['enroll_at'] = $current;
		$r['signin_at'] = $current;
		if (isset($posted->verified)) {
			$r['verified'] = $posted->verified;
		}
		if (isset($posted->comment)) {
			$r['comment'] = $posted->comment;
		}
		if (isset($posted->tags)) {
			$r['tags'] = $posted->tags;
			$this->model('matter\signin')->updateTags($app, $posted->tags);
		}
		$id = $modelRec->insert('xxt_signin_record', $r, true);
		$r['id'] = $id;
		/**
		 * 登记数据
		 */
		if (isset($posted->data)) {
			$dbData = new \stdClass;
			foreach ($posted->data as $n => $v) {
				if (is_array($v) && isset($v[0]->imgSrc)) {
					/* 上传图片 */
					$vv = [];
					$fsuser = $this->model('fs/user', $site);
					foreach ($v as $img) {
						if (preg_match('/^data:.+base64/', $img->imgSrc)) {
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
					//
					$dbData->{$n} = $v;
				} elseif (is_string($v)) {
					$v = $modelRec->escape($v);
					//
					$dbData->{$n} = $v;
				} elseif (is_object($v) || is_array($c = v)) {
					/*多选题*/
					$v = implode(',', array_keys(array_filter((array) $v, function ($i) {return $i;})));
					//
					$dbData->{$n} = $v;
				}
				// 记录数据
				$cd = [
					'aid' => $app,
					'enroll_key' => $ek,
					'name' => $n,
					'value' => $v,
				];
				$modelRec->insert('xxt_signin_record_data', $cd, false);
				$r['data'][$n] = $v;
			}
			// 记录数据
			$dbData = $modelRec->toJson($dbData);
			$modelRec->update('xxt_signin_record', ['data' => $dbData], "enroll_key='$ek'");
		}

		return new \ResponseData($r);
	}
	/**
	 * 更新登记记录
	 *
	 * @param string $app
	 * @param $ek record's key
	 */
	public function update_action($site, $app, $ek) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$record = $this->getPostJson();
		$modelRec = $this->model('matter\signin\record');

		foreach ($record as $k => $v) {
			if (in_array($k, ['signin_at', 'verified', 'tags', 'comment'])) {
				$modelRec->update(
					'xxt_signin_record',
					[$k => $v],
					"enroll_key='$ek'"
				);
				if ($k === 'tags') {
					// 更新记录的标签时，要同步更新活动的标签，实现标签在整个活动中有效
					$this->model('matter\signin')->updateTags($app, $v);
				} else if ($k === 'verified' && $v === 'N') {
					// 如果不通过验证，去掉关联的报名数据
					$modelRec->update(
						'xxt_signin_record',
						['verified_enroll_key' => ''],
						"enroll_key='$ek'"
					);
				}
			} elseif ($k === 'data' and is_object($v)) {
				$dbData = new \stdClass;
				foreach ($v as $cn => $cv) {
					if (is_array($cv) && isset($cv[0]->imgSrc)) {
						//上传图片
						$vv = [];
						$fsuser = $this->model('fs/user', $site);
						foreach ($cv as $img) {
							if (preg_match('/^data:.+base64/', $img->imgSrc)) {
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
					} elseif (is_object($cv) || is_array($cv)) {
						// 多选题
						$cv = implode(',', array_keys(array_filter((array) $cv, function ($i) {return $i;})));
						$dbData->{$cn} = $cv;
					} elseif (is_string($cv)) {
						$cv = $modelRec->escape($cv);
						$dbData->{$cn} = $cv;
					}
					/*检查数据项是否存在，如果不存在就先创建一条*/
					$q = [
						'count(*)',
						'xxt_signin_record_data',
						"enroll_key='$ek' and name='$cn'",
					];
					if (1 === (int) $modelRec->query_val_ss($q)) {
						$modelRec->update(
							'xxt_signin_record_data',
							['value' => $cv],
							"enroll_key='$ek' and name='$cn'"
						);
					} else {
						$cd = [
							'aid' => $app,
							'enroll_key' => $ek,
							'name' => $cn,
							'value' => $cv,
						];
						$modelRec->insert('xxt_signin_record_data', $cd, false);
					}
					$record->data->{$cn} = $cv;
				}
				// 记录数据
				$dbData = $modelRec->toJson($dbData);
				$modelRec->update('xxt_signin_record', ['data' => $dbData], "enroll_key='$ek'");
			}
		}

		return new \ResponseData($record);
	}
	/**
	 * 清空一条登记信息
	 */
	public function remove_action($site, $app, $key, $keepData = 'N') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$rst = $this->model('matter\signin\record')->remove($app, $key, $keepData !== 'Y');

		return new \ResponseData($rst);
	}
	/**
	 * 清空登记信息
	 */
	public function empty_action($site, $app, $keepData = 'N') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\signin\record')->clean($app, $keepData !== 'Y');

		return new \ResponseData($rst);
	}
	/**
	 * 登记数据导出
	 *
	 * 如果活动关联了报名活动，需要将关联的数据导出
	 */
	public function export_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$app = $this->model('matter\signin')->byId($app, ['fields' => 'id,title,data_schemas,enroll_app_id,tags', 'cascaded' => 'N']);
		$schemas = json_decode($app->data_schemas);

		// 关联的报名活动
		if (!empty($app->enroll_app_id)) {
			$enrollApp = $this->model('matter\enroll')->byId($app->enroll_app_id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
			$enrollSchemas = json_decode($enrollApp->data_schemas);
			$mapOfSigninSchemas = [];
			foreach ($schemas as $schema) {
				$mapOfSigninSchemas[] = $schema->id;
			}
			foreach ($enrollSchemas as $schema) {
				if (!in_array($schema->id, $mapOfSigninSchemas)) {
					$schemas[] = $schema;
				}
			}
		}

		// 获得所有有效的登记记录
		$q = [
			'enroll_at,signin_at,signin_num,verified,data,tags,comment',
			'xxt_signin_record',
			["aid" => $app->id, 'state' => 1],
		];
		$records = $this->model()->query_objs_ss($q);
		if (count($records) === 0) {
			die('record empty');
		}

		// 登记记录转换成下载数据
		$exportedData = [];
		$size = 0;
		// 转换标题
		$titles = ['登记时间', '最后签到时间', '签到次数', '审核通过'];
		foreach ($schemas as $schema) {
			$titles[] = $schema->title;
		}
		if (!empty($app->tags)) {
			$titles[] = '标签';
		}
		$titles[] = '备注';
		$titles = implode("\t", $titles);
		$size += strlen($titles);
		$exportedData[] = $titles;
		// 转换数据
		foreach ($records as $record) {
			$row = [];
			// 基本信息
			$row[] = date('y-m-j H:i', $record->enroll_at);
			$row[] = date('y-m-j H:i', $record->signin_at);
			$row[] = $record->signin_num;
			$row[] = $record->verified;
			// 处理登记项
			$data = str_replace("\n", ' ', $record->data);
			$data = json_decode($record->data);
			foreach ($schemas as $schema) {
				$v = isset($data->{$schema->id}) ? $data->{$schema->id} : '';
				switch ($schema->type) {
				case 'single':
				case 'phase':
					foreach ($schema->ops as $op) {
						if ($op->v === $v) {
							$row[] = $op->l;
							$disposed = true;
							break;
						}
					}
					empty($disposed) && $row[] = $v;
					break;
				case 'multiple':
					$labels = [];
					$v = explode(',', $v);
					foreach ($v as $oneV) {
						foreach ($schema->ops as $op) {
							if ($op->v === $oneV) {
								$labels[] = $op->l;
								break;
							}
						}
					}
					$row[] = implode(',', $labels);
					break;
				default:
					$row[] = $v;
					break;
				}
			}
			// 基本信息
			if (!empty($app->tags)) {
				$row[] = isset($record->tags) ? $record->tags : '';
			}
			$row[] = isset($record->comment) ? $record->comment : '';

			// 将数据转换为'|'分隔的字符串
			$row = implode("\t", $row);
			$size += strlen($row);
			$exportedData[] = $row;
		}

		// 文件下载
		$size += (count($exportedData) - 1) * 2;
		$exportedData = implode("\r\n", $exportedData);

		//header("Content-Type: text/plain;charset=utf-8");
		//header("Content-Disposition: attachment; filename=" . $app->title . '.txt');
		//header('Content-Length: ' . $size);
		//echo $exportedData;
		//exit;

		return new \ResponseData($exportedData);
	}
	/**
	 * 登记数据导出
	 */
	public function exportByEnroll_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 签到应用
		$modelApp = $this->model('matter\signin');
		$app = $modelApp->byId($app);

		if (empty($app->enroll_app_id)) {
			return new \ResponseError('参数错误，没有指定关联的报名活动');
		}

		// 登记应用
		$enrollApp = $this->model('matter\enroll')->byId($app->enroll_app_id, ['fields' => 'id,title,data_schemas', 'cascaded' => 'N']);
		$schemas = json_decode($enrollApp->data_schemas);

		// 获得所有有效的登记记录
		$q = [
			'enroll_key,enroll_at,verified,data',
			'xxt_enroll_record',
			["aid" => $enrollApp->id, 'state' => 1],
		];
		$records = $this->model()->query_objs_ss($q);
		if (count($records) === 0) {
			die('record empty');
		}

		// 登记记录转换成下载数据
		$exportedData = [];
		$size = 0;

		// 转换标题
		$titles = ['登记时间', '审核通过'];
		foreach ($schemas as $schema) {
			$titles[] = $schema->title;
		}
		$titles[] = '签到时间';
		$titles[] = '签到次数';
		$titles = implode("\t", $titles);
		$size += strlen($titles);
		$exportedData[] = $titles;

		// 转换数据
		foreach ($records as $record) {
			$row = [];
			$row[] = date('y-m-j H:i', $record->enroll_at);
			$row[] = $record->verified;
			// 处理登记项
			$data = str_replace("\n", ' ', $record->data);
			$data = json_decode($record->data);
			foreach ($schemas as $schema) {
				$v = isset($data->{$schema->id}) ? $data->{$schema->id} : '';
				switch ($schema->type) {
				case 'single':
				case 'phase':
					foreach ($schema->ops as $op) {
						if ($op->v === $v) {
							$row[] = $op->l;
							$disposed = true;
							break;
						}
					}
					empty($disposed) && $row[] = $v;
					break;
				case 'multiple':
					$labels = [];
					$v = explode(',', $v);
					foreach ($v as $oneV) {
						foreach ($schema->ops as $op) {
							if ($op->v === $oneV) {
								$labels[] = $op->l;
								break;
							}
						}
					}
					$row[] = implode(',', $labels);
					break;
				default:
					$row[] = $v;
					break;
				}
			}

			// 获得对应的签到数据
			$q = [
				'enroll_at,signin_at,signin_num,data',
				'xxt_signin_record',
				"aid='{$app->id}' and verified_enroll_key='$record->enroll_key'",
			];
			if ($signinRecord = $modelApp->query_obj_ss($q)) {
				$row[] = date('y-m-j H:i', $signinRecord->signin_at);
				$row[] = $signinRecord->signin_num;
			} else {
				$row[] = '';
				$row[] = '';
			}

			// 将数据转换为'|'分隔的字符串
			$row = implode("\t", $row);
			$size += strlen($row);
			$exportedData[] = $row;
		}

		// 文件下载
		$size += (count($exportedData) - 1) * 2;
		$exportedData = implode("\r\n", $exportedData);

		//header("Content-Type: text/plain;charset=utf-8");
		//header("Content-Disposition: attachment; filename=" . $app->title . '.txt');
		//header('Content-Length: ' . $size);
		//echo $exportedData;
		//exit;

		return new \ResponseData($exportedData);
	}
}