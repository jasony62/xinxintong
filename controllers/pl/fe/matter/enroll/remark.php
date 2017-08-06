<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记记录的评论
 */
class remark extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回一条登记记录的所有评论
	 *
	 * @param string $ek
	 * @param string $schema schema's id，如果不指定，返回的是对整条记录的评论
	 *
	 */
	public function list_action($ek, $schema = '', $page = 1, $size = 99) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		// 会按照指定的用户id进行过滤，所以去掉用户id，获得所有数据
		$oUser = new \stdClass;

		$result = $this->model('matter\enroll\remark')->listByRecord($oUser, $ek, $schema, $page, $size);

		return new \ResponseData($result);
	}
	/**
	 * 返回指定活动下所有评论
	 */
	public function byApp_action($site, $app, $page = 1, $size = 30) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$oCriteria = $this->getPostJson();
		$options = [
			'fields' => 'id,userid,user_src,create_at,nickname,content,agreed,like_num,schema_id,enroll_key',
			'criteria' => $oCriteria,
		];
		$result = $this->model('matter\enroll\remark')->listByApp($oApp, $page, $size, $options);

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
		$oRemark = new \stdClass;
		$oRemark->siteid = $oRecord->siteid;
		$oRemark->aid = $oRecord->aid;
		$oRemark->rid = $oRecord->rid;
		$oRemark->userid = $user->id;
		$oRemark->user_src = 'P';
		$oRemark->nickname = $modelRec->escape($user->name);
		$oRemark->enroll_key = $ek;
		$oRemark->enroll_userid = $oRecord->userid;
		$oRemark->schema_id = $schema;
		$oRemark->create_at = $current;
		$oRemark->content = $modelRec->escape($data->content);

		$oRemark->id = $modelRec->insert('xxt_enroll_record_remark', $oRemark, true);

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

		$this->_notifyHasRemark($oApp, $oRecord, $oRemark);

		return new \ResponseData($oRemark);
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

		/* 获得活动的用户链接 */
		$noticeURL = $this->model('matter\enroll')->getEntryUrl($oApp->siteid, $oApp->id);
		$noticeURL .= '&page=remark&ek=' . $oRecord->enroll_key;
		$noticeURL .= '&schema=' . $oRemark->schema_id;
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
	public function agree_action($value = '') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$posted = $this->getPostJson();
		if (empty($posted->remark)) {
			return new \ParameterError('没有指定评论数据');
		}
		if (is_array($posted->remark)) {
			$remarkIds = $posted->remark;
		} else {
			$remarkIds = [$posted->remark];
		}

		$modelRem = $this->model('matter\enroll\remark');
		if (!in_array($value, ['Y', 'N', 'A'])) {
			$value = '';
		}
		
		//确定模板名称
		if($value==='Y'){
			$name='site.enroll.remark.recommend';
		}else if($value==='N'){
			$name='site.enroll.remark.mask';
		}

		if(!empty($name)){
			$oRemark=$modelRem->query_obj_ss(['*','xxt_enroll_record_remark',['id'=>$posted->remark]]);
			$modelEnl = $this->model('matter\enroll');
			$oApp = $modelEnl->byId($oRemark->aid, ['cascaded' => 'N']);
			$modelRec = $this->model('matter\enroll\record');
			$oRecord = $modelRec->byId($oRemark->enroll_key);
			$oRemark->enroll_nickname=$modelRem->query_val_ss(['uname','xxt_site_account',['siteid'=>$oRemark->siteid,'uid'=>$oRemark->enroll_userid]]);
			$this->_notifyAgree($oApp, $oRecord, $oRemark, $name);
		}
		
		foreach ($remarkIds as $id) {
			$rst = $modelRem->update(
				'xxt_enroll_record_remark',
				['agreed' => $value],
				['id' => $id]
			);
		}

		return new \ResponseData($rst);
	}
	/**
	 * 给发评论的人发送通知
	 */
	private function _notifyAgree($oApp, $oRecord, $oRemark, $tmplName) {
		/* 模板消息参数 */
		$notice = $this->model('site\notice')->byName($oApp->siteid, $tmplName);
		if ($notice === false) {
			return false;
		}
		$tmplConfig = $this->model('matter\tmplmsg\config')->byId($notice->tmplmsg_config_id, ['cascaded' => 'Y']);
		if (!isset($tmplConfig->tmplmsg)) {
			return false;
		}
		$at=array('submit_mask_at','submit_recommend_at','remark_mask_at','remark_recommend_at');
		$params = new \stdClass;
		foreach ($tmplConfig->tmplmsg->params as $param) {
			if (!isset($tmplConfig->mapping->{$param->pname})) {
				continue;
			}
			$mapping = $tmplConfig->mapping->{$param->pname};
			if ($mapping->src === 'matter') {
				if (isset($oApp->{$mapping->id})) {
					$value = $oApp->{$mapping->id};
				}else if(in_array($mapping->id, $at)){
					$value = date('Y-m-d H:i:s');
				}else if($mapping->id=='submit_at'){
					$value = date('Y-m-d H:i:s',$oRecord->enroll_at);
				}else if($mapping->id=='remark_at'){
					$value = date('Y-m-d H:i:s',$oRemark->create_at);
				}
			} else if ($mapping->src === 'text') {
				$value = $mapping->name;
			}
			!isset($value) && $value = '';
			$params->{$param->pname} = $value;
		}

		/* 获得活动的用户链接 */
		$noticeURL = $this->model('matter\enroll')->getEntryUrl($oApp->siteid, $oApp->id);
		$noticeURL .= '&page=remark&ek=' . $oRemark->enroll_key;
		$noticeURL .= '&schema=' . $oRemark->schema_id;
		$params->url = $noticeURL;

		/* 消息的创建人 */
		$modelWay = $this->model('site\fe\way');
		$who=$modelWay->who($oRemark->siteid);
		$creater = new \stdClass;
		$creater->uid = $who->uid;
		$creater->name = $who->nickname;
		$creater->src = 'pl';

		/* 消息的接收人 */
		$receiver = new \stdClass;
		$receiver->assoc_with = $oRemark->enroll_key;
		$receiver->userid = $oRemark->userid;

		/*判断是否是同一个人*/
		if($creater->uid==$receiver->userid){
			return false;
		}

		/* 给用户发通知消息 */
		$modelTmplBat = $this->model('matter\tmplmsg\batch');
		$modelTmplBat->send($oRemark->siteid, $tmplConfig->msgid, $creater, [$receiver], $params, ['send_from' => 'enroll:' . $oRemark->aid . ':' . $oRemark->enroll_key]);
	}
}