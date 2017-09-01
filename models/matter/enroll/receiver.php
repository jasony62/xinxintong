<?php
namespace matter\enroll;

class receiver_model extends \TMS_MODEL {
	/**
	 *
	 * @param string $siteId
	 * @param string $aid
	 */
	public function &byUser($siteId, $userid, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array(
			$fields,
			'xxt_enroll_receiver',
			"siteid='$siteId' and userid='$userid'",
		);

		$receiver = $this->query_obj_ss($q);

		return $receiver;
	}
	/**
	 *
	 * @param string $siteId
	 * @param string $aid
	 */
	public function &byApp($siteId, $aid, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_enroll_receiver',
			['aid' => $aid],
		];

		$receivers = $this->query_objs_ss($q);

		return $receivers;
	}
	/**
	 *
	 */
	public function isUserJoined($appId, $userId) {
		$q = [
			'*',
			'xxt_enroll_receiver',
			['aid' => $appId, 'userid' => $userId],
		];
		$receiver = $this->query_obj_ss($q);

		return $receiver;
	}
	/**
	 * 获得指定时间戳后加入的登记活动通知接收人
	 *
	 * @param string $siteId
	 * @param string $aid
	 */
	public function &afterJoin($siteId, $aid, $timestamp, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			'xxt_enroll_receiver',
			"aid='$aid' and join_at>=$timestamp",
		];

		$receivers = $this->query_objs_ss($q);

		return $receivers;
	}
	/**
	 * 通知登记活动事件接收人
	 *
	 * @param object $app
	 * @param string $ek
	 *
	 */
	public function notify($oApp, $eventName, $options = []) {
		$receivers = $this->byApp($oApp->siteid, $oApp->id);
		if (count($receivers) === 0) {
			return [false, '没有指定事件接收人'];
		}

		/* 模板消息参数 */
		$oParams = new \stdClass;
		$oNotice = $this->model('site\notice')->byName($oApp->siteid, $eventName);
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
					if (isset($oApp->{$mapping->id})) {
						$value = $oApp->{$mapping->id};
					} else if ($mapping->id === 'event_at') {
						$value = date('Y-m-d H:i:s');
					}
				} else if ($mapping->src === 'text') {
					$value = $mapping->name;
				}
			}
			$oParams->{$param->pname} = isset($value) ? $value : '';
		}
		if (!empty($options['noticeURL'])) {
			$oParams->url = $options['noticeURL'];
		}

		/* 发送消息 */
		foreach ($receivers as &$oReceiver) {
			if (!empty($oReceiver->sns_user)) {
				$snsUser = json_decode($oReceiver->sns_user);
				if (isset($snsUser->src) && isset($snsUser->openid)) {
					$oReceiver->{$snsUser->src . '_openid'} = $snsUser->openid;
				}
			}
		}

		$modelTmplBat = $this->model('matter\tmplmsg\plbatch');
		$modelTmplBat->send($oApp->siteid, $oTmplConfig->msgid, $receivers, $oParams, $options);

		return [true];
	}
}