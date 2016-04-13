<?php
namespace site\op\matter\group;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 *
 */
class main extends \site\op\base {
	/**
	 *
	 */
	public function index_action($app) {
		$app = $this->model('matter\group')->byId($app);
		\TPL::assign('title', $app->title);
		\TPL::output('site/op/matter/group/main');
		exit;
	}
	/**
	 *
	 */
	public function pageGet_action($app) {
		$option = array('fields' => 'page_code_id', 'cascaded' => 'N');
		$app = $this->model('matter\group')->byId($app, $option);
		$page = $this->model('code/page')->byId($app->page_code_id);

		$params = array(
			'page' => $page,
		);

		return new \ResponseData($params);
	}
	/**
	 * 抽奖的轮次
	 */
	public function roundsGet_action($app) {
		$options = array(
			'fields' => 'round_id,title,autoplay,targets,times',
		);
		$rounds = $this->model('matter\group\round')->find($app, $options);
		foreach ($rounds as &$round) {
			$round->targets = json_decode($round->targets);
		}

		return new \ResponseData($rounds);
	}
	/**
	 *
	 */
	public function usersGet_action($app, $rid, $hasData = 'Y') {
		$model = $this->model('matter\group\player');
		$players = $model->playersByRound($app, $rid, $hasData);
		$winners = $model->winnersByRound($app, $rid);

		$result = array(
			'players' => &$players,
			'winners' => &$winners,
		);

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function empty_action($app) {
		$rst = $this->model()->delete('xxt_group_result', "aid='$app'");

		return new \ResponseData($rst);
	}
	/**
	 * 记录中奖人
	 */
	public function done_action($app, $rid, $ek) {
		$user = $this->getPostJson();

		$i = array(
			'aid' => $app,
			'round_id' => $rid,
			'enroll_key' => $ek,
			'userid' => isset($user->uid) ? $user->uid : '',
			'nickname' => $user->nickname,
			'draw_at' => time(),
		);

		$this->model()->insert('xxt_group_result', $i, false);

		return new \ResponseData('ok');
	}
}