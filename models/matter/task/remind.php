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

			$modelRec = $this->model('matter\enroll\record');
			/* 获得活动的进入链接 */
			$appURL = $oMatter->entryUrl;
			$modelQurl = $this->model('q\url');
			$noticeURL = $modelQurl->urlByUrl($oMatter->siteid, $appURL);
			$params = new \stdClass;
			$params->url = $noticeURL;

			$options = [
				'rid' => null,
			];
			$users = $modelRec->enrolleeByApp($oMatter, $options);
			if (count($users) === 0) {
				return [false, '没有填写人'];
			}

			/*获取模板消息id*/
			$oNotice = $this->model('site\notice')->byName($oMatter->siteid, 'timer.enroll.remind', ['onlySite' => false]);
			if ($oNotice === false) {
				return [false, '没有指定事件的模板消息'];
			}
			$oTmplConfig = $this->model('matter\tmplmsg\config')->byId($oNotice->tmplmsg_config_id);

			$tmplmsgId = $oTmplConfig->msgid;

			$receivers = [];
			$receiverUnique = [];//去重
			foreach ($users as $user) {
				if(in_array($user->userid, $receiverUnique)){
					continue;
				}
				$receiverUnique[] = $user->userid;

				$receiver = new \stdClass;
				isset($user->enroll_key) && $receiver->assoc_with = $user->enroll_key;
				$receiver->userid = $user->userid;
				$receivers[] = $receiver;
			}

			// $user = $this->accountUser();
			// $modelTmplBat = $this->model('matter\tmplmsg\batch');
			// $creater = new \stdClass;
			// $creater->uid = $user->id;
			// $creater->name = $user->name;
			// $creater->src = 'pl';
			// $modelTmplBat->send($oMatter->siteid, $tmplmsgId, $creater, $receivers, $params, ['send_from' => 'enroll:' . $oMatter->id]);
		}

		return [true];
	}
}