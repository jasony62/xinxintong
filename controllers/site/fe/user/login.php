<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点用户
 */
class login extends \site\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'] = array();
		$rule_action['actions'][] = 'index';
		$rule_action['actions'][] = 'do';

		return $rule_action;
	}
	/**
	 * 打开登录页面
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/login');
		exit;
	}
	/**
	 * 执行帐号注册
	 */
	public function do_action() {
		$data = $this->getPostJson();
		if (empty($data->uname) || empty($data->password)) {
			return new \ResponseError("登录信息不完整");
		}

		$modelAct = $this->model('site\user\account');
		$account = $modelAct->validate($this->siteId, $data->uname, $data->password);
		if (is_string($account)) {
			return new \ResponseError($account);
		}
		/*记录登录状态*/
		$fromip = $this->client_ip();
		$modelAct->updateLastLogin($account->uid, $fromip);
		/*更新cookie状态*/
		/*user*/
		$modelWay = $this->model('site\fe\way');
		$cookieUser = $modelWay->getCookieUser($this->siteId);
		/*合并用户*/
		if ($account->uid !== $cookieUser->uid) {
			if ($persisted = $modelAct->byId($cookieUser->uid)) {
				$modelAct->update('xxt_site_account', array('assoc_id' => $account->uid), "uid='{$cookieUser->uid}'");
			}
			$cookieUser->uid = $account->uid;
		}
		$cookieUser->uname = $data->uname;
		$cookieUser->loginExpire = time() + (86400 * TMS_COOKIE_SITE_LOGIN_EXPIRE);
		/*站点认证身份*/
		$modelMem = $this->model('site\user\member');
		$members = $modelMem->byUser($this->siteId, $cookieUser->uid);
		!empty($members) && $cookieUser->members = new \stdClass;
		foreach ($members as $member) {
			$cookieUser->members->{$member->schema_id} = $member;
		}
		/*社交帐号认证用户*/
		if (isset($cookieUser->sns)) {
			$model = $this->model();
			foreach ($cookieUser->sns as $snsName => $snsUser) {
				$modelSnsUser = \TMS_App::M('sns\\' . $snsName . '\fan');
				$modelSnsUser->modifyByOpenid($this->siteId, $snsUser->openid, array('userid' => $cookieUser->uid));
			}
		}
		$snsUsers = array();
		/*wx用户*/
		$snsUsers['wx'] = \TMS_App::M('sns\wx\fan')->byUser($this->siteId, $cookieUser->uid);
		/*yx用户*/
		$snsUsers['yx'] = \TMS_App::M('sns\yx\fan')->byUser($this->siteId, $cookieUser->uid);
		/*qy用户*/
		//$snsUsers['qy'] = \TMS_App::M('sns\qy\fan')->byUser($this->siteId, $cookieUser->uid);
		if (!empty($snsUsers)) {
			!isset($cookieUser->sns) && $cookieUser->sns = new \stdClass;
			foreach ($snsUsers as $snsName => $snsUser) {
				if ($snsUser) {
					$cookieUser->sns->{$snsName} = $snsUser;
				}
			}
		}
		/*缓存用户信息*/
		$modelWay->setCookieUser($this->siteId, $cookieUser);

		return new \ResponseData($cookieUser);
	}
}