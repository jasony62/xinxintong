<?php
namespace pl\fe\site\sns\qy;

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/base.php';
/**
 * 微信公众号
 */
class relay extends \pl\fe\base {
	/**
	 * get all text call.
	 */
	public function index_action($site) {
		\TPL::output('/pl/fe/site/sns/qy/main');
		exit;
	}
	/**
	 *
	 */
	public function list_action($site) {
		$relays = $this->model('sns\qy\relay')->bySite($site);

		return new \ResponseData($relays);
	}
	/**
	 * 添加转发接口
	 */
	public function add_action($site) {
		$r = array();
		$r['siteid'] = $site;
		$r['title'] = '新转发接口';

		$r = $this->model('sns\qy\relay')->add($r);

		return new \ResponseData($r);
	}
	/**
	 * 更新转发接口
	 */
	public function update_action($site, $id) {
		$nv = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_call_relay_qy',
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
			'xxt_call_relay_qy',
			array('state' => 0),
			"siteid='$site' and id='$id'"
		);

		return new \ResponseData($rst);
	}
}