<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动附件
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

		$dest = '/enroll/' . $oApp->id . '/' . $_POST['resumableFilename'];
		$oResumable = $this->model('fs/resumable', $oApp->siteid, $dest, '_attachment');
		$oResumable->handleRequest($_POST);

		return new \ResponseData('ok');
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

		if (defined('APP_FS_USER') && APP_FS_USER === 'ali-oss') {
			/* 文件存储在阿里 */
			$url = 'alioss://enroll/' . $oApp->id . '/' . $oFile->name;
		} else {
			/* 文件存储在本地 */
			$modelRes = $this->model('fs/local', $oApp->siteid, '_resumable');
			$modelAtt = $this->model('fs/local', $oApp->siteid, '附件');
			$fileUploaded = $modelRes->rootDir . '/enroll/' . $oApp->id . '/' . $oFile->name;

			$targetDir = $modelAtt->rootDir . '/enroll/' . date('Ym');
			if (!file_exists($targetDir)) {
				mkdir($targetDir, 0777, true);
			}
			$fileUploaded2 = $targetDir . '/' . $oApp->id . '_' . $modelApp->toLocalEncoding($oFile->name);
			if (false === rename($fileUploaded, $fileUploaded2)) {
				return new ResponseError('移动上传文件失败');
			}
			$url = 'local://enroll/' . date('Ym') . '/' . $oApp->id . '_' . $oFile->name;
		}

		$oAtt = new \stdClass;
		$oAtt->matter_id = $oApp->id;
		$oAtt->matter_type = 'enroll';
		$oAtt->name = $oFile->name;
		$oAtt->type = $oFile->type;
		$oAtt->size = $oFile->size;
		$oAtt->last_modified = $oFile->lastModified;
		$oAtt->url = $url;

		$oAtt->id = $modelApp->insert('xxt_matter_attachment', $oAtt, true);

		return new \ResponseData($oAtt);
	}
	/**
	 * 删除附件???
	 */
	public function del_action($id) {
		return new \ResponseData('not support');
	}
}