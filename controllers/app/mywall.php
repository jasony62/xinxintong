<?php
include_once dirname(dirname(__FILE__)) . '/member_base.php';
/**
 *
 */
class mywall extends member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 返回活动页
	 *
	 * 活动是否只向会员开放，如果是要求先成为会员，否则允许直接
	 * 如果已经报过名如何判断？
	 * 如果已经是会员，则可以查看和会员的关联
	 * 如果不是会员，临时分配一个key，保存在cookie中，允许重新报名
	 *
	 */
	public function index_action($mpid, $wid, $mocker = '', $code = null) {
		empty($mpid) && $this->outputError('没有指定当前运行的公众号');
		empty($wid) && $this->outputError('信息墙id为空');

		if ($code !== null) {
			$who = $this->getOAuthUserByCode($mpid, $code);
		} else {
			/**
			 * 为测试方便使用
			 */
			if (!empty($mocker)) {
				$who = $mocker;
				$this->setCookieOAuthUser($mpid, $mocker);
			} else {
				if (!$this->oauth($mpid)) {
					$who = null;
				}

			}
		}
		$this->afterOAuth($mpid, $wid, $who);
	}
	/**
	 * 返回活动页面
	 */
	private function afterOAuth($mpid, $wid, $who = null) {
		$model = $this->model('app\wall');
		$wall = $model->byId($wid);
		/**
		 * 当前访问用户
		 */
		$fan = $this->getCookieOAuthUser($mpid);

		$data = $model->approvedMessages($mpid, $wid, 0);
		$messages = $data ? $data[0] : array();

		\TPL::assign('title', '聊天记录');
		\TPL::assign('wid', $wid);
		\TPL::assign('openid', $fan->openid);
		\TPL::assign('messages', $messages);
		$this->view_action('/app/wall/mywall');
	}
}
