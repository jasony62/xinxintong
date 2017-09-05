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

			/* 模板消息参数 */
			$oParams = new \stdClass;
			$oNotice = $this->model('site\notice')->byName($oMatter->siteid, 'timer.enroll.remind', ['onlySite' => false]);
			if ($oNotice === false) {
				return [false, '没有指定事件的模板消息1'];
			}
			$oTmplConfig = $this->model('matter\tmplmsg\config')->byId($oNotice->tmplmsg_config_id, ['cascaded' => 'Y']);
			if (!isset($oTmplConfig->tmplmsg)) {
				return [false, '没有指定事件的模板消息2'];
			}
			foreach ($oTmplConfig->tmplmsg->params as $param) {
				if (!isset($oTmplConfig->mapping->{$param->pname})) {
					continue;
				}
				$mapping = $oTmplConfig->mapping->{$param->pname};
				if (isset($mapping->src)) {
					if ($mapping->src === 'matter') {
						if (isset($oMatter->{$mapping->id})) {
							$value = $oMatter->{$mapping->id};
						} else if ($mapping->id === 'event_at') {
							$value = date('Y-m-d H:i:s');
						}
					} else if ($mapping->src === 'text') {
						$value = $mapping->name;
					}
				}
				$oParams->{$param->pname} = isset($value) ? $value : '';
			}
			/* 获得活动的进入链接 */
			$appURL = $oMatter->entryUrl;
			$oParams->url = $appURL;

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

			$modelTmplBat = $this->model('matter\tmplmsg\plbatch');
			$modelTmplBat->send($oMatter->siteid, $oTmplConfig->msgid, $receivers, $oParams, []);
		}

		return [true];
	}
}