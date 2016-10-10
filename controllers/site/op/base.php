<?php
namespace site\op;
/**
 * 站点前端访问控制器基类
 */
class base extends \TMS_CONTROLLER {
	/**
	 * 当前访问的站点ID
	 */
	protected $siteId;
	/**
	 * 访问令牌
	 */
	protected $accessToken;
	/**
	 * 对请求进行通用的处理
	 */
	public function __construct() {
		if (empty($_GET['site'])) {
			header('HTTP/1.0 500 parameter error:site is empty.');
			die('参数错误！');
		}

		$this->siteId = $_GET['site'];
		$this->accessToken = isset($_GET['accessToken']) ? $_GET['accessToken'] : null;
	}
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

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
	 * 检查令牌是否有效
	 */
	protected function checkAccessToken() {
		$modelTok = $this->model('q\urltoken');

		return $modelTok->checkAccessToken($this->accessToken);
	}
}