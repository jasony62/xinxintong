<?php
namespace pl\fe\site\sns;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 易信公众号
 */
class yx extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/sns/yx/main');
		exit;
	}
	/**
	 * 获得公众号配置信息
	 */
	public function get_action($id) {
		$modelYx = $this->model('site\sns\yx');
		$yx = $modelYx->bySite($id);
		if ($yx === false) {
			/* 不存在就创建一个 */
			$yx = $modelYx->create($id);
		}
		return new \ResponseData($yx);
	}
	/**
	 * 更新账号配置信息
	 */
	public function update_action($id) {
		$nv = $this->getPostJson();

		/* 如果修改了token，需要重新重新进行连接验证 */
		isset($nv->token) && $nv->joined = 'N';

		$rst = $this->model()->update(
			'xxt_site_yx',
			$nv,
			"siteid='$id'"
		);

		return new \ResponseData($rst);
	}
}