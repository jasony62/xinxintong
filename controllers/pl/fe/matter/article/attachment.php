<?php
namespace pl\fe\matter\article;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 文章控制器
 */
class attachment extends \pl\fe\matter\base {
	/**
	 * 分段上传附件
	 */
	public function upload_action($site, $articleid) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\article');
		$oApp = $modelApp->byId($articleid);
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

		$modelApp = $this->model('matter\article');
		$oApp = $modelApp->byId($id);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$file = $this->getPostJson();
		$oAtt = $this->attachmentAdd($oApp, $file);
		if ($oAtt[0] === false) {
			return new ResponseError($oAtt[1]);
		}
		
		/* 更新文章状态 */
		$modelApp->update(
			'xxt_article',
			['has_attachment' => 'Y'],
			['id' => $id]
		);

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

		$rst = $rst[1];
		if ($rst == 1) {
			$q = [
				'1',
				'xxt_matter_attachment',
				"id='$id'",
			];
			$cnt = $model->query_val_ss($q);
			if ($cnt == 0) {
				$model->update(
					'xxt_article',
					array('has_attachment' => 'N'),
					"id='$id'"
				);
			}
		}

		return new \ResponseData($rst);
	}
}