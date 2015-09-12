<?php
namespace app;

require_once dirname(dirname(__FILE__)) . '/matter/enroll.php';
/**
 * 登记活动
 */
class enroll_model extends \matter\enroll_model {
	/**
	 *
	 * $aid string
	 * $cascaded array []
	 */
	public function &byId($aid, $fields = '*', $cascaded = 'Y') {
		$q = array(
			$fields,
			'xxt_enroll',
			"id='$aid'",
		);
		if ($e = $this->query_obj_ss($q)) {
			$e->entry_rule = json_decode($e->entry_rule);
			/**
			 * 页面内容
			 */
			if ($cascaded === 'Y') {
				$modelPage = \TMS_APP::M('app\enroll\page');
				$e->pages = $modelPage->byEnroll($aid);
			}
		}

		return $e;
	}
	/**
	 * 活动登记（不包括登记数据）
	 *
	 * $mpid 运行的公众号，和openid和src相对应
	 * $act
	 * $openid
	 * $vid
	 * $mid
	 */
	public function enroll($mpid, $act, $openid, $vid = '', $mid = '') {
		$fan = \TMS_APP::M('user/fans')->byOpenid($mpid, $openid);
		$modelRec = \TMS_APP::M('app\enroll\record');
		$ek = $modelRec->genKey($mpid, $act->id);
		$i = array(
			'aid' => $act->id,
			'mpid' => $mpid,
			'enroll_at' => time(),
			'enroll_key' => $ek,
			'openid' => $openid,
			'nickname' => !empty($fan) ? $fan->nickname : '',
			'vid' => $vid,
			'mid' => $mid,
		);
		$modelRun = \TMS_APP::M('app\enroll\round');
		if ($activeRound = $modelRun->getActive($mpid, $act->id)) {
			$i['rid'] = $activeRound->rid;
		}

		$this->insert('xxt_enroll_record', $i, false);

		return $ek;
	}
	/**
	 * 检查用户是否已经登记
	 *
	 * 如果设置轮次，只坚持当前轮次是否已经登记
	 */
	public function hasEnrolled($mpid, $aid, $openid) {
		if (empty($mpid) || empty($aid) || empty($openid)) {
			return false;
		}

		$q = array(
			'count(*)',
			'xxt_enroll_record',
			"state=1 and mpid='$mpid' and aid='$aid' and openid='$openid'",
		);
		$modelRun = \TMS_APP::M('app\enroll\round');
		if ($activeRound = $modelRun->getActive($mpid, $aid)) {
			$q[2] .= " and rid='$activeRound->rid'";
		}

		$rst = (int) $this->query_val_ss($q);

		return $rst > 0;
	}
	/**
	 * 活动签到
	 *
	 * 如果用户已经做过活动登记，那么设置签到时间
	 * 如果用户没有做个活动登记，那么要先产生一条登记记录，并记录签到时间
	 */
	public function signin($mpid, $aid, $openid) {
		$modelRec = $this->model('app\enroll\record');
		if ($ek = $modelRec->getLastKey($mpid, $aid, $openid)) {
			$rst = $this->update(
				'xxt_enroll_record',
				array('signin_at' => time()),
				"mpid='$mpid' and aid='$aid' and enroll_key='$ek'"
			);
			return true;
		} else {
			$ek = $this->enroll($mpid, (object) array('id' => $aid), $openid);
			$rst = $this->update(
				'xxt_enroll_record',
				array('signin_at' => time()),
				"mpid='$mpid' and aid='$aid' and enroll_key='$ek'"
			);
			return false;
		}
	}
	/**
	 * 活动报名名单
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
	public function participants($mpid, $aid, $options = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
			$rid = null;
			if (!empty($options->rid)) {
				if ($options->rid === 'ALL') {
					$rid = null;
				} else if (!empty($options->rid)) {
					$rid = $options->rid;
				}
			} else if ($activeRound = \TMS_APP::M('app\enroll\round')->getActive($mpid, $aid)) {
				$rid = $activeRound->rid;
			}

			$kw = isset($options->kw) ? $options->kw : null;
			$by = isset($options->by) ? $options->by : null;
		}
		$w = "e.mpid='$mpid' and aid='$aid'";
		!empty($rid) && $w .= " and e.rid='$rid'";
		// tags
		if (!empty($options->tags)) {
			$aTags = explode(',', $options->tags);
			foreach ($aTags as $tag) {
				$w .= "and concat(',',e.tags,',') like '%,$tag,%'";
			}

		}
		// todo need support?
		if (!empty($kw) && !empty($by)) {
			switch ($by) {
			case 'mobile':
				$kw && $w .= " and mobile like '%$kw%'";
				break;
			case 'nickname':
				$kw && $w .= " and nickname like '%$kw%'";
				break;
			}
		}
		// 活动参与人
		$q = array(
			'distinct e.openid',
			"xxt_enroll_record e",
			$w,
		);
		/**
		 * 获得填写的登记数据
		 */
		$participants = $this->query_vals_ss($q);

