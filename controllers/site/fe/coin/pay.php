<?php
namespace site\fe\coin;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 积分打赏控制器
 */
class pay extends \site\fe\base {
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
	private function _makeTransferToken($site) {
		$tokenName = $site . '_coin_transfer_token';
		$token = $this->model()->gen_salt(6);
		$_SESSION[$tokenName] = $token;
		$this->mySetCookie('_' . $tokenName, $token, time() + (60 * 5));

		return $token;
	}
	/**
	 * 检查转账的key是否一致
	 * 检查后立刻清理数据
	 */
	private function _checkTransferToken($site) {
		$tokenName = $site . '_coin_transfer_token';
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
	public function index_action($site, $code = null, $mocker = null) {
		//empty($site) && $this->outputError('没有指定当前运行的公众号');

		/*获得用户当前用户的ID*/
		//$openid = $this->doAuth($site, $code, $mocker);
		/* 生成一个token */
		$this->_makeTransferToken($site);

		\TPL::output('/site/fe/coin/pay');
		exit;
	}
	/**
	 * 返回定制页面
	 *
	 * @param string $site
	 * @param string $matter type,id
	 */
	public function pageGet_action($site, $matter) {
		/*获得页面定义*/
		$html = file_get_contents(TMS_APP_DIR . '/_template/site/fe/coin/pay/basic.html');
		$css = file_get_contents(TMS_APP_DIR . '/_template/site/fe/coin/pay/basic.css');

		$page = [
			'html' => $html,
			'css' => $css,
		];

		return new \ResponseData(['page' => $page]);
	}
	/**
	 * 进行打赏转账
	 *
	 * @param string $site
	 * @param string $matter
	 *
	 */
	public function payByMatter_action($site, $matter) {
		/* 检查referer */
		if (empty($_SERVER['HTTP_REFERER'])) {
			return new \ResponseError("请在指定页面进行操作", 1);
		}
		$referer = $_SERVER['HTTP_REFERER'];
		$host = APP_HTTP_HOST;
		if (!preg_match('#' . $host . '\/rest\/site\/fe\/coin\/pay#', $referer)) {
			return new \ResponseError("请在指定页面进行操作", 2);
		}
		/* 检查token */
		if (false === $this->_checkTransferToken($site)) {
			return new \ResponseError("提交参数错误", 3);
		}
		$data = $this->getPostJson();
		if (empty($data->coins)) {
			return new \ResponseError("没有指定转移数额", 201);
		}
		/* 检查用户积分余额 */
		$payer = $this->model('site\user\account')->byId($this->who->uid, ['fields' => 'uid,nickname,coin']);
		if ($payer->coin < $data->coins) {
			return new \ResponseError("积分余额不足", 202);
		}
		/* 检查素材 */
		list($matterType, $matterId) = explode(',', $matter);
		if (empty($matterType)) {
			return new \ResponseError("没有指定素材的类型", 101);
		}
		if (empty($matterId)) {
			return new \ResponseError("没有指定素材的ID", 102);
		}
		if (false === ($matter = $this->model('matter\\' . $matterType)->byId($matterId))) {
			return new \ResponseError("指定的素材不存在", 103);
		}
		/* 向指定用户支付积分 */
		$act = 'site.user.coin.pay';
		/* 作者是平台用户 */
		$payee = new \stdClass;
		$payee->id = $matter->creater;
		$payee->name = $matter->creater_name;
		$this->model('site\coin\log')->transfer2PlUser($matter, $act, $payer, $payee, $data->coins);

		return new \ResponseData('ok');
	}
}