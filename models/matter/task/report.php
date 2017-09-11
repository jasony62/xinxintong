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
		$timerArgument = new \stdClass;

		if($oMatter->type === 'mission'){
			$modelMission = $this->model('matter\mission');
			$mission = $modelMission->byId($oMatter->id);
			if (false === $mission) {
				return [false, '指定的活动不存在'];
			}
			if (empty($mission->user_app_id)) {
				return [false, '项目未指定用户名单应用'];
			}

			$oMatter->type = $mission->user_app_type;
			$oMatter->id = $mission->user_app_id;
			$timerArgument->url = $mission->opUrl;
		}

		if ($oMatter->type === 'enroll') {
			$modelEnl = $this->model('matter\enroll');
			$oMatter = $modelEnl->byId($oMatter->id, ['cascaded' => 'N']);

			/* 获得活动的管理员链接 */
			if(isset($timerArgument->url)){
				$appURL = $timerArgument->url;
			}else{
				$appURL = $modelEnl->getOpUrl($oMatter->siteid, $oMatter->id);
			}
			$modelQurl = $this->model('q\url');
			$noticeURL = $modelQurl->urlByUrl($oMatter->siteid, $appURL);

			$model = $this->model('matter\enroll\receiver');
			$rst = $model->notify($oMatter, 'timer.enroll.report', ['noticeURL' => $noticeURL]);

			return $rst;
		}

		return [true];
	}
}