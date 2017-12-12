<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记数据
 */
class data extends \pl\fe\matter\base {
	/**
	 * 根据record中的data数据，修复reocrd_data
	 */
	public function repairByRecord_action($ek = '') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek);
		if (false === $oRecord) {
			return new \ObjectNotFoundError();
		}
		$oApp = $this->model('matter\enroll')->byId($oRecord->aid, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$oUser = new \stdClass;
		$oUser->uid = $oRecord->userid;
		$oUser->group_id = $oRecord->group_id;

		$this->model('matter\enroll\data')->setData($oUser, $oApp, $oRecord, $oRecord->data);

		return new \ResponseData('ok');
	}
	/**
	 * 根据record中的data数据，修复reocrd_data
	 */
	public function repairByApp_action($app) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$modelRec = $this->model('matter\enroll\record');
		$modelRecDat = $this->model('matter\enroll\data');
		$result = $modelRec->byApp($oApp->id);

		foreach ($result->records as $oRecord) {
			$oUser = new \stdClass;
			$oUser->uid = $oRecord->userid;
			$oUser->group_id = $oRecord->group_id;

			$modelRecDat->setData($oUser, $oApp, $oRecord, $oRecord->data);
		}

		return new \ResponseData(count($result->records));
	}
	/**
	 *
	 */
	public function agree_action($ek, $schema, $value = '') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelData = $this->model('matter\enroll\data');
		$oRecData = $modelData->byRecord($ek, ['schema' => $schema, 'fields' => 'aid,userid,agreed,agreed_log']);
		if (false === $oRecData) {
			return new \ObjectNotFoundError();
		}

		if (!in_array($value, ['Y', 'N', 'A'])) {
			$value = '';
		}

		$oAgreedLog = $oRecData->agreed_log;
		if (isset($oAgreedLog->{$oUser->id})) {
			$oLog = $oAgreedLog->{$oUser->id};
			$oLog->time = time();
			$oLog->value = $value;
		} else {
			$oAgreedLog->{$oUser->id} = (object) ['time' => time(), 'value' => $value];
		}

		//确定模板名称
		if ($value == 'Y') {
			$name = 'site.enroll.submit.recommend';
		} else if ($value == 'N') {
			$name = 'site.enroll.submit.mask';
		}

		if (!empty($name)) {
			$modelRec = $this->model('matter\enroll\record');
			$oRecord = $modelRec->byId($ek);
			$modelEnl = $this->model('matter\enroll');
			$oApp = $modelEnl->byId($oRecord->aid, ['cascaded' => 'N']);
			$this->_notifyAgree($oApp, $oRecord, $name, $schema);
		}

		$rst = $modelData->update(
			'xxt_enroll_record_data',
			['agreed' => $value, 'agreed_log' => json_encode($oAgreedLog)],
			['enroll_key' => $ek, 'schema_id' => $schema, 'state' => 1]
		);

		return new \ResponseData($rst);
	}
	/**
	 * 给登记人发送评论通知
	 */
	private function _notifyAgree($oApp, $oRecord, $tmplName, $schema) {
		/* 模板消息参数 */
		$notice = $this->model('site\notice')->byName($oApp->siteid, $tmplName);
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
				} else if ($mapping->id === 'event_at') {
					$value = date('Y-m-d H:i:s');
				}
			} else if ($mapping->src === 'text') {
				$value = $mapping->name;
			}
			!isset($value) && $value = '';
			$params->{$param->pname} = $value;
		}

		/* 获得活动的用户链接 */
		$noticeURL = $this->model('matter\enroll')->getEntryUrl($oApp->siteid, $oApp->id);
		$noticeURL .= '&page=remark&ek=' . $oRecord->enroll_key;
		$noticeURL .= '&schema=' . $schema;
		$params->url = $noticeURL;

		/* 消息的创建人 */
		$modelWay = $this->model('site\fe\way');
		$who = $modelWay->who($oRecord->siteid);
		$creater = new \stdClass;
		$creater->uid = $who->uid;
		$creater->name = $who->nickname;
		$creater->src = 'pl';

		/* 消息的接收人 */
		$receiver = new \stdClass;
		$receiver->assoc_with = $oRecord->enroll_key;
		$receiver->userid = $oRecord->userid;

		/*判断是否是同一个人*/
		if ($creater->uid == $receiver->userid) {
			return false;
		}

		/* 给用户发通知消息 */
		$modelTmplBat = $this->model('matter\tmplmsg\batch');
		$modelTmplBat->send($oRecord->siteid, $tmplConfig->msgid, $creater, [$receiver], $params, ['send_from' => 'enroll:' . $oRecord->aid . ':' . $oRecord->enroll_key]);
	}
	/**
	 * 返回指定登记项的活动登记名单
	 */
	public function list4Schema_action($app, $page = 1, $size = 12) {
		if (false === ($oLoginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['fields' => 'id,data_schemas', 'cascaded' => 'N']);
		// 登记数据过滤条件
		$oCriteria = $this->getPostJson();

		// 登记记录过滤条件
		$oOptions = new \stdClass;
		$oOptions->page = $page;
		$oOptions->size = $size;

		!empty($oCriteria->keyword) && $oOptions->keyword = $oCriteria->keyword;
		!empty($oCriteria->rid) && $oOptions->rid = $oCriteria->rid;
		!empty($oCriteria->agreed) && $oOptions->agreed = $oCriteria->agreed;
		!empty($oCriteria->owner) && $oOptions->owner = $oCriteria->owner;
		!empty($oCriteria->tag) && $oOptions->tag = $oCriteria->tag;
		if (empty($oCriteria->schema)) {
			$oOptions->schemas = [];
			foreach ($oApp->dataSchemas as $dataSchema) {
				if (isset($dataSchema->shareable) && $dataSchema->shareable === 'Y') {
					$oOptions->schemas[] = $dataSchema->id;
				}
			}
			if (empty($oOptions->schemas)) {
				return new \ResponseData(['total' => 0]);
			}
		} else {
			$oOptions->schemas = [$oCriteria->schema];
		}

		$oUser = new \stdClass;

		// 查询结果
		$mdoelData = $this->model('matter\enroll\data');
		$result = $mdoelData->byApp($oApp, $oUser, $oOptions);
		if (count($result->records)) {
			$modelRem = $this->model('matter\enroll\remark');
			foreach ($result->records as &$oRec) {
				if ($oRec->remark_num) {
					$agreedRemarks = $modelRem->listByRecord($oUser, $oRec->enroll_key, $oRec->schema_id, $page = 1, $size = 10, ['agreed' => 'Y', 'fields' => 'id,content,create_at,nickname,like_num,like_log']);
					if ($agreedRemarks->total) {
						$oRec->agreedRemarks = $agreedRemarks;
					}
				}
				$oRec->tag = empty($oRec->tag) ? [] : json_decode($oRec->tag);
			}
		}

		return new \ResponseData($result);
	}
}