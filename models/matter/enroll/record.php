<?php
namespace matter\enroll;
/**
 * 登记活动记录
 */
class record_model extends \TMS_MODEL {
	/**
	 * 活动登记（不包括登记数据）
	 *
	 * @param string $siteId
	 * @param string $app
	 * @param object $user
	 * @param int $enrollAt
	 * @param string $referrer
	 */
	public function enroll($siteId, &$app, $user = null, $options = []) {

		$referrer = isset($options['referrer']) ? $options['referrer'] : '';
		$enrollAt = isset($options['enrollAt']) ? $options['enrollAt'] : time();

		$ek = $this->genKey($siteId, $app->id);

		$record = [
			'aid' => $app->id,
			'siteid' => $siteId,
			'mpid' => $siteId,
			'enroll_at' => $enrollAt,
			'first_enroll_at' => $enrollAt,
			'enroll_key' => $ek,
			'userid' => empty($user->uid) ? '' : $user->uid,
			'referrer' => $referrer,
		];
		/* 记录所属轮次 */
		$modelRun = \TMS_APP::M('matter\enroll\round');
		if ($activeRound = $modelRun->getActive($siteId, $app->id)) {
			$record['rid'] = $activeRound->rid;
		}

		/* 登记用户昵称 */
		$entryRule = $app->entry_rule;
		if (isset($entryRule->scope) && $entryRule->scope === 'member') {
			foreach ($entryRule->member as $schemaId => $rule) {
				if (isset($user->members->{$schemaId})) {
					$record['nickname'] = $user->members->{$schemaId}->name;
					break;
				}
			}
		} else if (isset($entryRule->scope) && $entryRule->scope === 'sns') {
			foreach ($entryRule->sns as $snsName => $rule) {
				if (isset($user->sns->{$snsName})) {
					$record['nickname'] = $this->escape($user->sns->{$snsName}->nickname);
					break;
				}
			}
		} else if (empty($entryRule->scope) || $entryRule->scope === 'none') {
			/* 不限制用户访问来源 */
			$record['nickname'] = empty($user->nickname) ? '' : $this->escape($user->nickname);
		} else {
			/* 匿名访问 */
			$record['nickname'] = '';
		}

		$this->insert('xxt_enroll_record', $record, false);

		return $ek;
	}
	/**
	 * 保存登记的数据
	 */
	public function setData($user, $siteId, &$app, $ek, $data, $submitkey = '') {
		if (empty($data)) {
			return array(true);
		}
		if (empty($submitkey)) {
			$submitkey = empty($user) ? '' : $user->uid;
		}
		// 处理后的登记记录
		$dbData = new \stdClass;
		// 清除已有的登记数据
		$this->delete('xxt_enroll_record_data', "aid='{$app->id}' and enroll_key='$ek'");

		$schemas = json_decode($app->data_schemas);
		$schemasById = [];
		foreach ($schemas as $schema) {
			$schemasById[$schema->id] = $schema;
		}
		foreach ($data as $n => $v) {
			/**
			 * 插入自定义属性
			 */
			if ($n === 'member' && is_object($v)) {
				//
				$dbData->{$n} = $v;
				/* 用户认证信息 */
				$treatedValue = new \stdClass;
				isset($v->name) && $treatedValue->name = urlencode($v->name);
				isset($v->email) && $treatedValue->email = urlencode($v->email);
				isset($v->mobile) && $treatedValue->mobile = urlencode($v->mobile);
				if (!empty($v->extattr)) {
					$extattr = new \stdClass;
					foreach ($v->extattr as $mek => $mev) {
						$extattr->{$mek} = urlencode($mev);
					}
					$treatedValue->extattr = $extattr;
				}
				$treatedValue = urldecode(json_encode($treatedValue));
			} else if (isset($schemasById[$n])) {
				$schema = $schemasById[$n];
				if (is_array($v) && (isset($v[0]->serverId) || isset($v[0]->imgSrc))) {
					/* 上传图片 */
					$treatedValue = [];
					$fsuser = \TMS_APP::model('fs/user', $siteId);
					foreach ($v as $img) {
						$rst = $fsuser->storeImg($img);
						if (false === $rst[0]) {
							return $rst;
						}
						$treatedValue[] = $rst[1];
					}
					$treatedValue = implode(',', $treatedValue);
					$dbData->{$n} = $treatedValue;
				} else if (is_array($v) && isset($v[0]->uniqueIdentifier)) {
					/* 上传文件 */
					$fsUser = \TMS_APP::M('fs/local', $siteId, '_user');
					$fsResum = \TMS_APP::M('fs/local', $siteId, '_resumable');
					$fsAli = \TMS_APP::M('fs/alioss', $siteId);
					$treatedValue = [];
					foreach ($v as $file) {
						if (defined('SAE_TMP_PATH')) {
							$dest = '/' . $app->id . '/' . $submitkey . '_' . $file->name;
							$fileUploaded2 = $fsAli->getBaseURL() . $dest;
						} else {
							$fileUploaded = $fsResum->rootDir . '/' . $submitkey . '_' . $file->uniqueIdentifier;
							!file_exists($fsUser->rootDir . '/' . $submitkey) && mkdir($fsUser->rootDir . '/' . $submitkey, 0777, true);
							$fileUploaded2 = $fsUser->rootDir . '/' . $submitkey . '/' . $file->name;
							if (false === rename($fileUploaded, $fileUploaded2)) {
								return array(false, '移动上传文件失败');
							}
						}
						unset($file->uniqueIdentifier);
						$file->url = $fileUploaded2;
						$treatedValue[] = $file;
					}
					$treatedValue = json_encode($treatedValue);
					$dbData->{$n} = $treatedValue;
				} else if ($schema->type === 'score') {
					$dbData->{$n} = $v;
					$treatedValue = json_encode($v);
				} else {
					if (is_string($v)) {
						$treatedValue = $this->escape($v);
					} else if (is_object($v) || is_array($v)) {
						if ($schema->type === 'multiple') {
							// 多选题，将选项合并为逗号分隔的字符串
							$treatedValue = implode(',', array_keys(array_filter((array) $v, function ($i) {return $i;})));
						} else {
							$treatedValue = implode(',', $v);
						}
					} else {
						$treatedValue = $v;
					}
					$dbData->{$n} = $treatedValue;
				}
			} else {
				/* 如果登记活动指定匹配清单，那么提交数据会包含匹配登记记录的数据，但是这些数据不在登记项定义中 */
				$treatedValue = $v;
				$dbData->{$n} = $treatedValue;
			}
			// 记录数据
			if (is_object($treatedValue) || is_array($treatedValue)) {
				$treatedValue = json_encode($treatedValue);
			}
			$ic = [
				'aid' => $app->id,
				'enroll_key' => $ek,
				'name' => $n,
				'value' => $treatedValue,
			];
			$this->insert('xxt_enroll_record_data', $ic, false);
		}
		/* 保留冗余数据 */
		$dbData = $this->toJson($dbData);
		$this->update('xxt_enroll_record', ['data' => $dbData], "enroll_key='$ek'");

		return [true, $dbData];
	}
	/**
	 * 根据ID返回登记记录
	 */
	public function &byId($ek, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_enroll_record',
			"enroll_key='$ek'",
		];
		if (($record = $this->query_obj_ss($q)) && $fields === '*') {
			$record->data=\TMS_MODEL::strConvert($record->data);
		}

