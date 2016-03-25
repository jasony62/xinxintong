<?php
namespace app\enroll;

class record_model extends \TMS_MODEL {
	/**
	 * 根据ID返回登记记录
	 */
	public function byId($ek, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';

		$q = array(
			$fields,
			'xxt_enroll_record',
			"enroll_key='$ek'",
		);
		if (($record = $this->query_obj_ss($q)) && $cascaded === 'Y') {
			$record->data = $this->dataById($ek);
		}

		return $record;
	}
	/**
	 * 登记清单
	 *
	 * 1、如果活动仅限会员报名，那么要叠加会员信息
	 * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
	 *
	 * $mpid
	 * $aid
	 * $options
	 * --creater openid
	 * --visitor openid
	 * --page
	 * --size
	 * --rid 轮次id
	 * --kw 检索关键词
	 * --by 检索字段
	 *
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 */
	public function find($mpid, $aid, $options = null) {
		$modelApp = $this->M('app\enroll');
		/* 获得活动的定义 */
		$app = $modelApp->byId($aid);
		if ($app === false) {
			return false;
		}
		if ($options) {
			is_array($options) && $options = (object) $options;
			$creater = isset($options->creater) ? $options->creater : null;
			$inviter = isset($options->inviter) ? $options->inviter : null;
			$visitor = isset($options->visitor) ? $options->visitor : '';
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
			} else if ($activeRound = $this->M('app\enroll\round')->getActive($mpid, $aid)) {
				$rid = $activeRound->rid;
			}
			$signinStartAt = isset($options->signinStartAt) ? $options->signinStartAt : null;
			$signinEndAt = isset($options->signinEndAt) ? $options->signinEndAt : null;
			$kw = isset($options->kw) ? $options->kw : null;
			$by = isset($options->by) ? $options->by : null;
		}
		$result = new \stdClass; // 返回的结果
		$result->total = 0;
		/* 获得数据项定义 */
		$modelPage = $this->M('app\enroll\page');
		$result->schema = $modelPage->schemaByApp($aid);
		/* 获得登记数据 */
		$w = "e.state=1 and e.mpid='$mpid' and e.aid='$aid'";
		if (!empty($creater)) {
			$w .= " and e.openid='$creater'";
		} else if (!empty($inviter)) {
			$user = new \stdClass;
			$user->openid = $inviter;
			$inviterek = $this->getLastKey($mpid, $aid, $user);
			$w .= " and e.referrer='ek:$inviterek'";
		}
		!empty($rid) && $w .= " and e.rid='$rid'";
		if (!empty($kw) && !empty($by)) {
			switch ($by) {
			case 'mobile':
				$kw && $w .= " and m.mobile like '%$kw%'";
				break;
			case 'nickname':
				$kw && $w .= " and e.nickname like '%$kw%'";
				break;
			}
		}
		/*签到时间*/
		//if (!empty($signinStartAt) && !empty($signinEndAt)) {
		//	$w .= " and exists(select 1 from xxt_enroll_signin_log l";
		//	$w .= " where l.signin_at>=$signinStartAt and l.signin_at<=$signinEndAt and l.enroll_key=e.enroll_key";
		//	$w .= ")";
		//}
		/*tags*/
		if (!empty($options->tags)) {
			$aTags = explode(',', $options->tags);
			foreach ($aTags as $tag) {
				$w .= "and concat(',',e.tags,',') like '%,$tag,%'";
			}
		}
		if ($app->access_control === 'Y') {
			$q = array(
				'e.enroll_key,e.enroll_at,e.signin_at,e.tags,e.follower_num,e.score,e.remark_num,e.nickname,e.openid,e.vid,m.mid,m.name,m.mobile,m.email',
				"xxt_enroll_record e left join xxt_member m on m.forbidden='N' and e.mid=m.mid",
				$w,
			);
		} else {
			$q = array(
				'e.enroll_key,e.enroll_at,e.signin_at,e.tags,e.follower_num,e.score,e.remark_num,e.nickname,e.openid,e.vid',
				"xxt_enroll_record e",
				$w,
			);
		}
		$q2 = array(
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
		);
		switch ($orderby) {
		case 'time':
			$q2['o'] = 'e.enroll_at desc';
			break;
		case 'score':
			$q2['o'] = 'e.score desc';
			break;
		case 'remark':
			$q2['o'] = 'e.remark_num desc';
			break;
		case 'follower':
			$q2['o'] = 'e.follower_num desc';
			break;
		default:
			$q2['o'] = 'e.enroll_at desc';
		}
		if ($records = $this->query_objs_ss($q, $q2)) {
			foreach ($records as &$r) {
				/* 获得填写的登记数据 */
				$qc = array(
					'name,value',
					'xxt_enroll_record_data',
					"enroll_key='$r->enroll_key'",
				);
				$cds = $this->query_objs_ss($qc);
				$r->data = new \stdClass;
				foreach ($cds as $cd) {
					$r->data->{$cd->name} = $cd->value;
				}
				/*获得点赞记录*/
				$app->can_like_record === 'Y' && $r->likers = $this->likers($r->enroll_key, 1, 3);
				/*获得签到记录*/
				if ($app->can_signin === 'Y') {
					$qs = array(
						'signin_at',
						'xxt_enroll_signin_log',
						"enroll_key='$r->enroll_key'",
					);
					$qs2 = array('o' => 'signin_at desc');
					$r->signinLogs = $this->query_objs_ss($qs, $qs2);
				}
				/*获得邀请数据*/
				if ($app->can_invite === 'Y') {
					$qf = array(
						'id,enroll_key,enroll_at,openid,nickname',
						'xxt_enroll_record',
						"aid='$aid' and referrer='ek:$r->enroll_key'",
					);
					$qf2 = array('o' => 'enroll_at');
					$r->followers = $this->query_objs_ss($qf, $qf2);
				}
				/*获得关联抽奖活动记录*/
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
			/* total */
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result->total = $total;
		}

		return $result;
	}
	/**
	 * 获得用户的登记清单
	 */
	public function byUser($mpid, $aid, $openid, $rid = null) {
		if (empty($openid)) {
			return false;
		}

		$q = array(
			'*',
			'xxt_enroll_record',
			"state=1 and mpid='$mpid' and aid='$aid' and openid='$openid'",
		);
		if (empty($rid)) {
			$modelRun = \TMS_APP::M('app\enroll\round');
			if ($activeRound = $modelRun->getActive($mpid, $aid)) {
				$q[2] .= " and rid='$activeRound->rid'";
			}
		} else {
			$q[2] .= " and rid='$rid'";
		}
		$q2 = array('o' => 'enroll_at desc');

		$list = $this->query_objs_ss($q, $q2);

		return $list;
	}
	/**
	 * 获得指定用户最后一次登记记录
	 * 如果设置轮次，只返回当前轮次的情况
	 */
	public function getLast($mpid, $aid, $user, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = array(
			$fields,
			'xxt_enroll_record',
			"state=1 and mpid='$mpid' and aid='$aid' and state=1",
		);
		if (!empty($user->openid)) {
			$q[2] .= " and openid='$user->openid'";
		} else if (!empty($user->vid)) {
			$q[2] .= " and vid='$user->vid'";
		} else {
			return false;
		}
		if ($activeRound = \TMS_APP::M('app\enroll\round')->getActive($mpid, $aid)) {
			$q[2] .= " and rid='$activeRound->rid'";
		}
		$q2 = array(
			'o' => 'enroll_at desc',
			'r' => array('o' => 0, 'l' => 1),
		);
		$records = $this->query_objs_ss($q, $q2);

		return count($records) === 1 ? $records[0] : false;
	}
	/**
	 * 获得指定用户最后一次登记的key
	 * 如果设置轮次，只坚持当前轮次的情况
	 */
	public function getLastKey($mpid, $aid, $user) {
		$last = $this->getLast($mpid, $aid, $user);

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
	 * 评论
	 */
	public function &remarks($ek, $page = 1, $size = 30) {
		$q = array(
			'r.*',
			'xxt_enroll_record_remark r',
			"r.enroll_key='$ek'",
		);
		$q2 = array(
			'o' => 'r.create_at',
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
		);

		$remarks = $this->query_objs_ss($q, $q2);

		return $remarks;
	}
	/*
			* 所有发表过评论的用户
		 * @param string $ek
	*/
	public function &remarkers($ek) {
		$q = array(
			'distinct openid',
			'xxt_enroll_record_remark',
			"enroll_key='$ek'");

		$remarkers = $this->query_objs_ss($q);

		return $remarkers;
	}
	/**
	 * 返回对指定记录点赞的人
	 * @param string $ek
	 */
	public function &likers($ek, $page = 1, $size = 10) {
		$q = array(
			'openid,nickname,score',
			'xxt_enroll_record_score',
			"enroll_key='$ek'",
		);
		$q2 = array(
			'o' => 'create_at',
			'r' => array('o' => ($page - 1) * $size, 'l' => $size),
		);
		$likers = $this->query_objs_ss($q, $q2);

		return $likers;
	}
	/**
	 * 返回登记人
	 */
	public function &enrollers($aid, $rid = '', $size = 1, $size = 30) {
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
	 * 保存登记的数据
	 */
	public function setData($user, $runningMpid, $aid, $ek, $data, $submitkey = '') {
		if (empty($data)) {
			return array(true);
		}

		if (empty($submitkey)) {
			$submitkey = isset($user->vid) ? $user->vid : '';
		}
		// 已有的登记数据
		$fields = $this->query_vals_ss(array('name', 'xxt_enroll_record_data', "aid='$aid' and enroll_key='$ek'"));
		foreach ($data as $n => $v) {
			/**
			 * 插入自定义属性
			 */
			if ($n === 'member' && is_object($v)) {
				/* 用户认证信息 */
				$vv = new \stdClass;
				isset($v->name) && $vv->name = urlencode($v->name);
				isset($v->email) && $vv->email = urlencode($v->email);
				isset($v->mobile) && $vv->mobile = urlencode($v->mobile);
				if (!empty($v->extattr)) {
					$extattr = new \stdClass;
					foreach ($v->extattr as $mek => $mev) {
						$extattr->{$mek} = urlencode($mev);
					}
					$vv->extattr = $extattr;
				}
				$vv = urldecode(json_encode($vv));
			} else if (is_array($v) && (isset($v[0]->serverId) || isset($v[0]->imgSrc))) {
				/* 上传图片 */
				$vv = array();
				$fsuser = \TMS_APP::model('fs/user', $runningMpid);
				foreach ($v as $img) {
					$rst = $fsuser->storeImg($img);
					if (false === $rst[0]) {
						return $rst;
					}
					$vv[] = $rst[1];
				}
				$vv = implode(',', $vv);
			} else if (is_array($v) && isset($v[0]->uniqueIdentifier)) {
				/* 上传文件 */
				$fsUser = \TMS_APP::M('fs/local', $runningMpid, '_user');
				$fsResum = \TMS_APP::M('fs/local', $runningMpid, '_resumable');
				$fsAli = \TMS_APP::M('fs/alioss', $runningMpid);
				$vv = array();
				foreach ($v as $file) {
					if (defined('SAE_TMP_PATH')) {
						$dest = '/' . $aid . '/' . $submitkey . '_' . $file->name;
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
					$vv[] = $file;
				}
				$vv = json_encode($vv);
			} else {
				if (is_string($v)) {
					$vv = $this->escape($v);
				} else if (is_object($v) || is_array($v)) {
					$vv = implode(',', array_keys(array_filter((array) $v, function ($i) {return $i;})));
				} else {
					$vv = $v;
				}
			}
			if (!empty($fields) && in_array($n, $fields)) {
				$this->update(
					'xxt_enroll_record_data',
					array('value' => $vv),
					"aid='$aid' and enroll_key='$ek' and name='$n'"
				);
				unset($fields[array_search($n, $fields)]);
			} else {
				$ic = array(
					'aid' => $aid,
					'enroll_key' => $ek,
					'name' => $n,
					'value' => $vv,
				);
				$this->insert('xxt_enroll_record_data', $ic, false);
			}
		}

		return array(true);
	}
	/**
	 * 生成活动登记的key
	 */
	public function genKey($mpid, $aid) {
		return md5(uniqid() . $mpid . $aid);
	}
	/**
	 * 活动登记（不包括登记数据）
	 *
	 * $mpid 运行的公众号，和openid和src相对应
	 * $act
	 * $mid
	 */
	public function add($mpid, $act, $user, $referrer = '') {
		$ek = $this->genKey($mpid, $act->id);
		$i = array(
			'aid' => $act->id,
			'mpid' => $mpid,
			'enroll_at' => time(),
			'enroll_key' => $ek,
			'openid' => $user->openid,
			'nickname' => empty($user->nickname) ? '' : $user->nickname,
			'vid' => $user->vid,
			'mid' => '',
			'referrer' => $referrer,
		);

		$modelRou = \TMS_APP::M('app\enroll\round');
		if ($activeRound = $modelRou->getActive($mpid, $act->id)) {
			$i['rid'] = $activeRound->rid;
		}

		$this->insert('xxt_enroll_record', $i, false);

		return $ek;
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
	 * 清除一条登记记录
	 * @param string $aid
	 * @param string $ek
	 */
	public function remove($aid, $ek) {
		$rst = $this->update(
			'xxt_enroll_record_data',
			array('state' => 0),
			"aid='$aid' and enroll_key='$ek'"
		);
		$rst = $this->update(
			'xxt_enroll_record',
			array('state' => 0),
			"aid='$aid' and enroll_key='$ek'"
		);

		return $rst;
	}
	/**
	 * 清除登记记录
	 * @param string $aid
	 */
	public function clean($aid) {
		$rst = $this->update(
			'xxt_enroll_record_data',
			array('state' => 0),
			"aid='$aid'"
		);
		$rst = $this->update(
			'xxt_enroll_record',
			array('state' => 0),
			"aid='$aid'"
		);

		return $rst;
	}
	/**
	 * 当前访问用户是否已经点了赞
	 *
	 * $openid
	 * $ek
	 */
	public function hasScored($openid, $ek) {
		$q = array(
			'score',
			'xxt_enroll_record_score',
			"enroll_key='$ek' and openid='$openid'",
		);

		return 1 === (int) $this->query_val_ss($q);
	}
	/**
	 * 登记总的赞数
	 *
	 * $ek
	 */
	public function score($ek) {
		$q = array(
			'count(*)',
			'xxt_enroll_record_score',
			"enroll_key='$ek'",
		);
		$score = (int) $this->query_val_ss($q);

		return $score;
	}
}