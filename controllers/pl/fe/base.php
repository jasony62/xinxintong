<?php
namespace pl\fe;
/**
 *
 */
class base extends \TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 * 获得当前登录账号的用户信息
	 */
	protected function &accountUser() {
		$account = \TMS_CLIENT::account();
		if ($account) {
			$user = new \stdClass;
			$user->id = $account->uid;
			$user->name = $account->nickname;
			$user->src = 'A';

		} else {
			$user = false;
		}
		return $user;
	}
	/**
	 * 尽最大可能向用户发送消息
	 *
	 * $site
	 * $openid
	 * $message
	 */
	public function sendByOpenid($site, $openid, $message, $openid_src = null) {
		$model=$this->model();
		if (empty($openid_src)) {
			$openid_src = $model->query_val_ss([
				'ufrom',
				'xxt_site_account',
				"siteid='$siteId' and (wx_openid='$openid' or qy_openid='$openid' or yx_openid='$openid')"
			]);
		}

		switch ($openid_src) {
		case 'yx':
			$config=$model->query_obj_ss(['joined,can_p2p','xxt_site_yx',['siteid'=>$site]]);
			if ($config->joined === 'Y' && $config->can_p2p === 'Y') {
				$rst = $this->model('sns\yx\proxy')->messageSend($message, array($openid));
			} else {
				$rst = $this->model('sns\yx\proxy')->messageCustomSend($message, $openid);
			}
			break;
		case 'wx':
			$rst = $this->model('sns\wx\proxy')->messageCustomSend($message, $openid);
			break;
		case 'qy':
			$message['touser'] = $openid;
			$message['agentid'] = $mpa->qy_agentid;
			$rst = $this->model('sns\qy\proxy')->messageSend($message, $openid);
			break;
		}
		return $rst;
	}
	/**
	 * 发送模板消息
	 *
	 * $mpid
	 * $tmplmsgId
	 * $openid
	 */
	protected function tmplmsgSendByOpenid($tmplmsgId, $openid, $data, $url = null, $snsConfig = null) {
		/*模板定义*/
		is_object($data) && $data = (array) $data;
		if (empty($url) && isset($data['url'])) {
			$url = $data['url'];
			unset($data['url']);
		}

		$modelTmpl = $this->model('matter\tmplmsg');
		$tmpl = $modelTmpl->byId($tmplmsgId, array('cascaded' => 'Y'));
		$siteId = $tmpl->siteid;
		$ufrom = $modelTmpl->query_val_ss([
			'ufrom',
			'xxt_site_account',
			"siteid='$siteId' and (wx_openid='$openid' or qy_openid='$openid' or yx_openid='$openid')"
		]);
		/*发送消息*/
		if (!empty($tmpl->templateid) && $ufrom==='wx') {
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
			if ($snsConfig === null) {
				$snsConfig = $this->model('sns\wx')->bySite($siteId);
			}
			$proxy = $this->model('sns\wx\proxy', $snsConfig);
			$rst = $proxy->messageTemplateSend($msg);
			if ($rst[0] === false) {
				return $rst;
			}
			$msgid = $rst[1]->msgid;
		} else {
			/*如果不是微信号，将模板消息转换文本消息*/
			$txt = array();
			$txt[] = $tmpl->title;
			if ($tmpl->params) {
				foreach ($tmpl->params as $p) {
					$value = isset($data[$p->pname]) ? $data[$p->pname] : (isset($data[$p->id]) ? $data[$p->id] : '');
					$txt[] = $p->plabel . '：' . $value;
				}
			}
			if (!empty($url)) {
				if ($ufrom === 'yx') {
					$txt[] = '查看详情：\n' . $url;
				} else {
					$txt[] = " <a href='" . $url . "'>查看详情</a>";
				}
			}
			$txt = implode("\n", $txt);
			$msg = array(
				"msgtype" => "text",
				"text" => array(
					"content" => $txt,
				),
			);
			$this->sendByOpenid($siteId, $openid, $msg, $ufrom);
			$msg['template_id'] = 0;
			$msgid = 0;
		}
		/*记录日志*/
		$log = [
			'siteid' => $siteId,
			'openid' => $openid,
			'tmplmsg_id' => $tmplmsgId,
			'template_id' => $msg['template_id'],
			'data' => $modelTmpl->escape(json_encode($msg)),
			'create_at' => time(),
			'msgid' => $msgid,
		];
		$modelTmpl->insert('xxt_log_tmplmsg', $log, false);

		return array(true);
	}
}