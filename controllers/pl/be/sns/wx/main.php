<?php
namespace pl\be\sns\wx;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 微信公众号
 */
class main extends \pl\be\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/be/sns/wx/main');
		exit;
	}
	/**
	 *
	 */
	public function setting_action() {
		\TPL::output('/pl/be/sns/wx/main');
		exit;
	}
	/**
	 * 获得公众号配置信息
	 */
	public function get_action() {
		$modelWx = $this->model('pl\sns\wx');
		$wx = $modelWx->byPl();
		if ($wx === false) {
			/* 不存在就创建一个 */
			$data = [
				'create_at' => time(),
				'plid' => md5(uniqid('xxt') . mt_rand()),
			];
			$modelWx->insert('xxt_pl_wx', $data, false);

			$wx = $modelWx->byPl();
		}

		return new \ResponseData($wx);
	}
	/**
	 * 更新账号配置信息
	 */
	public function update_action() {
		$nv = $this->getPostJson();

		/* 如果修改了token，需要重新重新进行连接验证 */
		isset($nv->token) && $nv->joined = 'N';

		$rst = $this->model()->update(
			'xxt_pl_wx',
			$nv,
			"1=1"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function checkJoin_action($site) {
		$wx = $this->model('pl\sns\wx')->byPl();

		return new \ResponseData($wx->joined);
	}
}