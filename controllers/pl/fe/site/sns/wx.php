<?php
namespace pl\fe\site\sns;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 微信公众号
 */
class wx extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/sns/wx/main');
		exit;
	}
	/**
	 * 获得公众号配置信息
	 */
	public function get_action($site) {
		$modelWx = $this->model('site\sns\wx');
		$wx = $modelWx->bySite($site);
		if ($wx === false) {
			/* 不存在就创建一个 */
			$wx = $modelWx->create($site);
		}
		return new \ResponseData($wx);
	}
	/**
	 * 更新账号配置信息
	 */
	public function update_action($site) {
		$nv = $this->getPostJson();

		/* 如果修改了token，需要重新重新进行连接验证 */
		isset($nv->token) && $nv->joined = 'N';

		$rst = $this->model()->update(
			'xxt_site_wx',
			$nv,
			"siteid='$site'"
		);

		return new \ResponseData($rst);
	}
}