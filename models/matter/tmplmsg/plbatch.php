<?php
namespace matter\tmplmsg;
/**
 * 模版消息的发送批次
 */
class plbatch_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [$fields, 'xxt_log_tmplmsg_plbatch', ['id' => $id]];

		$batch = $this->query_obj_ss($q);

		return $batch;
	}
	/**
	 * 批量发送模板消息
	 *
	 * @param string $siteId
	 * @param int $tmplmsgId 应用内模板消息id
	 * @param array $userids
	 *
	 */
	public function send($siteId, $tmplmsgId, $receivers, $params, $options = []) {
		if (count($receivers) === 0) {
			return true;
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
		$batch = $this->_create($siteId, $tmpl, $params, count($receivers), $options);

		// 消息发送日志
		$log = [
			'batch_id' => $batch->id,
			'siteid' => $siteId,
			'tmplmsg_id' => $tmplmsgId,
		];

		foreach ($receivers as $user) {
			/* 平台应用内消息 */
			if (isset($user->userid)) {
				$log['userid'] = $user->userid;
				$log['data'] = $modelTmpl->escape($modelTmpl->toJson($txtTmplMsg));
				$log['send_to'] = 'pl';
				$log['openid'] = '';
				$modelTmpl->insert('xxt_log_tmplmsg_pldetail', $log, false);
			} else {
				$log['userid'] = '';
			}
			/* 微信公众号用户 */
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
						$log['status'] = 'success';
						$log['msgid'] = $rst[1]->msgid;
					}

					$log['send_to'] = 'wx';
					$modelTmpl->insert('xxt_log_tmplmsg_pldetail', $log, false);
				} else {
					$log['openid'] = $user->wx_openid;
					if (!empty($url)) {
						$txtTmplMsg[] = " <a href='" . $url . "'>查看详情</a>";
					}
					$log['data'] = $modelTmpl->escape($modelTmpl->toJson($txtTmplMsg));

					$rst = $this->_sendTxtByOpenid($siteId, $user->wx_openid, 'wx', $txtTmplMsg, $log);
				}
			}
			/* 微信企业号用户，将模板消息转换文本消息 */
			if (!empty($user->qy_openid)) {
				$log['openid'] = $user->qy_openid;
				if (!empty($url)) {
					$txtTmplMsg[] = " <a href='" . $url . "'>查看详情</a>";
				}
				$log['data'] = $modelTmpl->escape($modelTmpl->toJson($txtTmplMsg));

				$rst = $this->_sendTxtByOpenid($siteId, $user->qy_openid, 'qy', $txtTmplMsg, $log);
			}
		}
	}
	/**
	 * 创建批次
	 */
	private function _create($siteId, $tmpl, $params, $userCount, $options = []) {
		$batch = new \stdClass;
		$batch->siteid = $siteId;
		$batch->tmplmsg_id = $tmpl->id;
		$batch->template_id = $tmpl->templateid;
		$batch->user_num = $userCount;
		$batch->create_at = time();
		$batch->params = $this->escape($this->toJson($params));
		!empty($options['event_name']) && $batch->event_name = $options['event_name'];
		!empty($options['send_from']) && $batch->send_from = $options['send_from'];
		!empty($options['remark']) && $batch->remark = $options['remark'];

		$batch->id = $this->insert('xxt_log_tmplmsg_plbatch', $batch, true);

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
		case 'qy':
			$log['send_to'] = 'qy';
			$snsConfig = $this->model('sns\qy')->bySite($siteId);
			$snsProxy = $this->model('sns\qy\proxy', $snsConfig);
			$message['touser'] = $openid;
			$message['agentid'] = $snsConfig->agentid;
			$rst = $snsProxy->messageSend($message, $openid);
			break;
		case 'wx':
			$log['send_to'] = 'wx';
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
		$this->insert('xxt_log_tmplmsg_pldetail', $log, false);

		return $rst;
	}
}