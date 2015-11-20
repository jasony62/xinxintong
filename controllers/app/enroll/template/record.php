<?php
namespace app\enroll\template;

/**
 * 登记活动
 */
class record extends \TMS_CONTROLLER {
	/**
	 * 列出所有的登记记录
	 *
	 * $mpid
	 * $aid
	 * $orderby
	 * $openid
	 * $page
	 * $size
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 *
	 */
	public function list_action($mpid, $aid, $rid = '', $orderby = 'time', $openid = null, $page = 1, $size = 10) {
		$user = $this->getUser($mpid);

		$options = array(
			'creater' => $openid,
			'visitor' => $user->openid,
			'rid' => $rid,
			'page' => $page,
			'size' => $size,
			'orderby' => $orderby,
		);

		$modelRec = $this->model('app\enroll\record');
		$rst = $modelRec->find($mpid, $aid, $options);

		return new \ResponseData($rst);
	}
	/**
	 * 列出当前访问用户所有的登记记录
	 *
	 * @return
	 * 数据列表
	 * 数据总条数
	 * 数据项的定义
	 *
	 */
	public function mine_action() {
		$result = array(
			'total' => 2,
			'schema' => array(
				array('id' => 'c1', 'title' => '任务名称'),
				array('id' => 'c2', 'title' => '本周情况'),
				array('id' => 'c3', 'title' => '下周计划'),
			),
			'records' => array(
				array(
					'enroll_key' => 'ek2',
					"enroll_at" => "1447924405",
					"signin_at" => "0",
					"tags" => null,
					"follower_num" => "0",
					"score" => null,
					"remark_num" => "0",
					"fid" => "b6917a20487c5831f56d71ff64b17498",
					"nickname" => "mocker",
					"openid" => "mocker",
					"headimgurl" => "",
					"data" => array(
						"member" => "{}", "c1" => "项目2", "c2" => "未完成", "c3" => "暂停"), "signinLogs" => array(),
				),
				array(
					'enroll_key' => 'ek1',
					"enroll_at" => "1447924388",
					"signin_at" => "0",
					"tags" => null,
					"follower_num" => "0",
					"score" => null,
					"remark_num" => "0",
					"fid" => "b6917a20487c5831f56d71ff64b17498",
					"nickname" => "mocker",
					"openid" => "mocker",
					"headimgurl" => "",
					"data" => array("member" => "{}", "c1" => "项目1", "c2" => "完成", "c3" => "继续"),
					"signinLogs" => array(),
				),
			),
		);
		return new \ResponseData($result);
	}
	/**
	 * 列出当前访问用户所有的登记记录
	 *
	 * $mpid
	 * $aid
	 * $orderby
	 * $page
	 * $size
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 *
	 */
	public function myFollowers_action($mpid, $aid, $rid = '', $orderby = 'time', $page = 1, $size = 10) {
		$modelRec = $this->model('app\enroll\record');

		$user = $this->getUser($mpid);

		$options = array(
			'inviter' => $user->openid,
			'rid' => $rid,
			'page' => $page,
			'size' => $size,
			'orderby' => $orderby,
		);

		$rst = $modelRec->find($mpid, $aid, $options);

		return new \ResponseData($rst);
	}
	/**
	 * 登记记录点赞
	 *
	 * $mpid
	 * $ek
	 */
	public function score_action($mpid, $ek) {
		$modelEnroll = $this->model('app\enroll');
		/**
		 * 当前活动
		 */
		$q = array('aid', 'xxt_enroll_record', "enroll_key='$ek'");
		$aid = $this->model()->query_val_ss($q);
		$act = $modelEnroll->byId($aid);
		/**
		 * 当前用户
		 */
		$user = $this->getUser($mpid);
		$modelRec = $this->M('app\model\record');
		if ($modelRec->hasScored($user->openid, $ek)) {
			/**
			 * 点了赞，再次点击，取消赞
			 */
			$this->model()->delete(
				'xxt_enroll_record_score',
				"enroll_key='$ek' and openid='$openid'"
			);
			$myScore = 0;
		} else {
			/**
			 * 点赞
			 */
			$i = array(
				'openid' => $openid,
				'enroll_key' => $ek,
				'create_at' => time(),
				'score' => 1,
			);
			$this->model()->insert('xxt_enroll_record_score', $i, false);
			$myScore = 1;
		}
		/**
		 * 获得点赞的总数
		 */
		$score = $modelRec->score($ek);
		$this->model()->update('xxt_enroll_record', array('score' => $score), "enroll_key='$ek'");

		return new \ResponseData(array($myScore, $score));
	}
	/**
	 * 针对登记记录发表评论
	 *
	 * $mpid
	 * $ek
	 */
	public function remark_action($mpid, $ek) {
		$data = $this->getPostJson();
		if (empty($data->remark)) {
			return new \ResponseError('评论不允许为空！');
		}

		$modelEnroll = $this->model('app\enroll');
		/**
		 * 当前活动
		 */
		$q = array('aid,openid', 'xxt_enroll_record', "enroll_key='$ek'");
		$record = $this->model()->query_obj_ss($q);
		$aid = $record->aid;
		$act = $modelEnroll->byId($aid);
		/**
		 * 发表评论的用户
		 */
		$user = $this->getUser($mpid);
		if (empty($user->openid)) {
			return new \ResponseError('无法获得用户身份标识');
		}

		$remark = array(
			'openid' => $user->openid,
			'enroll_key' => $ek,
			'create_at' => time(),
			'remark' => $this->model()->escape($data->remark),
		);
		$remark['id'] = $this->model()->insert('xxt_enroll_record_remark', $remark, true);
		$remark['nickname'] = $user->nickname;
		$this->model()->update("update xxt_enroll_record set remark_num=remark_num+1 where enroll_key='$ek'");
		/**
		 * 通知登记人有评论
		 */
		if ($act->remark_notice === 'Y' && !empty($act->remark_notice_page)) {
			$apis = $this->model('mp\mpaccount')->getApis($mpid);
			if ($apis && $apis->{$apis->mpsrc . '_custom_push'} === 'Y') {
				/**
				 * 发送评论提醒
				 */
				$url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/enroll?mpid=$mpid&aid=$aid&ek=$ek&page=$act->remark_notice_page";
				$text = urlencode($remark['nickname'] . '对【');
				$text .= '<a href="' . $url . '">';
				$text .= urlencode($act->title);
				$text .= '</a>';
				$text .= urlencode('】发表了评论：' . $remark['remark']);
				$message = array(
					"msgtype" => "text",
					"text" => array(
						"content" => $text,
					),
				);
				/**
				 * 通知登记人
				 */
				if ($this->model('log')->canReceivePush($mpid, $record->openid)) {
					if ($record->openid !== $user->openid) {
						$this->send_to_user($mpid, $record->openid, $message);
					}
				}
				/**
				 * 通知其他发表了评论的用户
				 */
				$modelRec = $this->model('app\enroll\record');
				$others = $modelRec->remarkers($ek);
				foreach ($others as $other) {
					if ($other->openid === $record->openid || $other->openid === $remarker->openid) {
						continue;
					}
					$this->send_to_user($mpid, $other->openid, $message);
				}
			}
		}

		return new \ResponseData($remark);
	}
}