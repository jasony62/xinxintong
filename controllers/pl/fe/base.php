<?php
namespace pl\fe;
/**
 *
 */
class base extends \TMS_CONTROLLER {
	/**
	 *
	 */
	protected $mpid;
	/**
	 *
	 */
	private $yx_token;
	/**
	 *
	 */
	private $wx_token;
	/**
	 *
	 */
	public function __construct() {
		$account = \TMS_CLIENT::account();
		if ($account === false) {
			return new \ResponseTimeout();
		}
		if (isset($_GET['mpid']) && ($mpid = $_GET['mpid'])) {
			$_SESSION['mpid'] = $mpid;
		} else if (!isset($_SESSION['mpid']) || !($mpid = $_SESSION['mpid'])) {
			header('HTTP/1.0 500 parameter error:mpid is empty.');
			die('参数不完整');
		}
		$this->mpid = $mpid;
		/**
		 * entries
		 */
		$prights = $this->model('mp\permission')->hasMpRight(
			$this->mpid,
			array('mpsetting', 'matter', 'app', 'reply', 'user', 'analyze'),
			'read'
		);
		$entries = array();
		(true === $prights || $prights['mpsetting']['read_p'] === 'Y') && $entries['/rest/mp/mpaccount'] = array('title' => '账号管理', 'entry' => '');
		(true === $prights || $prights['matter']['read_p'] === 'Y') && $entries['/rest/mp/matter'] = array('title' => '素材管理', 'entry' => '');
		(true === $prights || $prights['app']['read_p'] === 'Y') && $entries['/rest/mp/app'] = array('title' => '应用管理', 'entry' => '');
		(true === $prights || $prights['reply']['read_p'] === 'Y') && $entries['/rest/mp/call'] = array('title' => '回复管理', 'entry' => '');
		(true === $prights || $prights['user']['read_p'] === 'Y') && $entries['/page/mp/user/received'] = array('title' => '用户管理', 'entry' => '');
		(true === $prights || $prights['analyze']['read_p'] === 'Y') && $entries['/page/mp/analyze'] = array('title' => '统计分析', 'entry' => '');

		\TPL::assign('mp_view_entries', $entries);
	}
	/**
	 *
	 */
	protected function getMpaccount() {
		return \TMS_APP::M('mp\mpaccount')->byId($this->mpid, 'name,mpid,mpsrc,asparent,parent_mpid,yx_joined,wx_joined,qy_joined');
	}
	/**
	 * 获得父公众号的ID
	 */
	protected function getParentMpid() {
		$mpa = $this->getMpaccount();
		return empty($mpa->parent_mpid) ? false : $mpa->parent_mpid;
	}
	
	/*-----------------------原----------------------------*/
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 * 二维码
	 */
	public function qrcode_action($url) {
		include TMS_APP_DIR . '/lib/qrcode/qrlib.php';
		// outputs image directly into browser, as PNG stream
		\QRcode::png($url);
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
	protected function tmplmsgSendByOpenid($tmplmsgId, $openid, $data, $url = null) {
		/*模板定义*/
		is_object($data) && $data = (array) $data;
		if (empty($url) && isset($data['url'])) {
			$url = $data['url'];
			unset($data['url']);
		}

		$tmpl = $this->model('matter\tmplmsg')->byId($tmplmsgId, array('cascaded' => 'Y'));
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
			$wxConfig = $this->model('sns\wx')->bySite($siteId);
			$proxy = $this->model('sns\wx\proxy', $wxConfig);
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
		$log = array(
			'siteid' => $siteId,
			'openid' => $openid,
			'tmplmsg_id' => $tmplmsgId,
			'template_id' => $msg['template_id'],
			'data' => $this->model()->escape(json_encode($msg)),
			'create_at' => time(),
			'msgid' => $msgid,
		);
		$this->model()->insert('xxt_log_tmplmsg', $log, false);

		return array(true);
	}
}