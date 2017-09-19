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
		if ($oMatter->type === 'mission') {
			$modelMission = $this->model('matter\mission');
			$oMatter = $modelMission->byId($oMatter->id);
			if (false === $oMatter) {
				return [false, '指定的活动不存在'];
			}
			if (empty($oMatter->user_app_id)) {
				return [false, '项目未指定用户名单应用'];
			}

			/* 获得活动的进入链接 */
			$params = new \stdClass;
			$params->url = $oMatter->entryUrl;

			/* 获得用户 */
			switch ($oMatter->user_app_type) {
			case 'group':
				$q = [
					'distinct userid,enroll_key assoc_with',
					'xxt_group_player',
					['state' => 1, 'aid' => $oMatter->user_app_id],
				];
				$receivers = $modelMission->query_objs_ss($q);
				break;
			case 'enroll':
				$matterEnroll = new \stdClass;
				$matterEnroll->id = $oMatter->user_app_id;
				$modelRec = $this->model('matter\enroll\user');
				$options = [
					'rid' => 'ALL',
					'fields' => 'userid',
					'cascaded' => 'N',
				];
				$enrollUsers = $modelRec->enrolleeByApp($matterEnroll, '', '', $options);
				$receivers = $enrollUsers->users;
				break;
			case 'signin':
				$matterSignin = new \stdClass;
				$matterSignin->id = $oMatter->user_app_id;
				$receivers = $this->model('matter\signin\record')->enrolleeByApp($matterSignin, ['fields' => 'distinct userid,enroll_key assoc_with']);
				break;
			case 'mschema':
				$receivers = $this->model('site\user\member')->byMschema($oMatter->user_app_id, ['fields' => 'userid']);
				break;
			}
			if (empty($receivers)) {
				return [false, '没有填写人'];
			}

			$noticeName = 'timer.mission.remind';
		} else if ($oMatter->type === 'enroll') {
			$modelEnl = $this->model('matter\enroll');
			$oMatter = $modelEnl->byId($oMatter->id, ['cascaded' => 'N']);
			if (false === $oMatter) {
				return [false, '指定的活动不存在'];
			}

			/* 获得活动的进入链接 */
			$params = new \stdClass;
			$params->url = $oMatter->entryUrl;

			/*处理要发送的填写人*/
			$modelRec = $this->model('matter\enroll\user');
			$options = [
				'rid' => 'ALL',
				'fields' => 'userid',
				'cascaded' => 'N',
			];
			$enrollUsers = $modelRec->enrolleeByApp($oMatter, '', '', $options);
			$receivers = $enrollUsers->users;
			if (count($receivers) === 0) {
				return [false, '没有填写人'];
			}

			$noticeName = 'timer.enroll.remind';
		}

		/*获取模板消息id*/
		$oNotice = $this->model('site\notice')->byName($oMatter->siteid, $noticeName, ['onlySite' => false]);
		if ($oNotice === false) {
			return [false, '没有指定事件的模板消息1'];
		}
		$oTmplConfig = $this->model('matter\tmplmsg\config')->byId($oNotice->tmplmsg_config_id);
		$tmplmsgId = $oTmplConfig->msgid;
		if (empty($tmplmsgId)) {
			return [false, '没有指定事件的模板消息2'];
		}

		$modelTmplBat = $this->model('matter\tmplmsg\batch');
		$creater = new \stdClass;
		$creater->uid = $noticeName;
		$creater->name = 'timer';
		$creater->src = 'pl';
		$modelTmplBat->send($oMatter->siteid, $tmplmsgId, $creater, $receivers, $params, ['send_from' => $oMatter->type . ':' . $oMatter->id]);

		return [true];
	}
}