<?php
namespace matter\task;
/**
 *
 */
class report_model extends \TMS_MODEL {
	/**
	 * 执行活动状态报告任务
	 *
	 * @param
	 */
	public function exec($oMatter, $arguments = null) {
		if ($oMatter->type === 'enroll') {
			$modelEnl = $this->model('matter\enroll');
			$oMatter = $modelEnl->byId($oMatter->id, ['cascaded' => 'N']);

			/* 获得活动的管理员链接 */
			$appURL = $modelEnl->getOpUrl($oMatter->siteid, $oMatter->id);
			$modelQurl = $this->model('q\url');
			$noticeURL = $modelQurl->urlByUrl($oMatter->siteid, $appURL);

			$model = $this->model('matter\enroll\receiver');
			$rst = $model->notify($oMatter, 'timer.enroll.report', ['noticeURL' => $noticeURL]);

			return $rst;
		}

		return [true];
	}
}