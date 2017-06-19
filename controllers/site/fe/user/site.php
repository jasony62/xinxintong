<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 站点用户
 */
class site extends \site\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/main');
		exit;
	}
	/**
	 * 关注指定团队
	 */
	public function subscribe_action($target) {
		$user = $this->who;
		if (empty($user->unionid)) {
			return new \ResponseError('请登录后再关注');
		}
		$modelSite = $this->model('site');
		if (false === ($target = $modelSite->byId($target))) {
			return new \ObjectNotFoundError();
		}

		if (false === ($modelSite->isSubscribed($user->unionid, $target->id))) {
			//行为增加站点活跃数
			$this->model('site\active')->add($target->id, $user, 0, 'subscribeUser');
		}

		$modelSite->subscribe($user, $target);

		return new \ResponseData('ok');
	}
	/**
	 * 关注指定团队
	 */
	public function unsubscribe_action($target) {
		$user = $this->who;
		if (empty($user->unionid)) {
			return new \ResponseError('请登录后再取消关注');
		}
		$modelSite = $this->model('site');
		if (false === ($target = $modelSite->byId($target))) {
			return new \ObjectNotFoundError();
		}

		$modelSite->unsubscribe($user, $target);

		return new \ResponseData('ok');
	}
	/**
	 * 获得当前用户的关注动态
	 */
	public function trends_action() {
		$user = $this->who;
		if (empty($user->unionid)) {
			return new \ResponseData([]);
		}
		$result = new \stdClass;
		$modelSite = $this->model('site');

		$q = [
			'*',
			'xxt_site_subscription',
			["unionid" => $user->unionid],
		];
		$q2 = ['o' => 'put_at desc'];

		$matters = $modelSite->query_objs_ss($q, $q2);

		$result->trends = $matters;
		$result->total = count($matters);

		return new \ResponseData($result);
	}
}