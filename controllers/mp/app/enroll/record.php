<?php
namespace mp\app\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 *
 */
class record extends \mp\app\app_base {
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/mp/app/enroll/detail');
	}
	/**
	 * 给登记活动的参与人发消息
	 */
	public function sendNotify_action($matterType = null, $matterId = null, $aid, $rid = null, $tags = null, $kw = null, $by = null) {
		/**
		 * 接口是否具备
		 */
		$modelMpa = $this->model('mp\mpaccount');
		$mpa = $modelMpa->byId($this->mpid);
		if ($mpa->mpsrc !== 'yx') {
			return new \ResponseError('目前仅支持向易信用户发送通知消息！');
		}

		$setting = $modelMpa->getFeature($this->mpid, 'yx_p2p');
		if ($setting->yx_p2p !== 'Y') {
			return new \ResponseError('目前仅支持向开通了点对点消息接口的公众号发送消息！');
		}
		/**
		 * get matter.
		 */
		$model = $this->model('matter\\' . $matterType);
		$message = $model->forCustomPush($this->mpid, $matterId);
		/**
		 * 用户筛选条件
		 */
		$options = array(
			'tags' => $tags,
			'rid' => $rid,
			'kw' => $kw,
			'by' => $by,
		);
		$participants = $this->model('app\enroll')->participants($this->mpid, $aid, $options);

		$rst = $this->model('mpproxy/yx', $this->mpid)->messageSend($message, $participants);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		return new \ResponseData(count($participants));
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
	public function get_action($aid, $page = 1, $size = 30, $signinStartAt = null, $signinEndAt = null, $tags = null, $rid = null, $kw = null, $by = null, $orderby = null, $contain = null) {
		$options = array(
			'page' => $page,
			'size' => $size,
			'tags' => $tags,
			'signinStartAt' => $signinStartAt,
			'signinEndAt' => $signinEndAt,
			'rid' => $rid,
			'kw' => $kw,
			'by' => $by,
			'orderby' => $orderby,
			'contain' => $contain,
		);
		$mdoelRec = $this->model('app\enroll\record');
		$result = $mdoelRec->find($this->mpid, $aid, $options);

		return new \ResponseData($result);
	}
	/**
	 * 清空一条登记信息
	 */
	public function remove_action($aid, $key) {
		$rst = $this->model('app\enroll\record')->remove($aid, $key);

		return new \ResponseData($rst);
	}
	/**
	 * 清空登记信息
	 */
	public function empty_action($aid) {
		$rst = $this->model('app\enroll\record')->clean($aid);

		return new \ResponseData($rst);
	}
	/**
	 * 更新登记记录
	 *
	 * @param string $aid
	 * @param $ek enroll_key
	 */
	public function update_action($aid, $ek) {
		$record = $this->getPostJson();
		$model = $this->model();

		foreach ($record as $k => $v) {
			if (in_array($k, array('signin_at', 'tags', 'comment'))) {
				$model->update(
					'xxt_enroll_record',
					array($k => $v),
					"enroll_key='$ek'"
				);
				if ($k === 'tags') {
					$this->model('app\enroll')->updateTags($aid, $v);
				}
			} else if ($k === 'data' and is_object($v)) {
				foreach ($v as $cn => $cv) {
					/**
					 * 检查数据项是否存在，如果不存在就先创建一条
					 */
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
							'aid' => $aid,
							'enroll_key' => $ek,
							'name' => $cn,
							'value' => $cv,
						);
						$model->insert('xxt_enroll_record_data', $cd, false);
					}
				}
			}
		}

		return new \ResponseData('success');
	}
	/**
	 * 给记录批量添加标签
	 */
	public function batchTag_action($aid) {
		$posted = $this->getPostJson();
		$eks = $posted->eks;
		$aTags = $posted->tags;
		if (!empty($eks) && !empty($aTags)) {
			foreach ($eks as $ek) {
				$record = $this->model('app\enroll\record')->byId($ek, 'N');
				$existent = $record->tags;
				if (empty($existent)) {
					$aNew = $aTags;
				} else {
					$aExistent = explode(',', $existent);
					$aNew = array_unique(array_merge($aExistent, $aTags));
				}
				$newTags = implode(',', $aNew);
				$this->model()->update('xxt_enroll_record', array('tags' => $newTags), "enroll_key='$ek'");
			}
		}

		return new \ResponseData('ok');
	}
	/**
	 * 给符合条件的登记记录打标签
	 */
	public function tagByData_action($aid) {
		$posted = $this->getPostJson();
		$filter = $posted->filter;
		$aTags = explode(',', $posted->tag);

		if (!empty($aTags)) {
			/*更新应用标签*/
			$modelApp = $this->model('app\enroll');
			$modelApp->updateTags($aid, $posted->tag);
			/*给符合条件的记录打标签*/
			$modelRec = $this->model('app\enroll\record');
			$q = array(
				'distinct enroll_key',
				'xxt_enroll_record_data',
				"aid='$aid' and state=1",
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
				$options = array('cascaded' => 'N');
				foreach ($eks as $ek) {
					$record = $modelRec->byId($ek, $options);
					$existent = $record->tags;
					if (empty($existent)) {
						$aNew = $aTags;
					} else {
						$aExistent = explode(',', $existent);
						$aNew = array_unique(array_merge($aExistent, $aTags));
					}
					$newTags = implode(',', $aNew);
					$modelRec->update('xxt_enroll_record', array('tags' => $newTags), "enroll_key='$ek'");
				}
			}
		}

		return new \ResponseData('ok');
	}
	/**
	 * 给符合条件的登记记录打标签
	 */
	public function exportByData_action($aid) {
		$posted = $this->getPostJson();
		$filter = $posted->filter;
		$target = $posted->target;
		$includeData = isset($posted->includeData) ? $posted->includeData : 'N';

		if (!empty($target)) {
			/*更新应用标签*/
			$modelApp = $this->model('app\enroll');
			/*给符合条件的记录打标签*/
			$modelRec = $this->model('app\enroll\record');
			$q = array(
				'distinct enroll_key',
				'xxt_enroll_record_data',
				"aid='$aid' and state=1",
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
					$user->openid = $record->openid;
					$user->nickname = $record->nickname;
					$user->vid = '';
					$newek = $modelRec->add($this->mpid, $objApp, $user);
					if ($includeData === 'Y') {
						$modelRec->setData($user, $objApp->mpid, $objApp->id, $newek, $record->data);
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
	public function add_action($aid) {
		$posted = $this->getPostJson();

		$current = time();
		$modelRec = $this->model('app\enroll\record');
		$enrollKey = $modelRec->genKey($this->mpid, $aid);
		$r = array();
		$r['aid'] = $aid;
		$r['mpid'] = $this->mpid;
		$r['enroll_key'] = $enrollKey;
		$r['enroll_at'] = $current;
		$r['signin_at'] = $current;
		if (isset($posted->tags)) {
			$r['tags'] = $posted->tags;
			$this->model('app\enroll')->updateTags($aid, $posted->tags);
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
				}
				$cd = array(
					'aid' => $aid,
					'enroll_key' => $enrollKey,
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
	 * 导入认证用户
	 */
	public function importUser_action($aid) {
		$mids = $this->getPostJson();
		$modelRec = $this->model('app\enroll\record');

		$q = array(
			'count(*)',
			'xxt_enroll_record',
		);
		$rolls = array();
		$current = time();
		foreach ($mids as $mid) {
			$member = $this->model('user/member')->byId($mid);
			$q[2] = "aid='$aid' and mid='$mid'";
			if (1 === (int) $this->model()->query_val_ss($q)) {
				continue;
			}
			$enroll_key = $mddelRec->genKey($this->mpid, $aid);
			$r = array();
			$r['aid'] = $aid;
			$r['mpid'] = $this->mpid;
			$r['mid'] = $member->mid;
			$r['openid'] = $member->ooid;
			$r['enroll_key'] = $enroll_key;
			$r['enroll_at'] = $current;
			$r['signin_at'] = $current;

			$id = $this->model()->insert('xxt_enroll_record', $r, true);

			$r['id'] = $id;
			$r['nickname'] = $member->name;

			$rolls[] = $r;
		}

		return new \ResponseData($rolls);
	}
	/**
	 * 通过已有的活动导入用户???
	 *
	 * 目前支持指定的活动包括通用活动和讨论组活动
	 * 目前仅支持指定一个通用活动和一个讨论组活动
	 */
	public function importApp_action($aid) {
		$param = $this->getPostJson();
		$modelRec = $this->model('app\enroll\record');
		$current = time();

		$caid = $param->checkedActs[0];
		$cwid = $param->checkedWalls[0];
		$q = array(
			'w.openid,a.enroll_key',
			'xxt_enroll_record a,xxt_wall_enroll w',
			"a.aid='$caid' and w.wid='$cwid' and a.openid=w.openid and w.last_msg_at>0",
		);
		$fans = $this->model()->query_objs_ss($q);

		if (!empty($fans)) {
			foreach ($fans as $f) {
				/**
				 * 检查重复记录
				 */
				$q = array(
					'count(*)',
					'xxt_enroll_record',
					"mpid='$this->mpid' and aid='$aid' and src='$f->src' and openid='$f->openid'",
				);
				if (0 < (int) $this->model()->query_val_ss($q)) {
					continue;
				}
				/**
				 * 插入数据
				 */
				$enroll_key = $modelRec->genKey($this->mpid, $aid);
				$r = array();
				$r['aid'] = $aid;
				$r['mpid'] = $this->mpid;
				$r['enroll_key'] = $enroll_key;
				$r['enroll_at'] = $current;
				$r['signin_at'] = $current;
				$r['openid'] = $f->openid;

				$this->model()->insert('xxt_enroll_record', $r);
				/**
				 * 导入登记数据
				 * todo 临时方法
				 */
				$sql = 'insert into xxt_enroll_record_data(aid,enroll_key,name,value)';
				$sql .= " select '$aid','$enroll_key',name,value";
				$sql .= ' from xxt_enroll_record_data';
				$sql .= " where aid='$caid' and enroll_key='$f->enroll_key'";

				$this->model()->insert($sql);
			}
		}

		return new \ResponseData(count($fans));
	}
}