		return $record;
	}
	/**
	 * 获得用户的登记清单
	 */
	public function &byUser($appId, &$user) {
		$q = [
			'*',
			'xxt_enroll_record',
			["state" => 1, "aid" => $appId, "userid" => $user->uid],
		];

		$q2 = ['o' => 'enroll_at desc'];

		$list = $this->query_objs_ss($q, $q2);

		return $list;
	}
	/**
	 * 根据指定的数据查找匹配的记录
	 *
	 * 不是所有的字段都检查，只检查字符串类型
	 */
	public function &byData($siteId, &$app, &$data, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$records = false;

		// 查找条件
		$whereByData = '';
		foreach ($data as $k => $v) {
			if (!empty($v) && is_string($v)) {
				$whereByData .= ' and (';
				$whereByData .= 'data like \'%"' . $k . '":"' . $v . '"%\'';
				$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . '"%\'';
				$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . ',%"%\'';
				$whereByData .= ' or data like \'%"' . $k . '":"' . $v . ',%"%\'';
				$whereByData .= ')';
			}
		}

		// 没有指定条件时就认为没有符合条件的记录
		if (empty($whereByData)) {
			return $records;
		}

		// 查找匹配条件的数据
		$q = [
			$fields,
			'xxt_enroll_record',
			"state=1 and aid='{$app->id}' $whereByData",
		];
		$records = $this->query_objs_ss($q);
		foreach ($records as &$record) {
			if (empty($record->data)) {
				$record->data = new \stdClass;
			} else {
				$data = json_decode($record->data);
				if ($data === null) {
					$record->data = 'json error(' . json_last_error() . '):' . $r->data;
				} else {
					$record->data = $data;
				}
			}
		}

		return $records;
	}
	/**
	 * 为了计算每条记录的分数，转换schema的形式
	 */
	private function _mapOfScoreSchema(&$app) {
		$scoreSchemas = new \stdClass;

		$schemas = is_object($app->data_schemas) ? $app->data_schemas : json_decode($app->data_schemas);
		foreach ($schemas as $schema) {
			if ($schema->type === 'single' && isset($schema->score) && $schema->score === 'Y') {
				$scoreSchemas->{$schema->id} = new \stdClass;
				$scoreSchemas->{$schema->id}->ops = new \stdClass;
				foreach ($schema->ops as $op) {
					$scoreSchemas->{$schema->id}->ops->{$op->v} = $op;
				}
			}
		}

		return $scoreSchemas;
	}
	/**
	 * 计算记录的分数
	 */
	private function _calcScore(&$scoreSchemas, &$data) {
		$score = 0;
		foreach ($scoreSchemas as $schemaId => $schema) {
			if (!empty($data->{$schemaId})) {
				$opScore = empty($schema->ops->{$data->{$schemaId}}->score) ? 0 : $schema->ops->{$data->{$schemaId}}->score;
				$score += $opScore;
			}
		}

		return $score;
	}
	/**
	 * 登记清单
	 *
	 * 1、如果活动仅限会员报名，那么要叠加会员信息
	 * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
	 *
	 * $siteId
	 * $aid
	 * $options
	 * --creater openid
	 * --visitor openid
	 * --page
	 * --size
	 * --rid 轮次id
	 * --kw 检索关键词
	 * --by 检索字段
	 * $criteria 登记数据过滤条件
	 *
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 */
	public function find($siteId, &$app, $options = null, $criteria = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
			$creater = isset($options->creater) ? $options->creater : null;
			$inviter = isset($options->inviter) ? $options->inviter : null;
			$orderby = isset($options->orderby) ? $options->orderby : '';
			$page = isset($options->page) ? $options->page : null;
			$size = isset($options->size) ? $options->size : null;
			$rid = null;
			if (!empty($options->rid)) {
				if ($options->rid === 'ALL') {
					$rid = null;
				} else if (!empty($options->rid)) {
					$rid = $options->rid;
				}
			} else if ($activeRound = $this->M('matter\enroll\round')->getActive($siteId, $app->id)) {
				$rid = $activeRound->rid;
			}
		}
		$result = new \stdClass; // 返回的结果
		$result->total = 0;

		// 指定登记活动下的登记记录
		$w = "e.state=1 and e.aid='{$app->id}'";

		if (!empty($creater)) {
			$w .= " and e.userid='$creater'";
		} else if (!empty($inviter)) {
			$user = new \stdClass;
			$user->openid = $inviter;
			$inviterek = $this->getLastKey($siteId, $aid, $user);
			$w .= " and e.referrer='ek:$inviterek'";
		}

		// 指定了轮次
		!empty($rid) && $w .= " and e.rid='$rid'";

		// 指定了登记记录过滤条件
		if (!empty($criteria->record)) {
			$whereByRecord = '';
			if (!empty($criteria->record->verified)) {
				$whereByRecord .= " and verified='{$criteria->record->verified}'";
			}
			$w .= $whereByRecord;
		}

		// 指定了记录标签
		if (!empty($criteria->tags)) {
			$whereByTag = '';
			foreach ($criteria->tags as $tag) {
				$whereByTag .= " and concat(',',e.tags,',') like '%,$tag,%'";
			}
			$w .= $whereByTag;
		}

		// 指定了登记数据过滤条件
		if (isset($criteria->data)) {
			$whereByData = '';
			foreach ($criteria->data as $k => $v) {
				if (!empty($v)) {
					$whereByData .= ' and (';
					$whereByData .= 'data like \'%"' . $k . '":"' . $v . '"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . '"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . ',%"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"' . $v . ',%"%\'';
					$whereByData .= ')';
				}
			}
			$w .= $whereByData;
		}

		// 查询参数
		$q = [
			'e.enroll_key,e.enroll_at,e.tags,e.userid,e.nickname,e.verified,e.comment,e.data',
			"xxt_enroll_record e",
			$w,
		];

		$q2 = [];
		// 查询结果分页
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		// 查询结果排序
		$q2['o'] = 'e.enroll_at desc';
		// 处理获得的数据
		if ($records = $this->query_objs_ss($q, $q2)) {
			foreach ($records as &$r) {
				/*$data = str_replace("\n", ' ', $r->data);
				$data = json_decode($data);
				if ($data === null) {
					$r->data = 'json error(' . json_last_error_msg() . '):' . $r->data;
				} else {
					$r->data = $data;
				}*/
				$r->data=\TMS_MODEL::strConvert($r->data);
				// 记录的分数
				if ($app->scenario === 'voting') {
					if (!isset($scoreSchemas)) {
						$scoreSchemas = $this->_mapOfScoreSchema($app);
						$countScoreSchemas = count(array_keys((array) $scoreSchemas));
					}
					$r->_score = $this->_calcScore($scoreSchemas, $data);
					$r->_average = $countScoreSchemas === 0 ? 0 : $r->_score / $countScoreSchemas;
				}
				// 获得邀请数据
				if (isset($app->can_invite) && $app->can_invite === 'Y') {
					$qf = array(
						'id,enroll_key,enroll_at,openid,nickname',
						'xxt_enroll_record',
						"aid='$aid' and referrer='ek:$r->enroll_key'",
					);
					$qf2 = array('o' => 'enroll_at');
					$r->followers = $this->query_objs_ss($qf, $qf2);
				}
				//获得关联抽奖活动记录
				$ql = array(
					'award_title',
					'xxt_lottery_log',
					"enroll_key='$r->enroll_key'",
				);
				$lotteryResult = $this->query_objs_ss($ql);
				if (!empty($lotteryResult)) {
					$lrs = array();
					foreach ($lotteryResult as $lr) {
						$lrs[] = $lr->award_title;
					}
					$r->data->lotteryResult = implode(',', $lrs);
				}
			}
			$result->records = $records;

			// 符合条件的数据总数
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result->total = $total;
		}

		return $result;
	}
	/**
	 * 登记清单
	 *
	 * 1、如果活动仅限会员报名，那么要叠加会员信息
	 * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
	 *
	 * $siteId
	 * $aid
	 * $options
	 * --creater openid
	 * --visitor openid
	 * --page
	 * --size
	 * --rid 轮次id
	 * --kw 检索关键词
	 * --by 检索字段
	 * $criteria 登记数据过滤条件
	 *
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 */
	public function list4Schema($siteId, &$app, $schemaId, $options = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
			$page = isset($options->page) ? $options->page : null;
			$size = isset($options->size) ? $options->size : null;
		}
		$result = new \stdClass; // 返回的结果
		$result->total = 0;

		// 查询参数
		$q = [
			'value',
			"xxt_enroll_record_data",
			"state=1 and aid='{$app->id}' and name='{$schemaId}' and value<>''",
		];

		$q2 = [];
		// 查询结果分页
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		// 处理获得的数据
		if ($records = $this->query_objs_ss($q, $q2)) {
			$result->records = $records;

			// 符合条件的数据总数
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result->total = $total;
		}

		return $result;
	}
	/**
	 * 登记情况摘要
	 */
	public function &summary($siteId, $appId) {
		$modelRnd = \TMS_APP::M('matter\enroll\round');
		$page = (object) ['num' => 1, 'size' => 5];
		$rounds = $modelRnd->byApp($siteId, $appId, ['fields' => 'rid,title', 'page' => $page]);

		if (empty($rounds)) {
			$summary = new \stdClass;
			/* total */
			$q = [
				'count(*)',
				'xxt_enroll_record',
				['aid' => $appId, 'state' => 1],
			];
			$summary->total = $this->query_val_ss($q);
		} else {
			$summary = [];
			$activeRound = $modelRnd->getActive($siteId, $appId);
			foreach ($rounds as $round) {
				/* total */
				$q = [
					'count(*)',
					'xxt_enroll_record',
					['aid' => $appId, 'state' => 1, 'rid' => $round->rid],
				];
				$round->total = $this->query_val_ss($q);
				if ($activeRound && $round->rid === $activeRound->rid) {
					$round->active = 'Y';
				}

				$summary[] = $round;
			}
		}

		return $summary;
	}
	/**
	 * 获得指定用户最后一次登记记录
	 * 如果设置轮次，只返回当前轮次的情况
	 */
	public function getLast($siteId, $app, $user, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_enroll_record',
			"siteid='$siteId' and aid='{$app->id}' and state=1",
		];
		$q[2] .= " and userid='{$user->uid}'";
		if ($activeRound = \TMS_APP::M('matter\enroll\round')->getActive($siteId, $app->id)) {
			$q[2] .= " and rid='$activeRound->rid'";
		}
		$q2 = [
			'o' => 'enroll_at desc',
			'r' => ['o' => 0, 'l' => 1],
		];
		$records = $this->query_objs_ss($q, $q2);

		if ($fields === '*') {
			foreach ($records as &$record) {
				$record->data=\TMS_MODEL::strConvert($record->data);
			}
		}

		return count($records) === 1 ? $records[0] : false;
	}
	/**
	 * 获得指定用户最后一次登记的key
	 * 如果设置轮次，只检查当前轮次的情况
	 *
	 * @param string $siteId
	 * @param object $app
	 * @param object $user
	 *
	 */
	public function getLastKey($siteId, &$app, &$user) {
		$last = $this->getLast($siteId, $app, $user);

		return $last ? $last->enroll_key : false;
	}
	/**
	 *
	 */
	public function hasAcceptedInvite($aid, $openid, $ek) {
		$q = array(
			'enroll_key',
			'xxt_enroll_record',
			"aid='$aid' and openid='$openid' and referrer='ek:$ek'",
		);
		$records = $this->query_objs_ss($q);
		if (empty($records)) {
			return false;
		} else {
			return $records[0]->enroll_key;
		}
	}
	/**
	 * 获得一条登记记录的数据
	 */
	public function dataById($ek) {
		$q = array(
			'name,value',
			'xxt_enroll_record_data',
			"enroll_key='$ek'",
		);
		$cusdata = array();
		$cdata = $this->query_objs_ss($q);
		if (count($cdata) > 0) {
			foreach ($cdata as $cd) {
				$cusdata[$cd->name] = $cd->value;
			}
		}
		return $cusdata;
	}
	/**
	 * 返回登记人
	 */
	public function &enrollers($aid, $rid = '', $page = 1, $size = 30) {
		$w = "aid='$aid' and state=1";
		!empty($rid) && $w .= " and rid='$rid'";
		$q = array(
			'distinct openid,nickname',
			'xxt_enroll_record',
			$w,
		);
		$enrollers = $this->query_objs_ss($q);

		$result = array(
			'enrollers' => $enrollers,
		);

		return $result;
	}
	/**
	 * 生成活动登记的key
	 */
	public function genKey($siteId, $aid) {
		return md5(uniqid() . $siteId . $aid);
	}
	/**
	 *
	 */
	public function modify($ek, $data) {
		$rst = $this->update(
			'xxt_enroll_record',
			$data,
			"enroll_key='$ek'"
		);
		return $rst;
	}
	/**
	 * 登记人清除一条登记记录
	 *
	 * @param string $aid
	 * @param string $ek
	 */
	public function removeByUser($site, $appId, $ek) {
		$rst = $this->update(
			'xxt_enroll_record_data',
			['state' => 200],
			"aid='$appId' and enroll_key='$ek'"
		);
		$rst = $this->update(
			'xxt_enroll_record',
			['state' => 200],
			"aid='$appId' and enroll_key='$ek'"
		);

		return $rst;
	}
	/**
	 * 清除一条登记记录
	 *
	 * @param string $aid
	 * @param string $ek
	 */
	public function remove($appId, $ek, $byDelete = false) {
		if ($byDelete) {
			$rst = $this->delete(
				'xxt_enroll_record_data',
				"aid='$appId' and enroll_key='$ek'"
			);
			$rst = $this->delete(
				'xxt_enroll_record',
				"aid='$appId' and enroll_key='$ek'"
			);
		} else {
			$rst = $this->update(
				'xxt_enroll_record_data',
				array('state' => 100),
				"aid='$appId' and enroll_key='$ek'"
			);
			$rst = $this->update(
				'xxt_enroll_record',
				array('state' => 100),
				"aid='$appId' and enroll_key='$ek'"
			);
		}

		return $rst;
	}
	/**
	 * 清除登记记录
	 *
	 * @param string $appId
	 */
	public function clean($appId, $byDelete = false) {
		if ($byDelete) {
			$rst = $this->delete(
				'xxt_enroll_record_data',
				"aid='$appId'"
			);
			$rst = $this->delete(
				'xxt_enroll_record',
				"aid='$appId'"
			);
		} else {
			$rst = $this->update(
				'xxt_enroll_record_data',
				array('state' => 0),
				"aid='$appId'"
			);
			$rst = $this->update(
				'xxt_enroll_record',
				array('state' => 0),
				"aid='$appId'"
			);
		}

		return $rst;
	}
	/**
	 * 统计登记信息
	 *
	 */
	public function &getStat($appId) {
		$result = [];

		$app = \TMS_APP::M('matter\enroll')->byId($appId, ['data_schemas', 'cascaded' => 'N']);
		if (empty($app->data_schemas)) {
			return $result;
		}

		$dataSchemas = json_decode($app->data_schemas);

		foreach ($dataSchemas as $schema) {
			if (!in_array($schema->type, ['single', 'multiple', 'phase', 'score'])) {
				continue;
			}
			$result[$schema->id] = ['title' => $schema->title, 'id' => $schema->id, 'ops' => []];
			if (in_array($schema->type, ['single', 'phase'])) {
				foreach ($schema->ops as $op) {
					/**
					 * 获取数据
					 */
					$q = [
						'count(*)',
						'xxt_enroll_record_data',
						"aid='$appId' and state=1 and name='{$schema->id}' and value='{$op->v}'",
					];
					$op->c = $this->query_val_ss($q);
					$result[$schema->id]['ops'][] = $op;
				}
			} else if ($schema->type === 'multiple') {
				foreach ($schema->ops as $op) {
					/**
					 * 获取数据
					 */
					$q = [
						'count(*)',
						'xxt_enroll_record_data',
						"aid='$appId' and state=1 and name='{$schema->id}' and FIND_IN_SET('{$op->v}', value)",
					];
					$op->c = $this->query_val_ss($q);
					$result[$schema->id]['ops'][] = $op;
				}
			} else if ($schema->type === 'score') {
				$scoreByOp = [];
				foreach ($schema->ops as &$op) {
					$op->c = 0;
					$result[$schema->id]['ops'][] = $op;
					$scoreByOp[$op->v] = $op;
				}
				// 计算总分数
				$q = [
					'value',
					'xxt_enroll_record_data',
					"aid='$appId' and state=1 and name='{$schema->id}'",
				];
				$values = $this->query_objs_ss($q);
				foreach ($values as $value) {
					$value = json_decode($value->value);
					foreach ($value as $opKey => $opValue) {
						$scoreByOp[$opKey]->c += (int) $opValue;
					}
				}
				// 计算平均分
				if ($rowNumber = count($values)) {
					foreach ($schema->ops as &$op) {
						$op->c = $op->c / $rowNumber;
					}
				} else {
					$op->c = 0;
				}
			}
		}

		return $result;
	}
}