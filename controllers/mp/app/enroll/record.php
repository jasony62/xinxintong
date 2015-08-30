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

		$setting = $modelMpa->getSetting($this->mpid, 'yx_p2p');
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
		$participants = $this->model('app\enroll')->getParticipants($this->mpid, $aid, $options);

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
	public function get_action($aid, $page = 1, $size = 30, $tags = null, $rid = null, $kw = null, $by = null, $contain = null) {
		$options = array(
			'page' => $page,
			'size' => $size,
			'tags' => $tags,
			'rid' => $rid,
			'kw' => $kw,
			'by' => $by,
			'contain' => $contain,
		);

		$result = $this->model('app\enroll\record')->byEnroll($this->mpid, $aid, $options);

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
	 * 更新报名信息
	 *
	 * $ek enroll_key
	 */
	public function update_action($aid, $ek) {
		$roll = $this->getPostJson();

		foreach ($roll as $k => $v) {
			if (in_array($k, array('signin_at', 'tags', 'comment'))) {
				$this->model()->update(
					'xxt_enroll_record',
					array($k => $v),
					"enroll_key='$ek'"
				);
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
					if (1 === (int) $this->model()->query_val_ss($q)) {
						$this->model()->update(
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
						$this->model()->insert(
							'xxt_enroll_record_data',
							$cd
						);
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
	 * 手工添加报名信息
	 */
	public function add_action($aid) {
		$posted = (array) $this->getPostJson();
		/**
		 * 报名记录
		 */
		$current = time();
		$enroll_key = $this->model('app\enroll')->genEnrollKey($this->mpid, $aid);
		$r = array();
		$r['aid'] = $aid;
		$r['mpid'] = $this->mpid;
		$r['enroll_key'] = $enroll_key;
		$r['enroll_at'] = $current;
		$r['signin_at'] = $current;
		if (isset($posted['tags'])) {
			$r['tags'] = $posted['tags'];
		}

		$id = $this->model()->insert('xxt_enroll_record', $r, true);

		$r['id'] = $id;
		/**
		 * 登记信息
		 */
		if (!empty($posted->data)) {
			foreach ($posted->data as $n => $v) {
				if (in_array($n, array('signin_at', 'comment'))) {
					continue;
				}

				$cd = array(
					'aid' => $aid,
					'enroll_key' => $enroll_key,
					'name' => $n,
					'value' => $v,
				);
				$this->model()->insert(
					'xxt_enroll_record_data',
					$cd
				);
				$r[$n] = $v;
			}
		}

		return new \ResponseData($r);
	}
	/**
	 * 导入认证用户
	 */
	public function importUser_action($aid) {
		$mids = $this->getPostJson();

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

			/**
			 * 报名记录
			 */
			$enroll_key = $this->model('app\enroll')->genEnrollKey($this->mpid, $aid);
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
	 * 通过已有的活动导入用户
	 *
	 * 目前支持指定的活动包括通用活动和讨论组活动
	 * 目前仅支持指定一个通用活动和一个讨论组活动
	 */
	public function importApp_action($aid) {
		$param = $this->getPostJson();
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
				$enroll_key = $this->model('app\enroll')->genEnrollKey($this->mpid, $aid);
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
