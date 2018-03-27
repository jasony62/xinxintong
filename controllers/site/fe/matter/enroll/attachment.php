<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';

/**
 * 登记活动附件
 */
class attachment extends base {
	/**
	 * 下载附件
	 */
	public function get_action($app, $attachment) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			$this->outputError('指定的登记活动不存在，请检查参数是否正确');
		}
		/**
		 * 获取附件
		 */
		$q = [
			'*',
			'xxt_matter_attachment',
			['matter_id' => $oApp->id, 'matter_type' => 'enroll', 'id' => $attachment],
		];
		if (false === ($oAtt = $modelApp->query_obj_ss($q))) {
			die('指定的附件不存在');
		}

		if (strpos($oAtt->url, 'alioss') === 0) {
			$downloadUrl = 'http://xxt-attachment.oss-cn-shanghai.aliyuncs.com/' . $oApp->siteid . '/enroll/' . $oApp->id . '/' . urlencode($oAtt->name);
			$this->redirect($downloadUrl);
		} else if (strpos($oAtt->url, 'local') === 0) {
			$fs = $this->model('fs/local', $oApp->siteid, '附件');
			//header("Content-Type: application/force-download");
			header("Content-Type: $oAtt->type");
			header("Content-Disposition: attachment; filename=" . $oAtt->name);
			header('Content-Length: ' . $oAtt->size);
			echo $fs->read(str_replace('local://', '', $oAtt->url));
		} else {
			$fs = $this->model('fs/saestore', $oApp->siteid);
			//header("Content-Type: application/force-download");
			header("Content-Type: $oAtt->type");
			header("Content-Disposition: attachment; filename=" . $oAtt->name);
			header('Content-Length: ' . $oAtt->size);
			echo $fs->read($oAtt->url);
		}

		exit;
	}
}