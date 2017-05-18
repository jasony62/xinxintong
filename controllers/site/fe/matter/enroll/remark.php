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
	public function list_action($ek, $schema = '', $page = 1, $size = 99) {
		$oUser = $this->who;

		$result = $this->model('matter\enroll\remark')->listByRecord($oUser, $ek, $schema, $page, $size);

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
	/*
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
		$current = time();
		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oRecord->aid, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		/**
		 * 发表评论的用户
		 */
		$remark = new \stdClass;
		$remark->userid = $this->who->uid;
		$remark->user_src = 'S';
		$remark->nickname = $this->who->nickname;
		$remark->enroll_key = $ek;
		$remark->schema_id = $schema;
		$remark->create_at = $current;
		$remark->content = $modelRec->escape($data->content);

		$remark->id = $modelRec->insert('xxt_enroll_record_remark', $remark, true);

		$modelRec->update("update xxt_enroll_record set remark_num=remark_num+1 where enroll_key='$ek'");
		if (isset($schema)) {
			$modelRec->update("update xxt_enroll_record_data set remark_num=remark_num+1,last_remark_at=$current where enroll_key='$ek' and schema_id='$schema'");
		}

		$this->_notifyHasRemark($oApp, $oRecord, $remark);

		return new \ResponseData($remark);
	}

	/**
	 * 通知登记活动事件接收人
	 */
	private function _notifyHasRemark($oApp, $oRecord, $oRemark) {
		$receivers = $this->model('matter\enroll\receiver')->byApp($oApp->siteid, $oApp->id);
		if (count($receivers) === 0) {
			return false;
		}
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

		/* 获得活动的管理员链接 */
		$noticeURL = 'http://' . $_SERVER['HTTP_HOST'];
		$noticeURL .= '/rest/pl/fe/matter/enroll/editor?site=' . $oApp->siteid;
		$noticeURL .= '&id=' . $oApp->id;
		$noticeURL .= '&ek=' . $oRecord->enroll_key;
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
		$oRemark = $modelRem->byId($remark, ['fields' => 'id,like_log']);
		if (false === $oRemark) {
			return new \ObjectNotFoundError();
		}

		$oLikeLog = $oRemark->like_log;

		$oUser = $this->who;

		if (isset($oLikeLog->{$oUser->uid})) {
			unset($oLikeLog->{$oUser->uid});
		} else {
			$oLikeLog->{$oUser->uid} = time();
		}
		$likeNum = count(get_object_vars($oLikeLog));

		$modelRem->update(
			'xxt_enroll_record_remark',
			['like_log' => json_encode($oLikeLog), 'like_num' => $likeNum],
			['id' => $oRemark->id]
		);

		return new \ResponseData(['like_log' => $oLikeLog, 'like_num' => $likeNum]);
	}
}