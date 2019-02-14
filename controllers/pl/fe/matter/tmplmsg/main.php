<?php
namespace pl\fe\matter\tmplmsg;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 模板消息
 */
class main extends \pl\fe\matter\base {
	/**
	 * @param int $id
	 */
	public function get_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\tmplmsg');

		$tmplmsg = $modelTmpl->byId($id, ['cascaded' => 'Y']);

		return new \ResponseData($tmplmsg);
	}
	/**
	 *
	 */
	public function list_action($site, $cascaded = 'N') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmpl = $this->model('matter\tmplmsg');

		$tmplmsgs = $modelTmpl->bySite($site, ['cascaded' => $cascaded]);

		return new \ResponseData($tmplmsgs);
	}
	/**
	 *
	 */
	public function mappingGet_action($id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$mapping = $this->model('matter\tmplmsg\config')->byId($id);

		return new \ResponseData($mapping);
	}
	/**
	 * 创建模板消息
	 */
	public function create_action($site, $title = '新模板消息') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();

		$d['siteid'] = $site;
		$d['mpid'] = $site;
		$d['creater'] = $user->id;
		$d['create_at'] = time();
		$d['title'] = $title;

		$id = $model->insert('xxt_tmplmsg', $d, true);

		$q = array(
			"t.*",
			'xxt_tmplmsg t',
			"t.id=$id",
		);

		$tmplmsg = $model->query_obj_ss($q);

		return new \ResponseData($tmplmsg);
	}
	/**
	 * 删除模板消息
	 *
	 * $id
	 */
	public function remove_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$rst = $this->model()->update(
			'xxt_tmplmsg',
			array('state' => 0),
			"siteid='$site' and id=$id"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 更新模板消息属性
	 */
	public function update_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$nv = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_tmplmsg',
			$nv,
			"siteid='$site' and id=$id"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * $tid tmplmsg's id
	 */
	public function addParam_action($site, $tid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$p['siteid'] = $site;
		$p['tmplmsg_id'] = $tid;

		$id = $this->model()->insert('xxt_tmplmsg_param', $p, true);

		return new \ResponseData($id);
	}
	/**
	 *
	 * 更新参数定义
	 *
	 * $id parameter's id
	 */
	public function updateParam_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$nv = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_tmplmsg_param',
			$nv,
			"id=$id"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * $pid parameter's id
	 */
	public function removeParam_action($site, $pid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model()->delete('xxt_tmplmsg_param', "id=$pid");

		return new \ResponseData($rst);
	}
	/**
	 * 获取微信公众号的模板列表并同步更新到本地数据库
	 *
	 */
	public function synTemplateList_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$wx = $this->model('sns\wx');
		$config = $wx->bySite($site);
		if (!$config || $config->joined === 'N') {
			return new \ResponseError('未与微信公众号连接，无法同步微信模板消息!');
		}

		$proxy = $this->model('sns\wx\proxy', $config);
		$rst = $proxy->templateList();

		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		$templates = $rst[1]->template_list;

		if (empty($templates)) {
			return new \ResponseError('微信公众号没有设置模板消息！');
		}

		$d['siteid'] = $site;
		$d['mpid'] = $site;
		$d['creater'] = $user->id;
		$d['create_at'] = time();
		$p['siteid'] = $site;

		foreach ($templates as $k => $v) {
			$d['templateid'] = $v->template_id;
			$tmp[] = $v->template_id;
			$d['title'] = $v->title;
			$d['example'] = $v->example;
			//同步模板
			if ($id = $wx->query_val_ss(['id', 'xxt_tmplmsg', ['siteid' => $site, 'templateid' => $v->template_id]])) {
				$wx->update('xxt_tmplmsg', $d, "id='$id'");
			} else {
				$id = $wx->insert('xxt_tmplmsg', $d);
			}
			//同步参数
			$p['tmplmsg_id'] = $id;
			$e = array();
			$content = preg_replace(["/：{/", "/\s*/"], [":{"], trim($v->content));

			if (!preg_match('/{{.+?}:{.+?}}/', $content, $m)) {
				$r1 = explode("}}", $content);
				//去掉空行的字符串
				array_pop($r1);

				foreach ($r1 as $k1 => $v1) {
					$arr = explode(':', $v1);
					if (isset($arr[1])) {
						$p['pname'] = substr(trim($arr[1]), 2, -5);
						$p['plabel'] = $arr[0];
					} else {
						$p['pname'] = substr(trim($v1), 2, -5);
						$p['plabel'] = '';
					}

					$pid = $wx->query_val_ss([
						'id',
						'xxt_tmplmsg_param',
						['siteid' => $site, 'tmplmsg_id' => $p['tmplmsg_id'], 'pname' => $p['pname']],
					]);

					if ($pid) {
						$wx->update('xxt_tmplmsg_param', $p, "id='$pid'");
					} else {
						$wx->insert('xxt_tmplmsg_param', $p);
					}
				}
			} else {
				$word = $m[0];
				$str = strstr($content, $word);
				$param1 = explode(':', $word);

				$e[substr($param1[0], 2, -7)] = $l1 = substr($param1[1], 2, -7);
				$e[$l1] = '';

				$content = substr($str, strlen($word));
				$arr2 = explode("}}", $content);
				array_pop($arr2);

				foreach ($arr2 as $k2 => $v2) {
					$crr = explode(":", $v2);

					if (isset($crr[1])) {
						$e[substr($crr[1], 2, -5)] = $crr[0];
					} else {
						$e[substr($crr[0], 2, -5)] = '';
					}
				}

				foreach ($e as $k3 => $v3) {
					$p['pname'] = $k3;
					$p['plabel'] = $v3;

					$pid = $wx->query_val_ss([
						'id',
						'xxt_tmplmsg_param',
						['siteid' => $site, 'tmplmsg_id' => $p['tmplmsg_id'], 'pname' => $p['pname']],
					]);

					if ($pid) {
						$wx->update('xxt_tmplmsg_param', $p, "id='$pid'");
					} else {
						$wx->insert('xxt_tmplmsg_param', $p);
					}
				}
			}
		}
		$one = $wx->query_objs_ss(['templateid', 'xxt_tmplmsg', "siteid='$site' and templateid!=''"]);

		foreach ($one as $v0) {
			$two[] = $v0->templateid;
		}
		//将本地原来有实际上微信管理端已经删除的模板ID 设置为‘’ 表示本地删除
		if (isset($two) && isset($tmp) && $rest = array_diff($two, $tmp)) {
			foreach ($rest as $v4) {
				$wx->update('xxt_tmplmsg', ['templateid' => ''], ['siteid' => $site, 'templateid' => $v4]);
			}
		}

		$modelTmpl = $this->model('matter\tmplmsg');
		$tmplmsgs = $modelTmpl->bySite($site, ['cascaded' => 'Y']);

		return new \ResponseData($tmplmsgs);
	}
	/**
	 * 尽最大可能向用户发送消息
	 *
	 * $site
	 * $openid
	 * $message
	 */
	protected function sendByOpenid($site, $openid, $message, $openid_src = null) {
		$model = $this->model();
		if (empty($openid_src)) {
			$openid_src = $model->query_val_ss([
				'ufrom',
				'xxt_site_account',
				"siteid='$site' and (wx_openid='$openid' or qy_openid='$openid')",
			]);
		}

		switch ($openid_src) {
		case 'wx':
			$config = $this->model('sns\wx')->bySite($site);
			$rst = $this->model('sns\wx\proxy', $config)->messageCustomSend($message, $openid);
			break;
		case 'qy':
			$config = $this->model('sns\qy')->bySite($site);
			$message['touser'] = $openid;
			$message['agentid'] = $config->agentid;
			$rst = $this->model('sns\qy\proxy', $config)->messageSend($message, $openid);
			break;
		default:
			$rst = array(false);
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
	protected function tmplSendByOpenid($site, $tmplmsgId, $openid, $data, $url = null, $snsConfig = null) {
		/*模板定义*/
		is_object($data) && $data = (array) $data;
		if (empty($url) && isset($data['url'])) {
			$url = $data['url'];
			unset($data['url']);
		}

		$modelTmpl = $this->model('matter\tmplmsg');
		$tmpl = $modelTmpl->byId($tmplmsgId, array('cascaded' => 'Y'));
		$ufrom = $modelTmpl->query_val_ss([
			'ufrom',
			'xxt_site_account',
			"siteid='$site' and (wx_openid='$openid' or qy_openid='$openid')",
		]);
		/*发送消息*/
		if (!empty($tmpl->templateid) && $ufrom === 'wx') {
			/*只有微信号才有模板消息ID*/
			$msg = array(
				'touser' => $openid,
				'template_id' => $tmpl->templateid,
				'url' => $url,
			);
			if ($tmpl->params) {
				foreach ($tmpl->params as $p) {
					$value = isset($data[$p->pname]) ? $data[$p->pname]->name : (isset($data[$p->id]) ? $data[$p->id]->name : '');
					$msg['data'][$p->pname] = array('value' => $value, 'color' => '#173177');
				}
			}
			if ($snsConfig === null) {
				$snsConfig = $this->model('sns\wx')->bySite($site);
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
					$value = isset($data[$p->pname]) ? $data[$p->pname]->name : (isset($data[$p->id]) ? $data[$p->id]->name : '');
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
			$rst = $this->sendByOpenid($site, $openid, $msg, $ufrom);
			if ($rst[0] === false) {
				return $rst;
			}
			$msg['template_id'] = 0;
			$msgid = 0;
		}
		/*记录日志*/
		$log = [
			'siteid' => $site,
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
	/**
	 * 发送模板消息
	 * @param $name string eg:site.matter.push、site.enroll.submit and so on
	 */
	public function send_action($site, $name, $openid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelNot = $this->model('site\notice');

		if ($notice = $modelNot->byName($site, $name)) {
			$modelMap = $this->model('matter\tmplmsg\config');
			$notice->tmplmsg_config_id && $notice->tmplmsgConfig = $modelMap->byId($notice->tmplmsg_config_id, ['cascaded' => 'Y']);
		} else {
			return new \ResponseError("没有设置模板消息或发送类型不支持。");
		}

		$rst = $this->tmplSendByOpenid($site, $notice->tmplmsgConfig->tmplmsg->id, $openid, $notice->tmplmsgConfig->mapping);

		if ($rst[0] === true) {
			return new \ResponseData('ok');
		} else {
			return new \ResponseError($rst);
		}
	}
}