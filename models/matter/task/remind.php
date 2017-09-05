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
			$params = new \stdClass;
			$noticeURL = $oMatter->entryUrl;
			$params->url = $noticeURL;

			/*处理要发送的填写人*/
			$modelRec = $this->model('matter\enroll\record');
			$options = [
				'rid' => '',
			];
			$oUsers = $modelRec->enrolleeByApp($oMatter, $options);
			if (count($oUsers) === 0) {
				return [false, '没有填写人'];
			}
			$receivers = [];
			foreach ($oUsers as $oUser) {
				$receiver = new \stdClass;
				$receiver->assoc_with = $oUser->enroll_key;
				$receiver->userid = $oUser->userid;
				$receivers[] = $receiver;
			}

			/*获取模板消息id*/
			$oNotice = $this->model('site\notice')->byName($oMatter->siteid, 'timer.enroll.remind', ['onlySite' => false]);
			if ($oNotice === false) {
				return [false, '没有指定事件的模板消息1'];
			}
			$oTmplConfig = $this->model('matter\tmplmsg\config')->byId($oNotice->tmplmsg_config_id);
			$tmplmsgId = $oTmplConfig->msgid;
			if(empty($tmplmsgId)){
				return [false, '没有指定事件的模板消息2'];
			}

			$modelTmplBat = $this->model('matter\tmplmsg\batch');
			$creater = new \stdClass;
			$creater->uid = 'timer.enroll.remind';
			$creater->name = 'timer';
			$creater->src = 'pl';
			$modelTmplBat->send($oMatter->siteid, $tmplmsgId, $creater, $receivers, $params, ['send_from' => 'enroll:' . $oMatter->id]);
		}

		return [true];
	}
}