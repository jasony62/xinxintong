<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记记录
 */
class record extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 活动报名名单
	 *
	 * 1、如果活动仅限会员报名，那么要叠加会员信息
	 * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 */
	public function list_action($site, $app, $page = 1, $size = 30, $rid = null, $orderby = null, $contain = null) {
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

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$app = $modelApp->byId($app);

		// 查询结果
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->find($site, $app, $options, $criteria);

		return new \ResponseData($result);
	}
	/**
	 * 登记情况汇总信息
	 */
	public function summary_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$mdoelRec = $this->model('matter\enroll\record');
		$summary = $mdoelRec->summary($site, $app);

		return new \ResponseData($summary);
	}
	/**
	 * 手工添加登记信息
	 *
	 * @param string $app
	 */
	public function add_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$current = time();
		$modelRec = $this->model('matter\enroll\record');
		$ek = $modelRec->genKey($site, $app);

		$r = array();
		$r['aid'] = $app;
		$r['siteid'] = $site;
		$r['enroll_key'] = $ek;
		$r['enroll_at'] = $current;
		$r['verified'] = isset($posted->verified) ? $posted->verified : 'N';
		$r['comment'] = isset($posted->comment) ? $posted->comment : '';
		if (isset($posted->tags)) {
			$r['tags'] = $posted->tags;
			$this->model('matter\enroll')->updateTags($app, $posted->tags);
		}
		$id = $modelRec->insert('xxt_enroll_record', $r, true);
		$r['id'] = $id;
		/**
		 * 登记数据
		 */
		if (isset($posted->data)) {
			$dbData = new \stdClass;
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
					//
					$dbData->{$n} = $v;
				} else if (is_string($v)) {
					$v = $modelRec->escape($v);
					//
					$dbData->{$n} = $v;
				} else if (is_object($v) || is_array($c = v)) {
					/*多选题*/
					$v = implode(',', array_keys(array_filter((array) $v, function ($i) {return $i;})));
					//
					$dbData->{$n} = $v;
				}
				$cd = array(
					'aid' => $app,
					'enroll_key' => $ek,
					'name' => $n,
					'value' => $v,
				);
				$modelRec->insert('xxt_enroll_record_data', $cd, false);
				$r['data'][$n] = $v;
			}
			//
			$dbData = $modelRec->toJson($dbData);
			$modelRec->update('xxt_enroll_record', ['data' => $dbData], "enroll_key='$ek'");
		}

		// 记录操作日志
		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'add', $ek);

		return new \ResponseData($r);
	}
	/**
	 * 删除一条登记信息
	 */
	public function remove_action($site, $app, $key) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\enroll\record')->remove($app, $key);

		// 记录操作日志
		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'remvoe', $key);

		return new \ResponseData($rst);
	}
	/**
	 * 清空登记信息
	 */
	public function empty_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\enroll\record')->clean($app);

		// 记录操作日志
		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'empty');

		return new \ResponseData($rst);
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
		$model = $this->model();
		$current = time();

		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
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
					$record->data->{$cn} = $cv;
				}
				//
				$dbData = $model->toJson($dbData);
				$model->update(
					'xxt_enroll_record',
					['data' => $dbData],
					"enroll_key='$ek'"
				);
			}
		}
		// 记录操作日志
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'update', $record);

		return new \ResponseData($record);
	}
	/**
	 * 所有记录通过审核
	 */
	public function verifyAll_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);

		$rst = $this->model()->update(
			'xxt_enroll_record',
			['verified' => 'Y'],
			"aid='{$app->id}'"
		);

		// 记录操作日志
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'verify.all');

		return new \ResponseData($rst);
	}
	/**
	 * 指定记录通过审核
	 */
	public function batchVerify_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
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
		$app->type = 'enroll';
		$this->model('matter\log')->matterOp($site, $user, $app, 'verify.batch', $eks);

		return new \ResponseData('ok');
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
	 * 给登记活动的参与人发消息
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $tmplmsg
	 *
	 */
	public function notify_action($site, $app, $tmplmsg, $rid = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$site = \TMS_MODEL::escape($site);
		$app = \TMS_MODEL::escape($app);
		$posted = $this->getPostJson();
		$message = $posted->message;

		// 记录筛选条件
		$criteria = $posted->criteria;
		$options = [
			'rid' => $rid,
		];

		$participants = $this->model('matter\enroll')->participants($site, $app, $options, $criteria);

		$rst = $this->notifyWithMatter($site, $participants, $tmplmsg, $message);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		return new \ResponseData($participants);
	}
	/**
	 * 给用户发送素材
	 */
	protected function notifyWithMatter($siteId, &$userIds, $tmplmsgId, &$message) {
		if (count($userIds)) {
			$mapOfUsers = new \stdClass;
			$modelAcnt = $this->model('site\user\account');
			$modelWxfan = $modelYxfan = $modelQyfan = false;

			// 微信可以使用平台的公众号
			$wxSiteId = false;

			foreach ($userIds as $userid) {
				$user = $modelAcnt->byId($userid, ['fields' => 'ufrom,wx_openid,yx_openid,qy_openid']);
				if (!isset($mapOfUsers->{$userid})) {
					$mapOfUsers->{$userid} = $user;
					switch ($user->ufrom) {
					case 'wx':
						if ($wxSiteId === false) {
							$modelSns = $this->model('sns\wx');
							$wxConfig = $modelSns->bySite($siteId);
							if ($wxConfig === false || $wxConfig->joined !== 'Y') {
								$wxSiteId = 'platform';
							} else {
								$wxSiteId = $siteId;
							}
						}
						// 用模板消息发送
						$rst = $this->tmplmsgSendByOpenid($tmplmsgId, $user->wx_openid, $message);
						if ($rst[0] === false) {
							return $rst;
						}
						break;
					case 'yx':
						// 如果开放了点对点消息，用点对点消息发送
						break;
					case 'qy':
						// 点对点发送
						break;
					}
				}
			}
		}

		return array(true);
	}
	/**
	 * 登记数据导出
	 */
	public function export_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$app = $this->model('matter\enroll')->byId($app, ['fields' => 'id,title,data_schemas,scenario', 'cascaded' => 'N']);
		$schemas = json_decode($app->data_schemas);

		// 获得所有有效的登记记录
		$records = $this->model('matter\enroll\record')->find($site, $app);
		if ($records->total === 0) {
			die('record empty');
		}
		$records = $records->records;

		// 登记记录转换成下载数据
		$exportedData = [];
		$size = 0;
		// 转换标题
		$titles = ['登记时间', '审核通过'];
		foreach ($schemas as $schema) {
			$titles[] = $schema->title;
		}
		// 记录分数
		if ($app->scenario === 'voting') {
			$titles[] = '总分数';
			$titles[] = '平均分数';
		}
		$titles = implode("\t", $titles);
		$size += strlen($titles);
		$exportedData[] = $titles;
		// 转换数据
		foreach ($records as $record) {
			$row = [];
			$row[] = date('y-m-j H:i', $record->enroll_at);
			$row[] = $record->verified;
			// 处理登记项
			$data = $record->data;
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
			// 记录分数
			if ($app->scenario === 'voting') {
				$row[] = $record->_score;
				$row[] = sprintf('%.2f', $record->_average);
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