<?php
namespace op\enroll;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 登记活动抽奖
 */
class lottery extends \member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 走马灯抽奖页面
	 */
	public function index_action($aid) {
		$app = $this->model('app\enroll')->byId($aid);

		\TPL::assign('title', $app->title);
		\TPL::output('/op/enroll/lottery');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($aid) {
		$option = array('fields' => 'lottery_page_id', 'cascaded' => 'N');
		$app = $this->model('app\enroll')->byId($aid, $option);
		$page = $this->model('code\page')->byId($app->lottery_page_id);

		$params = array(
			'page' => $page,
		);

		return new \ResponseData($params);
	}
	/**
	 * 抽奖的轮次
	 */
	public function roundsGet_action($aid) {
		$options = array(
			'fields' => 'round_id,title,autoplay,targets,times',
		);
		$rounds = $this->model('app\enroll\lottery')->rounds($aid, $options);
		foreach ($rounds as &$round) {
			$round->targets = json_decode($round->targets);
		}

		return new \ResponseData($rounds);
	}
	/**
	 * 参与抽奖的人
	 */
	public function playersGet_action($aid, $rid, $hasData = 'Y') {
		$players = $this->model('app\enroll\lottery')->players($aid, $rid, $hasData);

		return new \ResponseData($players);
	}
	/**
	 * 清空参与抽奖的人
	 */
	public function empty_action($aid) {
		$rst = $this->model()->delete('xxt_enroll_lottery', "aid='$aid'");

		return new \ResponseData($rst);
	}
	/**
	 * 记录中奖人
	 */
	public function done_action($aid, $rid, $ek) {
		$fans = $this->getPostJson();

		$i = array(
			'aid' => $aid,
			'round_id' => $rid,
			'enroll_key' => $ek,
			'openid' => $fans->openid,
			'nickname' => $fans->nickname,
			'draw_at' => time(),
		);

		$this->model()->insert('xxt_enroll_lottery', $i, false);

		return new \ResponseData('success');
	}
}