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
			$fsAlioss = \TMS_APP::M('fs/alioss', $this->siteId, '_attachment');
			$downloadUrl = $fsAlioss->getHostUrl() . '/' . $oApp->siteid . '/_attachment/enroll/' . $oApp->id . '/' . urlencode($oAtt->name);
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
	/**
	 * 下载题目中的文件
	 */
	public function download_action($app, $file) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			die('指定的登记活动不存在，请检查参数是否正确');
		}
		if (empty($file)) {
			die('参数错误');
		}

		$file = $modelApp->unescape($file);
		$file = json_decode($file);

		// 附件是否存在;
		$file->url = TMS_APP_DIR . '/' . $file->url;
		if (!file_exists($file->url)) {
			die('指定的附件不存在');
		}

		header("Content-Type: $file->type");
		Header( "Accept-Ranges: bytes" );
		header('Content-Length: ' . $file->size);
		header("Content-Disposition: attachment; filename=" . $file->name);
		readfile($file->url);

		exit;
	}
}