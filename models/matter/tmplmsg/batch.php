<?php
namespace matter\tmplmsg;
/**
 * 模版消息的发送批次
 */
class batch_model extends \TMS_MODEL {
	/**
	 * 批量发送模板消息
	 *
	 * @param string $siteId
	 * @param int $tmplmsgId 应用内模板消息id
	 * @param object $creater
	 * @param array $userids
	 *
	 */
	public function send($siteId, $tmplmsgId, $creater, $userIds, $params, $options = []) {
		if (count($userIds) === 0) {
			return true;
		}

		$modelAcnt = $this->model('site\user\account');
		$mapOfUsers = [];
		foreach ($userIds as $userid) {
			$user = $modelAcnt->byId($userid, ['fields' => 'wx_openid,yx_openid,qy_openid']);
			if ($user) {
				$mapOfUsers[$userid] = $user;
			}
		}

		$modelTmpl = $this->model('matter\tmplmsg');
		$tmpl = $modelTmpl->byId($tmplmsgId, ['cascaded' => 'Y']);

		/* 拼装通知消息 */
		$url = isset($params->url) ? $params->url : '';
		// 微信模板消息
		$wxTmplMsg = [
			'template_id' => $tmpl->templateid,
			'url' => $url,
		];
		// 文本格式模板消息
		$txtTmplMsg = [];
		$txtTmplMsg[] = $tmpl->title;
		// 组装模板内容
		if ($tmpl->params) {
			foreach ($tmpl->params as $tp) {
				$value = isset($params->{$tp->pname}) ? $params->{$tp->pname} : (isset($params->{$tp->id}) ? $params->{$tp->id} : '');
				$wxTmplMsg['data'][$tp->pname] = ['value' => $value, 'color' => '#173177'];
				$txtTmplMsg[] = $tp->plabel . '：' . $value;
			}
		}

		// 创建发送批次
		empty($options['remark']) && $options['remark'] = implode("\n", $txtTmplMsg);
		$batch = $this->_create($siteId, $tmpl, $creater, $params, count($mapOfUsers), $options);

		// 消息发送日志
		$log = [
			'batch_id' => $batch->id,
			'siteid' => $siteId,
			'tmplmsg_id' => $tmplmsgId,
		];

		foreach ($mapOfUsers as $userid => $user) {
			$log['userid'] = $userid;
			if (!empty($user->wx_openid)) {
				if (!empty($tmpl->templateid)) {
					/* 发送微信模板消息 */
					$wxTmplMsg['touser'] = $user->wx_openid;
					$log['openid'] = $user->wx_openid;
					$log['data'] = $modelTmpl->escape($modelTmpl->toJson($wxTmplMsg));
					if (!isset($snsConfig)) {
						$snsConfig = $this->model('sns\wx')->bySite($tmpl->siteid);
						$wxProxy = $this->model('sns\wx\proxy', $snsConfig);
					}
					$rst = $wxProxy->messageTemplateSend($wxTmplMsg);
					if ($rst[0] === false) {
						$log['status'] = 'failed:' . $rst[1];
					} else {
						$log['msgid'] = $rst[1]->msgid;
					}
					$modelTmpl->insert('xxt_log_tmplmsg_detail', $log, false);
				} else {
					$log['openid'] = $user->wx_openid;
					if (!empty($url)) {
						$txtTmplMsg[] = " <a href='" . $url . "'>查看详情</a>";
					}
					$log['data'] = $modelTmpl->escape($modelTmpl->toJson($txtTmplMsg));

					$rst = $this->_sendTxtByOpenid($siteId, $user->wx_openid, 'wx', $txtTmplMsg, $log);
				}
			}
			/* 易信用户，将模板消息转换文本消息 */
			if (!empty($user->qy_openid)) {
				$log['openid'] = $user->qy_openid;
				if (!empty($url)) {
					$txtTmplMsg[] = " <a href='" . $url . "'>查看详情</a>";
				}
				$log['data'] = $modelTmpl->escape($modelTmpl->toJson($txtTmplMsg));

				$rst = $this->_sendTxtByOpenid($siteId, $user->wx_openid, 'qy', $txtTmplMsg, $log);
			}
			if (!empty($user->yx_openid)) {
				$log['openid'] = $user->yx_openid;
				if (!empty($url)) {
					$txtTmplMsg[] = '查看详情：\n' . $url;
				}
				$log['data'] = $modelTmpl->escape($modelTmpl->toJson($txtTmplMsg));

				$rst = $this->_sendTxtByOpenid($siteId, $user->yx_openid, 'yx', $txtTmplMsg, $log);
			}
		}
	}
	/**
	 * 创建批次
	 */
	private function _create($siteId, $tmpl, $creater, $params, $userCount, $options = []) {
		$batch = new \stdClass;
		$batch->siteid = $siteId;
		$batch->tmplmsg_id = $tmpl->id;
		$batch->template_id = $tmpl->templateid;
		$batch->user_num = $userCount;
		$batch->creater = $creater->uid;
		$batch->creater_name = $creater->name;
		$batch->creater_src = $creater->src;
		$batch->create_at = time();
		$batch->params = $this->escape($this->toJson($params));
		!empty($options['send_from']) && $batch->send_from = $options['send_from'];
		!empty($options['remark']) && $batch->remark = $options['remark'];

		$batch->id = $this->insert('xxt_log_tmplmsg_batch', $batch, true);

		return $batch;
	}
	/**
	 *
	 */
	private function _sendTxtByOpenid($siteId, $openid, $openidSrc, $aTxt, $log) {
		$txt = implode("\n", $aTxt);
		$message = [
			"msgtype" => "text",
			"text" => [
				"content" => $txt,
			],
		];
		switch ($openidSrc) {
		case 'yx':
			$snsConfig = $this->model('sns\yx')->bySite($siteId);
			$snsProxy = $this->model('sns\yx\proxy', $snsConfig);
			if ($snsConfig->can_p2p === 'Y') {
				$rst = $snsProxy->messageSend($message, array($openid));
				if (false === $rst[0]) {
					$rst = array(false, $rst[1][0][$openid]);
				}
			} else {
				$rst = $snsProxy->messageCustomSend($message, $openid);
			}
			break;
		case 'qy':
			$snsConfig = $this->model('sns\qy')->bySite($siteId);
			$snsProxy = $this->model('sns\qy\proxy', $snsConfig);
			$message['touser'] = $openid;
			$message['agentid'] = $snsConfig->agentid;
			$rst = $snsProxy->messageSend($message, $openid);
			break;
		case 'wx':
			$snsConfig = $this->model('sns\wx')->bySite($siteId);
			$snsProxy = $this->model('sns\wx\proxy', $snsConfig);
			$rst = $snsProxy->messageCustomSend($message, $openid);
			break;
		}
		/* 记录日志 */
		if (false === $rst[0]) {
			$log['status'] = 'failed:' . $this->escape(is_string($rst[1]) ? $rst[1] : $this->toJson($rst[1]));
		} else {
			$log['status'] = 'success';
		}
		$this->insert('xxt_log_tmplmsg_detail', $log, false);

		return $rst;
	}
}