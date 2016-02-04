<?php
namespace coin;

require_once dirname(dirname(__FILE__)) . '/member_base.php';
/**
 * 积分打赏控制器
 */
class pay extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 在session和cookie中保留一个一次性的token，用于验证打赏页面是否是通过合法页面获得
	 */
	private function _makeTransferToken($mpid) {
		$tokenName = $mpid . '_coin_transfer_token';
		$token = $this->model()->gen_salt(6);
		$_SESSION[$tokenName] = $token;
		$this->mySetCookie('_' . $tokenName, $token, time() + (60 * 5));

		return $token;
	}
	/**
	 * 检查转账的key是否一致
	 * 检查后立刻清理数据
	 */
	private function _checkTransferToken($mpid) {
		$tokenName = $mpid . '_coin_transfer_token';
		/*session*/
		if (!isset($_SESSION[$tokenName])) {
			return false;
		}
		$tokenInSession = $_SESSION[$tokenName];
		unset($_SESSION[$tokenName]);
		/*cookie*/
		$tokenInCookie = $this->myGetCookie('_' . $tokenName);
		$this->mySetCookie('_' . $tokenName, '', time() - 60);

		return $tokenInSession === $tokenInCookie;
	}
	/**
	 * 返回打赏页面
	 */
	public function index_action($mpid, $code = null, $mocker = null) {
		empty($mpid) && $this->outputError('没有指定当前运行的公众号');

		/*获得用户当前用户的ID*/
		$openid = $this->doAuth($mpid, $code, $mocker);
		/*生成一个token*/
		$this->_makeTransferToken($mpid);

		\TPL::output('/coin/pay');
		exit;
	}
	/**
	 * 返回定制页面
	 *
	 * @param string $mpid
	 * @param string $matter type,id
	 */
	public function pageGet_action($mpid, $matter) {
		/*获得页面定义*/
		$html = file_get_contents(TMS_APP_DIR . '/controllers/mp/_template/coin/pay/basic.html');
		$css = file_get_contents(TMS_APP_DIR . '/controllers/mp/_template/coin/pay/basic.css');

		$page = array(
			'html' => $html,
			'css' => $css,
		);

		return new \ResponseData(array('page' => $page));
	}
	/**
	 * 进行打赏转账
	 * @param string $mpid
	 * @param string $matter
	 */
	public function transfer_action($mpid, $matter) {
		$payer = $this->getUser($mpid);
		/*检查referer*/
		if (empty($_SERVER['HTTP_REFERER'])) {
			return new \ResponseError("请在指定页面进行操作", 1);
		}
		$referer = $_SERVER['HTTP_REFERER'];
		$host = $_SERVER['HTTP_HOST'];
		if (!preg_match('#' . $host . '\/rest\/coin\/pay#', $referer)) {
			return new \ResponseError("请在指定页面进行操作", 2);
		}
		/*检查token*/
		if (false === $this->_checkTransferToken($mpid)) {
			return new \ResponseError("提交参数错误", 3);
		}
		/*检查素材*/
		list($matterType, $matterId) = explode(',', $matter);
		if (empty($matterType)) {
			return new \ResponseError("没有指定素材的类型", 101);
		}
		if (empty($matterId)) {
			return new \ResponseError("没有指定素材的ID", 102);
		}
		$matter = $this->model('matter\\' . $matterType)->byId($matterId);
		if (!$matter) {
			return new \ResponseError("指定的素材不存在", 103);
		}
		/*检查指定素材是否能接收赏金*/
		if ($matter->creater_src !== 'M') {
			return new \ResponseError("指定的素材无法接收奖励", 104);
		}
		$payee = $this->model('user/member')->byId($matter->creater);
		/*检查用户账号余额*/
		$data = $this->getPostJson();
		if (empty($data->coins)) {
			return new \ResponseError("没有指定转移数额", 201);
		}
		$payer = $this->model('user/fans')->byOpenid($mpid, $payer->openid);
		if ($payer->coin < $data->coins) {
			return new \ResponseError("余额不足", 202);
		}
		/*进行转账*/
		$this->model('coin\log')->transfer($mpid, $payer->openid, $payee->openid, $data->coins);

		return new \ResponseData('ok');
	}
}