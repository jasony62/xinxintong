<?php
/**
 *
 */
class xxt_base extends TMS_CONTROLLER {
	/**
	 * 尽最大可能向用户发送消息
	 *
	 * $mpid
	 * $openid
	 * $message
	 */
	public function sendByOpenid($mpid, $openid, $message, $openid_src = null) {
		if (empty($openid_src)) {
			$user = $model->query_obj_ss([
				'ufrom',
				'xxt_site_account',
				"siteid='$mpid' and (wx_openid='$openid' or qy_openid='$openid')",
			]);
			$mpa = $this->model("sns\\" . $user->ufrom)->bySite($mpid);
			$mpproxy = $this->model('sns\\' . $user->ufrom . '\\proxy', $mpa);
			$mpa->mpsrc = $user->ufrom;
		} else {
			switch ($openid_src) {
			case 'qy':
				$mpa = $this->model('sns\qy')->bySite($mpid);
				$mpproxy = $this->model('sns\qy\proxy', $mpa);
				$mpa->qy_agentid = $mpa->agentid;
				$mpa->mpsrc = 'qy';
				break;
			case 'wx':
				$mpa = $this->model('sns\wx')->bySite($mpid);
				$mpproxy = $this->model('sns\wx\proxy', $mpa);
				$mpa->mpsrc = 'wx';
				break;
			}
		}

		switch ($mpa->mpsrc) {
		case 'wx':
			$rst = $mpproxy->messageCustomSend($message, $openid);
			break;
		case 'qy':
			$message['touser'] = $openid;
			$message['agentid'] = $mpa->qy_agentid;
			$rst = $mpproxy->messageSend($message, $openid);
			break;
		}
		return $rst;
	}
	/**
	 * 发送模板消息页面
	 *
	 * $mpid
	 * $tmplmsgId
	 * $openid
	 */
	public function tmplmsgSendByOpenid($siteId, $tmplmsgId, $openid, $data, $url) {
		/*模板定义*/
		is_object($data) && $data = (array) $data;
		$tmpl = $this->model('matter\tmplmsg')->byId($tmplmsgId, array('cascaded' => 'Y'));
		$model = $this->model('matter\log');
		/*发送消息*/
		if (!empty($tmpl->templateid)) {
			/*只有微信号才有模板消息ID*/
			$msg = array(
				'touser' => $openid,
				'template_id' => $tmpl->templateid,
				'url' => $url,
			);
			if ($tmpl->params) {
				foreach ($tmpl->params as $p) {
					$value = isset($data[$p->pname]) ? $data[$p->pname] : (isset($data[$p->id]) ? $data[$p->id] : '');
					$msg['data'][$p->pname] = array('value' => $value, 'color' => '#173177');
				}
			}
			$config = $this->model('sns\\wx')->bySite($siteId);
			$mpproxy = $this->model('sns\\wx\\proxy', $config);
			$rst = $mpproxy->messageTemplateSend($msg);
			if ($rst[0] === false) {
				return $rst;
			}
			$msgid = $rst[1]->msgid;
		} else {
			/*如果不是微信号，将模板消息转换文本消息*/
			$user = $model->query_obj_ss([
				'ufrom',
				'xxt_site_account',
				"siteid='$siteId' and (wx_openid='$openid' or qy_openid='$openid')",
			]);
			$txt = array();
			$txt[] = $tmpl->title;
			if ($tmpl->params) {
				foreach ($tmpl->params as $p) {
					$value = isset($data[$p->pname]) ? $data[$p->pname] : (isset($data[$p->id]) ? $data[$p->id] : '');
					$txt[] = $p->plabel . '：' . $value;
				}
			}
			if (!empty($url)) {
				$txt[] = " <a href='" . $url . "'>查看详情</a>";
			}
			$txt = implode("\n", $txt);
			$msg = array(
				"msgtype" => "text",
				"text" => array(
					"content" => $txt,
				),
			);
			$this->sendByOpenid($siteId, $openid, $msg, $user->ufrom);
			$msg['template_id'] = 0;
			$msgid = 0;
		}
		/*记录日志*/
		$log = array(
			'mpid' => $siteId,
			'openid' => $openid,
			'tmplmsg_id' => $tmplmsgId,
			'template_id' => $msg['template_id'],
			'data' => $model->escape(json_encode($msg)),
			'create_at' => time(),
			'msgid' => $msgid,
		);
		$model->insert('xxt_log_tmplmsg', $log, false);

		return array(true);
	}
	/**
	 *
	 */
	protected function outputError($err, $title = '程序错误') {
		TPL::assign('title', $title);
		TPL::assign('body', $err);
		TPL::output('error');
		exit;
	}
}