<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 记录活动附件
 */
class attachment extends \pl\fe\matter\base {
	/**
	 * 分段上传附件
	 */
	public function upload_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$rst = $this->attachmentUpload($oApp, $_POST);

		return new \ResponseData($rst);
	}
	/**
	 * 上传成功后将附件信息保存到数据库中
	 */
	public function add_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oFile = $this->getPostJson();
		$oAtt = $this->attachmentAdd($oApp, $oFile);
		if ($oAtt[0] === false) {
			return new ResponseError($oAtt[1]);
		}

		return new \ResponseData($oAtt);
	}
	/**
	 * 删除附件???
	 */
	public function del_action($site, $id) {
		return new \ResponseData('not support');
	}
}