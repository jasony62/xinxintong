<?php
namespace site\op\matter\group;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 * 分组活动主控制器
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
		$option = array('fields' => 'siteid,page_code_name', 'cascaded' => 'N');
		$app = $this->model('matter\group')->byId($app, $option);
		$page = $this->model('code\page')->lastPublishedByName($app->siteid, $app->page_code_name);

		$params = array(
			'page' => $page,
		);

		return new \ResponseData($params);
	}
	/**
	 * 抽取的轮次
	 */
	public function roundsGet_action($app) {
		$options = array(
			'fields' => 'round_id,title,autoplay,targets,times',
		);
		$rounds = $this->model('matter\group\round')->byApp($app, $options);
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
		$pendings = $model->pendings($app, $hasData);
		$winners = $model->winnersByRound($app, $rid);

		$result = array(
			'players' => &$pendings,
			'winners' => &$winners,
		);

		return new \ResponseData($result);
	}
	/**
	 * 清除分组结果
	 *
	 * @param string $app
	 */
	public function empty_action($app) {
		$rst = $this->model('matter\group\round')->clean($app);

		return new \ResponseData($rst);
	}
	/**
	 * 记录分组结果
	 *
	 * @param string $app
	 */
	public function done_action($app) {
		/*活的应用的轮次，并转换为map*/
		$mapOfRounds = new \stdClass;
		$options = array(
			'fields' => 'round_id,title',
		);
		$rounds = $this->model('matter\group\round')->byApp($app, $options);
		foreach ($rounds as &$round) {
			$mapOfRounds->{$round->round_id} = $round;
		}
		/*记录分组结果*/
		$users = $this->getPostJson();
		if (is_object($users)) {
			$users = array($users);
		}
		$current = time();
		foreach ($users as $user) {
			$winner = array(
				'round_id' => $user->rid,
				'round_title' => $mapOfRounds->{$user->rid}->title,
				'draw_at' => $current,
			);
			$this->model()->update('xxt_group_player', $winner, "aid='$app' and enroll_key='$user->ek'");
		}

		return new \ResponseData('ok');
	}
}