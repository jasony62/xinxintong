<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 *
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
	public function list_action($site, $app, $page = 1, $size = 30, $signinStartAt = null, $signinEndAt = null, $tags = null, $rid = null, $kw = null, $by = null, $orderby = null, $contain = null) {
		/*应用*/
		$modelApp = $this->model('matter\enroll');
		$app = $modelApp->byId($app);
		/*参数*/
		$options = array(
			'page' => $page,
			'size' => $size,
			'tags' => $tags,
			'rid' => $rid,
			'kw' => $kw,
			'by' => $by,
			'orderby' => $orderby,
			'contain' => $contain,
		);
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->find($site, $app, $options);

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
	 * 给符合条件的登记记录打标签
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
			$modelApp = $this->model('matter\enroll');
			/*给符合条件的记录打标签*/
			$modelRec = $this->model('matter\enroll\record');
			$q = array(
				'distinct enroll_key',
				'xxt_enroll_record_data',
				"aid='$app' and state=1",
			);
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
				$objApp = $modelApp->byId($target, array('cascaded' => 'N'));
				$options = array('cascaded' => $includeData);
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
		$r['signin_at'] = $current;
		if (isset($posted->verified)) {
			$r['verified'] = $posted->verified;
		}
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
			foreach ($posted->data as $n => $v) {
				if (in_array($n, array('signin_at', 'comment'))) {
					continue;
				} else if (is_array($v) && isset($v[0]->imgSrc)) {
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
					$v = $modelRec->escape($v);
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
				$modelRec->insert('xxt_enroll_record_data', $cd, false);
				$r['data'][$n] = $v;
			}
		}

		return new \ResponseData($r);
	}
	/**
	 * 清空一条登记信息
	 */
	public function remove_action($site, $app, $key) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\enroll\record')->remove($app, $key);

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

		foreach ($record as $k => $v) {
			if (in_array($k, array('verified', 'signin_at', 'tags', 'comment'))) {
				$model->update(
					'xxt_enroll_record',
					array($k => $v),
					"enroll_key='$ek'"
				);
				/*if ($k === 'tags') {
					$this->model('matter\enroll')->updateTags($app, $v);
				}*/
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
						$cd = array(
							'aid' => $app,
							'enroll_key' => $ek,
							'name' => $cn,
							'value' => $cv,
						);
						$model->insert('xxt_enroll_record_data', $cd, false);
					}
					$record->data->{$cn} = $cv;
				}
			}
		}

		return new \ResponseData($record);
	}
	/**
	 * 所有记录通过审核
	 */
	public function verifyAll_action($site, $app) {
		$rst = $this->model()->update(
			'xxt_enroll_record',
			array('verified' => 'Y'),
			"aid='$app'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 给登记活动的参与人发消息
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $tmplmsg
	 *
	 */
	public function notify_action($site, $app, $tmplmsg, $rid = null, $tags = null, $kw = null, $by = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$message = $this->getPostJson();
		/**
		 * 用户筛选条件
		 */
		$options = array(
			'tags' => $tags,
			'rid' => $rid,
			'kw' => $kw,
			'by' => $by,
		);

		$participants = $this->model('matter\enroll')->participants($site, $app, $tmplmsg, $options);

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
		/**
		 * 指定的消息发送方式
		 */
		if (count($userIds)) {
			$mapOfUsers = new \stdClass;
			$modelAcnt = $this->model('site\user\account');
			$modelWxfan = $modelYxfan = $modelQyfan = false;

			/*微信可以使用平台的公众号*/
			$wxSiteId = false;

			foreach ($userIds as $userid) {
				$user = $modelAcnt->byId($userid, ['fields' => 'ufrom']);
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
						$modelWxfan === false && $modelWxfan = $this->model('sns\wx\fan');
						$fan = $modelWxfan->byUser($wxSiteId, $userid, 'openid', 'Y');
						/*如果定义了发送素材的模版消息，用模版消息发送*/
						$rst = $this->tmplmsgSendByOpenid($tmplmsgId, $fan->openid, $message);
						if ($rst[0] === false) {
							return $rst;
						}
						break;
					case 'yx':
						$modelYxfan === false && $modelYxfan = $this->model('sns\yx\fan');
						/*如果开放了点对点消息，用点对点消息发送*/
						$fan = $modelYxfan->byUser($siteId, $userid, 'openid', 'Y');
						break;
					case 'qy':
						$modelQyfan = false && $modelQyfan = $this->model('sns\qy\fan');
						$fan = $modelQyfan->byUser($siteId, $userid, 'openid', 'Y');
						break;
					}
				}
			}
		}

		return array(true);
	}
}