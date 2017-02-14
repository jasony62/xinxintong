<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点用户注册
 */
class register extends \site\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 打开注册页
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/register');
		exit;
	}
	/**
	 * register a new account.
	 *
	 * @param string $siteid
	 */
	public function do_action() {
		$user = $this->who;
		$data = $this->getPostJson();
		if (empty($data->uname) || empty($data->password)) {
			return new \ResponseError("登录信息不完整");
		}

		$modelAct = $this->model('site\user\account');
		/*uname*/
		$uname = $data->uname;
		if ($modelAct->checkUname($this->siteId, $uname)) {
			return new \DataExistedError('注册失败，注册账号已经存在。');
		}
		/*password*/
		$password = $data->password;
		/*options*/
		$options = array(
			'uid' => $user->uid,
			'from_ip' => $this->client_ip(),
		);
		/*nickname*/
		if (isset($data->nickname)) {
			$options['nickname'] = $data->nickname;
			$user->nickname = $data->nickname;
		}
		/*create account*/
		$modelAct->create($this->siteId, $uname, $password, $options);

		/*record account into cookie.*/
		$modelWay = $this->model('site\fe\way');
		$cookieUser = $modelWay->getCookieUser($this->siteId);
		$cookieUser->uname = $uname;
		$cookieUser->loginExpire = time() + (86400 * TMS_COOKIE_SITE_LOGIN_EXPIRE);
		$modelWay->setCookieUser($this->siteId, $cookieUser);

		/**
		 * coin log
		 */
		$siteConfig = $this->model('site')->byId($this->siteId);
		$siteConfig->type = 'site';
		$siteConfig->title = $siteConfig->name;
		$siteConfig->siteid = $this->siteId;
		$modelCoin = $this->model('site\coin\log');
		$modelCoin->award($siteConfig, $user, 'site.user.register');

		return new \ResponseData($cookieUser);
	}
}