		return $participants;
	}
	/**
	 * 统计登记信息
	 *
	 * todo 图片的流的选择是写死的
	 *
	 * 只统计radio/checkbox类型的数据项
	 *
	 * return
	 * name => array(l=>label,c=>count)
	 *
	 */
	public function getStat($aid) {
		/**
		 * 获得活动的定义
		 */
		$q = array(
			'p.html form_html',
			'xxt_enroll a,xxt_code_page p',
			"a.id='$aid' and a.form_code_id=p.id",
		);
		$act = $this->query_obj_ss($q);
		// 记录返回的结果
		$defsAndCnt = array();
		/**
		 * 获得扩展数据项
		 * 数据项的定义需要从表单中获取
		 * 表单中定义了数据项的id和name
		 * 定义数据项都是input，所以首先应该将页面中所有input元素提取出来
		 * 每一个元素中都有ng-model和title属相，ng-model包含了id，title是名称
		 */
		if (!empty($act->form_html)) {
			$wraps = array();
			if (preg_match_all("/<div.+?wrap=.+?>.+?<\/div/i", $act->form_html, $wraps)) {
				$wraps = $wraps[0];
				foreach ($wraps as $wrap) {
					$def = array();
					$inp = array();
					$title = array();
					$ngmodel = array();
					$opval = array();
					$optit = array();
					if (!preg_match('/<input.+?>/', $wrap, $inp)) {
						continue;
					}

					$inp = $inp[0];
					if (preg_match('/title="(.*?)"/', $inp, $title)) {
						$title = $title[1];
					}

					if (preg_match('/type="radio"/', $inp)) {
						/**
						 * for radio group.
						 */
						if (preg_match('/ng-model="data\.(.+?)"/', $inp, $ngmodel)) {
							$id = $ngmodel[1];
						}

						$existing = false;
						foreach ($defsAndCnt as &$d) {
							if ($existing = ($d['id'] === $id)) {
								break;
							}
						}

						if (!$existing) {
							$defsAndCnt[] = array('title' => $title, 'id' => $id, 'ops' => array());
							$d = &$defsAndCnt[count($defsAndCnt) - 1];
						}
						if (preg_match('/value="(.+?)"/', $wrap, $opval)) {
							$op['v'] = $opval[1];
						}

						if (preg_match('/data-label="(.+?)"/', $wrap, $optit)) {
							$op['l'] = $optit[1];
						}

						/**
						 * 获取数据
						 */
						$q = array(
							'count(*)',
							'xxt_enroll_record_data',
							"name='$id' and value='{$op['v']}'",
						);
						$op['c'] = $this->query_val_ss($q);

						$d['ops'][] = $op;
					} else if (preg_match('/type="checkbox"/', $wrap)) {
						/**
						 * for checkbox group.
						 */
						if (preg_match('/ng-model="data\.(.+?)\.(.+?)"/', $wrap, $ngmodel)) {
							$id = $ngmodel[1];
							$opval = $ngmodel[2];
						}
						$existing = false;
						foreach ($defsAndCnt as &$d) {
							if ($existing = ($d['id'] === $id)) {
								break;
							}
						}

						if (!$existing) {
							$defsAndCnt[] = array('title' => $title, 'id' => $id, 'ops' => array());
							$d = &$defsAndCnt[count($defsAndCnt) - 1];
						}
						$op['v'] = $opval;
						if (preg_match('/data-label="(.+?)"/', $wrap, $optit)) {
							$op['l'] = $optit[1];
						}

						/**
						 * 获取数据
						 */
						$q = array(
							'count(*)',
							'xxt_enroll_record_data',
							"name='$id' and FIND_IN_SET('$opval', value)",
						);
						$op['c'] = $this->query_val_ss($q);
						//
						$d['ops'][] = $op;
					}
				}
			}
		}

		return $defsAndCnt;
	}
	/**
	 * 根据邀请到的用户数量进行的排名
	 */
	public function rankByFollower($mpid, $aid, $openid) {
		$modelRec = \TMS_APP::M('app\enroll\record');
		$last = $modelRec->getLast($mpid, $aid, $openid);

		$q = array(
			'count(*)',
			'xxt_enroll_record',
			"state=1 and aid='$aid' and follower_num>$last->follower_num",
		);

		$rank = (int) $this->query_val_ss($q);

		return $rank + 1;
	}
}