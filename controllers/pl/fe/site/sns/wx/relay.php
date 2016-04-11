<?php
namespace pl\fe\site\sns\wx;

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/base.php';
/**
 * 微信公众号
 */
class relay extends \pl\fe\base {
	/**
	 * get all text call.
	 */
	public function index_action($site) {
		\TPL::output('/pl/fe/site/sns/wx/main');
		exit;
	}
	/**
	 *
	 */
	public function list_action($site) {
		$relays = $this->model('sns\wx\relay')->bySite($site);

		return new \ResponseData($relays);
	}
	/**
	 * 添加转发接口
	 */
	public function add_action($site) {
		$r = array();
		$r['siteid'] = $site;
		$r['title'] = '新转发接口';

		$r = $this->model('sns\wx\relay')->add($r);

		return new \ResponseData($r);
	}
	/**
	 * 更新转发接口
	 */
	public function update_action($site, $id) {
		$nv = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_call_relay_wx',
			$nv,
			"id='$id'"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function remove_action($site, $id) {
		$rst = $this->model()->update(
			'xxt_call_relay_wx',
			array('state' => 0),
			"siteid='$site' and id='$id'"
		);

		return new \ResponseData($rst);
	}
}