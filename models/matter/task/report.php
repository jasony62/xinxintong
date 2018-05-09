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
	public function exec($oMatter, $oArgs = null) {
		if ($oMatter->type === 'mission') {
			$modelMission = $this->model('matter\mission');
			$oMission = $modelMission->byId($oMatter->id);
			if (false === $oMission) {
				return [false, '指定的项目不存在'];
			}
			if (isset($oMission->state) && $oMission->state === '0') {
				return [false, '指定的项目已经不可用'];
			}
			$appURL = $oMission->opUrl;
			$modelQurl = $this->model('q\url');
			$noticeURL = $modelQurl->urlByUrl($oMission->siteid, $appURL);

			$model = $this->model('matter\mission\receiver');
			$rst = $model->notify($oMission, 'timer.mission.report', ['noticeURL' => $noticeURL]);

			return $rst;
		}

		if ($oMatter->type === 'enroll') {
			$modelEnl = $this->model('matter\enroll');
			$oMatter = $modelEnl->byId($oMatter->id, ['cascaded' => 'N']);
			if (false === $oMatter) {
				return [false, '指定的活动不存在'];
			}
			if (isset($oMatter->state) && $oMatter->state === '0') {
				return [false, '指定的活动已经不可用'];
			}

			/* 获得活动的管理员链接 */
			if (!empty($oArgs->page) && $oArgs->page !== 'console') {
				$noticeURL = $oMatter->entryUrl . '&page=' . $oArgs->page;
			} else {
				$appURL = $modelEnl->getOpUrl($oMatter->siteid, $oMatter->id);
				$modelQurl = $this->model('q\url');
				$noticeURL = $modelQurl->urlByUrl($oMatter->siteid, $appURL);
			}

			$model = $this->model('matter\enroll\receiver');
			$rst = $model->notify($oMatter, 'timer.enroll.report', ['noticeURL' => $noticeURL]);

			return $rst;
		}

		return [true];
	}
}