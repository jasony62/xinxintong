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

		if (defined('SAE_TMP_PATH')) {
			$dest = '/enroll/' . $oApp->id . '/' . $_POST['resumableFilename'];
			$oResumable = $this->model('fs/resumableAliOss', $oApp->siteid, $dest, 'xxt-attachment');
		} else {
			$modelFs = $this->model('fs/local', $oApp->siteid, '_resumable');
			$dest = '/enroll_' . $oApp->id . '_' . $_POST['resumableIdentifier'];
			$oResumable = $this->model('fs/resumable', $oApp->siteid, $dest, $modelFs);
		}

		$oResumable->handleRequest($_POST);

		exit;
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

		if (defined('SAE_TMP_PATH')) {
			/*文件存储在阿里*/
			$url = 'alioss://enroll/' . $oApp->id . '/' . $file->name;
		} else {
			/*文件存储在本地*/
			$modelRes = $this->model('fs/local', $oApp->siteid, '_resumable');
			$modelAtt = $this->model('fs/local', $oApp->siteid, '附件');
			$fileUploaded = $modelRes->rootDir . '/enroll_' . $oApp->id . '_' . $oFile->uniqueIdentifier;
			$fileUploaded2 = $modelAtt->rootDir . '/enroll_' . $oApp->id . '_' . $modelApp->toLocalEncoding($oFile->name);
			if (false === rename($fileUploaded, $fileUploaded2)) {
				return new ResponseError('移动上传文件失败');
			}
			$url = 'local://enroll_' . $oApp->id . '_' . $oFile->name;
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
	 * 删除附件
	 */
	public function del_action($id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 附件对象
		$q = ['matter_id,name,url',
			'xxt_matter_attachment',
			['id' => $id],
		];
		$oAtt = $modelApp->query_obj_ss($q);
		if (false === $oAtt) {
			return new \ObjectNotFoundError();
		}

		$modelApp = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($oAtt->matter_id);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		/**
		 * remove from fs
		 */
		if (strpos($oAtt->url, 'alioss') === 0) {
			$fs = $this->model('fs/alioss', $oApp->siteid, 'xxt-attachment');
			$object = $oApp->siteid . '/article/' . $oAtt->matter_id . '/' . $oAtt->name;
			$rsp = $fs->delete_object($object);
		} else if (strpos($oAtt->url, 'local') === 0) {
			$fs = $this->model('fs/local', $oApp->siteid, '附件');
			$path = 'article_' . $oAtt->matter_id . '_' . $oAtt->name;
			$rsp = $fs->delete($path);
		} else {
			$fs = $this->model('fs/saestore', $oApp->siteid);
			$fs->delete($oAtt->url);
		}
		$rst = $modelApp->delete('xxt_matter_attachment', ['id' => $id]);

		return new \ResponseData($rst);
	}
}