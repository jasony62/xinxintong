<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记记录的评论
 */
class remark extends \pl\fe\matter\base {
	/**
	 * 返回一条登记记录的所有评论
	 *
	 * @param string $ek
	 * @param string $schema schema's id，如果不指定，返回的是对整条记录的评论
	 *
	 */
	public function list_action($ek, $schema = '', $page = 1, $size = 99) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oUser = new \stdClass;

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
	/**
	 * 给指定的登记记录的添加评论
	 * 需要处理用户没有提交评论的登记项数据的情况（用户提交数据后又增加了登记项）
	 */
	public function add_action($ek, $schema = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$data = $this->getPostJson();
		if (empty($data->content)) {
			return new \ResponseError('评论内容不允许为空');
		}
		$current = time();

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
		/**
		 * 发表评论的用户
		 */
		$remark = new \stdClass;
		$remark->userid = $user->id;
		$remark->user_src = 'P';
		$remark->nickname = $user->name;
		$remark->enroll_key = $ek;
		$remark->schema_id = $schema;
		$remark->create_at = $current;
		$remark->content = $modelRec->escape($data->content);

		$remark->id = $modelRec->insert('xxt_enroll_record_remark', $remark, true);

		$modelRec->update("update xxt_enroll_record set remark_num=remark_num+1 where enroll_key='$ek'");

		if (isset($schema)) {
			$oSchemaData = $modelRec->query_obj_ss(['id', 'xxt_enroll_record_data', ['enroll_key' => $ek, 'schema_id' => $schema, 'state' => 1]]);
			if ($oSchemaData) {
				$modelRec->update("update xxt_enroll_record_data set remark_num=remark_num+1,last_remark_at=$current where id='{$oSchemaData->id}'");
			} else {
				/* 用户没有提交过数据，创建一条记录 */
				$aNewSchemaData = [
					'aid' => $oRecord->aid,
					'rid' => $oRecord->rid,
					'enroll_key' => $ek,
					'submit_at' => $oRecord->enroll_at,
					'userid' => $oRecord->userid,
					'schema_id' => $schema,
					'remark_num' => 1,
				];
				$modelRec->insert('xxt_enroll_record_data', $aNewSchemaData, false);
			}
		}

		$this->_notifyHasRemark($oApp, $oRecord, $remark);

		return new \ResponseData($remark);
	}
	/**
	 * 给登记人发送评论通知
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
		$noticeURL = $this->model('matter\enroll')->getEntryUrl($oApp->siteid, $oApp->id);
		$noticeURL .= '&page=remark&ek=' . $oRecord->enroll_key;
		$params->url = $noticeURL;

		/* 消息的创建人 */
		$creater = new \stdClass;
		$creater->uid = $oRemark->userid;
		$creater->name = $oRemark->nickname;
		$creater->src = 'pl';

		/* 消息的接收人 */
		$receiver = new \stdClass;
		$receiver->assoc_with = $oRecord->enroll_key;
		$receiver->userid = $oRecord->userid;

		/* 给用户发通知消息 */
		$modelTmplBat = $this->model('matter\tmplmsg\batch');
		$modelTmplBat->send($oRecord->siteid, $tmplConfig->msgid, $creater, [$receiver], $params, ['send_from' => 'enroll:' . $oRecord->aid . ':' . $oRecord->enroll_key]);
	}
	/**
	 *
	 */
	public function agree_action($remark, $value = '') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelRem = $this->model('matter\enroll\remark');
		if ($value !== 'Y' && $value !== 'N') {
			$value = '';
		}

		$rst = $modelRem->update(
			'xxt_enroll_record_remark',
			['agreed' => $value],
			['id' => $remark]
		);

		return new \ResponseData($rst);
	}

}