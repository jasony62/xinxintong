<?php
namespace pl\fe\matter\link;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 文章控制器
 */
class attachment extends \pl\fe\matter\base {
	/**
	 * 分段上传附件
	 */
	public function upload_action($site, $linkId) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\link');
		$oApp = $modelApp->byId($linkId);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$rst = $this->attachmentUpload($oApp, $_POST);

		return new \ResponseData($rst);
	}
	/**
	 * 上传成功后将附件信息保存到数据库中
	 */
	public function add_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\link');
		$oApp = $modelApp->byId($id);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$file = $this->getPostJson();
		$oAtt = $this->attachmentAdd($oApp, $file);
		if ($oAtt[0] === false) {
			return new ResponseError($oAtt[1]);
		}

		return new \ResponseData($oAtt[1]);
	}
	/**
	 * 删除附件
	 */
	public function del_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->attachmentDel($site, $id);
		if ($rst[0] === false) {
			return new ResponseError($rst[1]);
		}

		return new \ResponseData($rst);
	}
}