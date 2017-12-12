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
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'][] = 'index';
		$rule_action['actions'][] = 'preview';
		// $rule_action['actions'][] = 'get';
		// $rule_action['actions'][] = 'update';
		// $rule_action['actions'][] = 'remove';

		return $rule_action;
	}
	/**
	 * 需要检查的素材
	 */
	protected function get_access_app() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'][] = 'article';
		$rule_action['actions'][] = 'channel';
		$rule_action['actions'][] = 'contribute';
		$rule_action['actions'][] = 'custom';
		$rule_action['actions'][] = 'enroll';
		$rule_action['actions'][] = 'group';
		$rule_action['actions'][] = 'inner';
		$rule_action['actions'][] = 'link';
		$rule_action['actions'][] = 'lottery';
		$rule_action['actions'][] = 'merchant';
		$rule_action['actions'][] = 'mission';
		$rule_action['actions'][] = 'news';
		$rule_action['actions'][] = 'signin';
		$rule_action['actions'][] = 'text';
		$rule_action['actions'][] = 'tmplmsg';
		$rule_action['actions'][] = 'wall';

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
			$mpa = $this->model('mp\mpaccount')->byId($siteId, 'mpsrc');
			$txt = array();
			$txt[] = $tmpl->title;
			if ($tmpl->params) {
				foreach ($tmpl->params as $p) {
					$value = isset($data[$p->pname]) ? $data[$p->pname] : (isset($data[$p->id]) ? $data[$p->id] : '');
					$txt[] = $p->plabel . '：' . $value;
				}
			}
			if (!empty($url)) {
				if ($mpa->mpsrc === 'yx') {
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
			$this->sendByOpenid($siteId, $openid, $msg);
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
	/**
	 * 二维码
	 */
	public function qrcode_action($site, $url, $size = 3) {
		include TMS_APP_DIR . '/lib/qrcode/qrlib.php';
		// outputs image directly into browser, as PNG stream
		//@ob_clean();
		\QRcode::png($url, false, QR_ECLEVEL_L, $size);
	}
	/**
	 * 素材访问控制
	 */
	public function accessControlUser($path) {
		$modelWay = \TMS_APP::M('site\fe\way');
		if (($user = $modelWay->getCookieRegUser()) === false) {
			return [false, '请登陆'];
		}

		//暂时未处理pl/fe后没有下一级的情况
		$path = explode('/', strstr($path, 'fe'));
		if(empty($path[1])){
			return [true];
		}

		if($path[1] === 'matter'){
			$matter_type = $path[2];
			if (isset($_GET['id'])) {
				$matter_id = $_GET['id'];
			} else if (isset($_GET['app'])) {
				$matter_id = $_GET['app'];
			}

			$accessApp = $this->get_access_app();
			if ($accessApp['rule_type'] === 'black') {
				if (!in_array($matter_type, $accessApp['actions'])) {
					return [true];
				}
			} else if ($accessApp['rule_type'] === 'white') {
				if (in_array($matter_type, $accessApp['actions'])) {
					return [true];
				}
			}
				
			$options = ['cascaded' => 'N', 'fields' => 'siteid,id,title'];
			if ($matter_type === 'lottery') {
				unset($options['cascaded']);
			}
			$app = $this->model('matter\\' . $matter_type)->byId($matter_id, $options);
			if (!$app) {
				return [false, '指定的素材不存在'];
			}

			$site = $app->siteid;
			$modelSite = \TMS_APP::M('site\admin');
			$siteUser = $modelSite->byUid($site, $user->unionid);
			if ($siteUser !== false) {
				return [true];
			}

			/*检查此素材是否在项目中*/
			if($matter_type !== 'mission'){
				$q = [
					'mission_id',
					'xxt_mission_matter',
					['matter_id' => $matter_id, 'matter_type' => $matter_type],
				];
				$mission = $modelSite->query_obj_ss($q);
			}else{
				$mission = new \stdClass;
				$mission->mission_id = $matter_id;
			}
			if ($mission) {
				$q2 = [
					'id',
					'xxt_mission_acl',
					['mission_id' => $mission->mission_id, 'coworker' => $user->unionid, 'state' => 1],
				];
				$missionUser = $modelSite->query_obj_ss($q2);
				if($missionUser){
					return [true];
				}
			}
		} else if ($path[1] === 'site') {
			$site = $_GET['site'];
			$modelSite = \TMS_APP::M('site\admin');
			$siteUser = $modelSite->byUid($site, $user->unionid);
			if ($siteUser !== false) {
				return [true];
			}
		} else {
			return [true];
		}
		
		return [false, '访问控制未通过'];
	}
}