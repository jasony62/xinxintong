<?php
namespace matter\task;
/**
 *
 */
class remind_model extends \TMS_MODEL {
	/**
	 * 执行活动任务提醒任务
	 *
	 * @param
	 */
	public function exec($oMatter, $arguments = null) {
		if ($oMatter->type === 'enroll') {
			$modelEnl = $this->model('matter\enroll');
			$oMatter = $modelEnl->byId($oMatter->id, ['cascaded' => 'N']);
			if (false === $oMatter) {
				return [false, '指定的活动不存在'];
			}

			/* 获得活动的进入链接 */
			$noticeURL = $oMatter->entryUrl;

			/*处理要发送的填写人*/
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oMatter)) {
				$rid = $activeRound->rid;
			}else{
				$rid = 'ALL';
			}
			$modelRec = $this->model('matter\enroll\user');
			$options = [
				'rid' => $rid,
				'fields' => "userid"
			];
			$enrollUsers = $modelRec->enrolleeByApp($oMatter, '', '', $options);
			$receivers = $enrollUsers->users;

			if (count($receivers) === 0) {
				return [false, '没有填写人'];
			}
			$model = $this->model('matter\enroll\receiver');
			$rst = $model->notify($oMatter, 'timer.enroll.remind', ['noticeURL' => $noticeURL], $receivers);

			return $rst;
		}

		return [true];
	}
}