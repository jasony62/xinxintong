<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动登记记录评论
 */
class remark extends base {
	/**
	 * 返回一条登记记录的所有评论
	 */
	public function list_action($ek, $schema, $page = 1, $size = 99) {
		$oUser = $this->who;

		$oRecordData = $this->model('matter\enroll\data')->byRecord($ek, ['schema' => $schema, 'fields' => 'id,agreed,value,like_num,like_log,remark_num,supplement']);

		$result = $this->model('matter\enroll\remark')->listByRecord($oUser, $ek, $schema, $page, $size);

		$result->data = $oRecordData;

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function summary_action($ek) {
		$q = [
			'schema_id,remark_num,last_remark_at',
			'xxt_enroll_record_data',
			['enroll_key' => $ek],
		];
		$values = $this->model()->query_objs_ss($q);

		return new \ResponseData($values);
	}
	/**
	 * 给指定的登记记录的添加评论
	 */
	public function add_action($ek, $schema = '') {
		$data = $this->getPostJson();
		if (empty($data->content)) {
			return new \ResponseError('评论内容不允许为空');
		}

		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek);
		if (false === $oRecord) {
			return new \ObjectNotFoundError();
		}
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRecord->aid, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->who;
		$userNickname = $modelEnl->getUserNickname($oApp, $oUser);
		$oUser->nickname = $userNickname;

		/**
		 * 发表评论的用户
		 */
		$current = time();
		$remark = new \stdClass;
		$remark->siteid = $oRecord->siteid;
		$remark->aid = $oRecord->aid;
		$remark->userid = $oUser->uid;
		$remark->user_src = 'S';
		$remark->nickname = $oUser->nickname;
		$remark->enroll_key = $ek;
		$remark->enroll_userid = $oRecord->userid;
		$remark->schema_id = $schema;
		$remark->create_at = $current;
		$remark->content = $modelRec->escape($data->content);

		$remark->id = $modelRec->insert('xxt_enroll_record_remark', $remark, true);

		$modelRec->update("update xxt_enroll_record set remark_num=remark_num+1 where enroll_key='$ek'");
		if (isset($schema)) {
			$modelRec->update("update xxt_enroll_record_data set remark_num=remark_num+1,last_remark_at=$current where enroll_key='$ek' and schema_id='$schema'");
		}

		$modelUsr = $this->model('matter\enroll\user');
		/* 更新发起评论的活动用户数据 */
		$oEnrollUsr = $modelUsr->byId($oApp, $oUser->uid, ['fields' => 'id,nickname,last_remark_other_at,remark_other_num']);
		if (false === $oEnrollUsr) {
			$modelUsr->add($oApp, $oUser, ['last_remark_other_at' => time(), 'remark_other_num' => 1]);
		} else {
			$modelUsr->update(
				'xxt_enroll_user',
				['last_remark_other_at' => time(), 'remark_other_num' => $oEnrollUsr->remark_other_num + 1],
				['id' => $oEnrollUsr->id]
			);
		}
		/* 更新被评论的活动用户数据 */
		$oEnrollUsr = $modelUsr->byId($oApp, $oRecord->userid, ['fields' => 'id,nickname,last_remark_at,remark_num']);
		if ($oEnrollUsr) {
			$modelUsr->update(
				'xxt_enroll_user',
				['last_remark_at' => time(), 'remark_num' => $oEnrollUsr->remark_num + 1],
				['id' => $oEnrollUsr->id]
			);
		}

		$this->_notifyHasRemark($oApp, $oRecord, $remark);

		return new \ResponseData($remark);
	}
	/**
	 * 通知评论登记记录事件
	 */
	private function _notifyHasRemark($oApp, $oRecord, $oRemark) {
		/* 模板消息参数 */
		$notice = $this->model('site\notice')->byName($oApp->siteid, 'site.enroll.remark');
		if ($notice === false) {
			return false;
		}
		$tmplConfig = $this->model('matter\tmplmsg\config')->byId($notice->tmplmsg_config_id, ['cascaded' => 'Y']);
		if (!isset($tmplConfig->tmplmsg)) {
			return false;
		}

		$params = new \stdClass;
		foreach ($tmplConfig->tmplmsg->params as $param) {
			if (!isset($tmplConfig->mapping->{$param->pname})) {
				continue;
			}
			$mapping = $tmplConfig->mapping->{$param->pname};
			if ($mapping->src === 'matter') {
				if (isset($oApp->{$mapping->id})) {
					$value = $oApp->{$mapping->id};
				}
			} else if ($mapping->src === 'text') {
				$value = $mapping->name;
			}
			!isset($value) && $value = '';
			$params->{$param->pname} = $value;
		}
		/**
		 * 给记录的提交人发送通知
		 */
		if ($oRecord->userid !== $oRemark->userid) {
			/* 发送给登记记录的提交人 */
			$noticeURL = $this->model('matter\enroll')->getEntryUrl($oApp->siteid, $oApp->id);
			$noticeURL .= '&page=remark&ek=' . $oRecord->enroll_key;
			$noticeURL .= '&schema=' . $oRemark->schema_id;
			$params->url = $noticeURL;

			/* 消息的接收人 */
			$oEnroller = new \stdClass;
			$oEnroller->assoc_with = $oRecord->enroll_key;
			$oEnroller->userid = $oRecord->userid;

			/* 消息的创建人 */
			$oCreator = new \stdClass;
			$oCreator->uid = $oRemark->userid;
			$oCreator->name = $oRemark->nickname;
			$oCreator->src = 'site';

			/* 给用户发通知消息 */
			$modelTmplBat = $this->model('matter\tmplmsg\batch');
			$modelTmplBat->send($oRecord->siteid, $tmplConfig->msgid, $oCreator, [$oEnroller], $params, ['send_from' => 'enroll:' . $oRecord->aid . ':' . $oRecord->enroll_key]);
		}
		/**
		 * 给活动管理员发送通知
		 */
		$receivers = $this->model('matter\enroll\receiver')->byApp($oApp->siteid, $oApp->id);
		if (count($receivers)) {
			/* 获得活动的管理员链接 */
			$appURL = $this->model('matter\enroll')->getOpUrl($oApp->siteid, $oApp->id);
			$modelQurl = $this->model('q\url');
			$noticeURL = $modelQurl->urlByUrl($oApp->siteid, $appURL);
			$params->url = $noticeURL;

			/* 发送消息 */
			foreach ($receivers as &$receiver) {
				if (!empty($receiver->sns_user)) {
					$snsUser = json_decode($receiver->sns_user);
					if (isset($snsUser->src) && isset($snsUser->openid)) {
						$receiver->{$snsUser->src . '_openid'} = $snsUser->openid;
					}
				}
			}
			/* 发送给活动管理员 */
			$modelTmplBat = $this->model('matter\tmplmsg\plbatch');
			$modelTmplBat->send($oApp->siteid, $tmplConfig->msgid, $receivers, $params, ['event_name' => 'site.enroll.remark', 'send_from' => 'enroll:' . $oApp->id . ':' . $oRemark->enroll_key]);
		}

		return true;
	}
	/**
	 * 点赞登记记录中的某一个题
	 *
	 * @param string $remark remark'id
	 *
	 */
	public function like_action($remark) {
		$modelRem = $this->model('matter\enroll\remark');
		$oRemark = $modelRem->byId($remark, ['fields' => 'aid,id,like_log']);
		if (false === $oRemark) {
			return new \ObjectNotFoundError();
		}

		$oLikeLog = $oRemark->like_log;

		$oUser = $this->who;

		if (isset($oLikeLog->{$oUser->uid})) {
			unset($oLikeLog->{$oUser->uid});
			$incLikeNum = -1;
		} else {
			$oLikeLog->{$oUser->uid} = time();
			$incLikeNum = 1;
		}
		$likeNum = count(get_object_vars($oLikeLog));

		$modelRem->update(
			'xxt_enroll_record_remark',
			['like_log' => json_encode($oLikeLog), 'like_num' => $likeNum],
			['id' => $oRemark->id]
		);

		$oApp = new \stdClass;
		$oApp->id = $oRemark->aid;
		$modelUsr = $this->model('matter\enroll\user');
		/* 更新进行点赞的活动用户的数据 */
		$oEnrollUsr = $modelUsr->byId($oApp, $this->who->uid, ['fields' => 'id,nickname,last_like_other_remark_at,like_other_remark_num']);
		if (false === $oEnrollUsr) {
			$modelUsr->add($oApp, $this->who, ['last_like_other_remark_at' => time(), 'like_other_remark_num' => 1]);
		} else {
			$modelUsr->update(
				'xxt_enroll_user',
				['last_like_other_remark_at' => time(), 'like_other_remark_num' => $oEnrollUsr->like_other_remark_num + $incLikeNum],
				['id' => $oEnrollUsr->id]
			);
		}
		/* 更新被点赞的活动用户的数据 */
		$oEnrollUsr = $modelUsr->byId($oApp, $this->who->uid, ['fields' => 'id,nickname,last_like_remark_at,like_remark_num']);
		if ($oEnrollUsr) {
			$modelUsr->update(
				'xxt_enroll_user',
				['last_like_remark_at' => time(), 'like_remark_num' => $oEnrollUsr->like_remark_num + $incLikeNum],
				['id' => $oEnrollUsr->id]
			);
		}

		return new \ResponseData(['like_log' => $oLikeLog, 'like_num' => $likeNum]);
	}
}