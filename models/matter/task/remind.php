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
				return [false, '指定的项目不存在'];
			}
			if (isset($oMatter->state) && $oMatter->state === '0') {
				return [false, '指定的项目已经不可用'];
			}

			/* 获得活动的进入链接 */
			$noticeURL = $oMatter->entryUrl;

			/* 获得用户 */
			if (empty($oMatter->user_app_id)) {
				$receivers = $this->model('matter\mission\user')->enrolleeByMission($oMatter, ['fields' => 'distinct userid']);
			} else {
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
					$modelEnlUsr = $this->model('matter\enroll\user');
					$options = [
						'rid' => 'ALL',
						'onlyEnrolled' => 'Y',
						'fields' => 'userid',
						'cascaded' => 'N',
					];
					$enrollUsers = $modelEnlUsr->enrolleeByApp($matterEnroll, '', '', $options);
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
			}
			if (empty($receivers)) {
				return [false, '没有填写人'];
			}
			$noticeName = 'timer.mission.remind';
		} else if ($oMatter->type === 'enroll') {
			$modelEnl = $this->model('matter\enroll');
			$oMatter = $modelEnl->byId($oMatter->id, ['cascaded' => 'N']);
			if (false === $oMatter || $oMatter->state !== '1') {
				return [false, '指定的活动不存在，或已不可用'];
			}
			$noticeURL = $oMatter->entryUrl; // 获得活动的进入链接
			$noticeName = 'timer.enroll.remind'; // 事件的名称

			/* 发送给通讯录中的用户 */
			if (isset($oMatter->entryRule->scope->member) && $oMatter->entryRule->scope->member === 'Y' && isset($oMatter->entryRule->member)) {
				$modelMs = $this->model('site\user\memberschema');
				$modelMem = $this->model('site\user\member');
				$receivers = [];
				foreach ($oMatter->entryRule->member as $mschemaId => $oRule) {
					$oMschema = $modelMs->byId($mschemaId, ['fields' => 'is_wx_fan', 'cascaded' => 'N']);
					if ($oMschema->is_wx_fan === 'Y') {
						$aOnce = $modelMem->byMschema($mschemaId, ['fields' => 'userid']);
						$receivers = array_merge($receivers, $aOnce);
					}
				}
			}
			/* 发送给记录填写人 */
			if (empty($receivers)) {
				$modelUsr = $this->model('matter\enroll\user');
				$options = [
					'rid' => 'ALL',
					'onlyEnrolled' => 'Y',
					'fields' => 'userid',
					'cascaded' => 'N',
				];
				$enrollUsers = $modelUsr->enrolleeByApp($oMatter, '', '', $options);
				$receivers = $enrollUsers->users;
				if (count($receivers) === 0) {
					return [false, '没有填写人'];
				}
			}
		} else if ($oMatter->type === 'plan') {
			$modelPlan = $this->model('matter\plan');
			$oMatter = $modelPlan->byId($oMatter->id, ['fields' => 'id,state,siteid,title,summary']);
			if (false === $oMatter || $oMatter->state !== '1') {
				return [false, '指定的活动不存在'];
			}
			/* 获得活动的进入链接 */
			if ($inviteUrl = $modelPlan->getInviteUrl($oMatter->id, $oMatter->siteid)) {
				$noticeURL = $inviteUrl;
			} else {
				$noticeURL = $oMatter->entryUrl;
			}

			/* 处理要发送的填写人 */
			$modelUsr = $this->model('matter\plan\user');
			$planUsers = $modelUsr->byApp($oMatter);
			$receivers = $planUsers->users;
			if (count($receivers) === 0) {
				return [false, '没有填写人'];
			}
			$noticeName = 'timer.plan.remind';
		}

		/*获取模板消息id*/
		$tmpConfig = $this->model('matter\tmplmsg\config')->getTmplConfig($oMatter, $noticeName, ['onlySite' => false, 'noticeURL' => $noticeURL]);
		if ($tmpConfig[0] === false) {
			return [false, $tmpConfig[1]];
		}
		$tmpConfig = $tmpConfig[1];

		$modelTmplBat = $this->model('matter\tmplmsg\batch');
		$creater = new \stdClass;
		$creater->uid = $noticeName;
		$creater->name = 'timer';
		$creater->src = 'pl';
		$modelTmplBat->send($oMatter->siteid, $tmpConfig->tmplmsgId, $creater, $receivers, $tmpConfig->oParams, ['send_from' => $oMatter->type . ':' . $oMatter->id]);

		return [true];
	}
}