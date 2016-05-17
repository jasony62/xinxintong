<?php
namespace pl\fe\matter\contribute;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 投稿活动主控制器
 */
class main extends \pl\fe\matter\base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'contribute';
	}
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/contribute/frame');
		exit;
	}
	/**
	 *
	 */
	public function setting_action() {
		\TPL::output('/pl/fe/matter/contribute/frame');
		exit;
	}
	/**
	 *
	 */
	public function running_action() {
		\TPL::output('/pl/fe/matter/contribute/frame');
		exit;
	}
	/**
	 * 返回投稿应用
	 */
	public function get_action($site, $app) {
		$modelCtr = $this->model('matter\contribute');
		$modelRole = $this->model('matter\contribute\role');
		$c = $modelCtr->byId($app);
		/**
		 * belong to channel
		 */
		$c->channels = $this->model('matter\channel')->byMatter($app, 'contribute');
		/**
		 * 参与人
		 */
		$c->initiator = $modelRole->users($site, $app, 'I');
		$c->reviewer = $modelRole->users($site, $app, 'R');
		$c->typesetter = $modelRole->users($site, $app, 'T');
		/**
		 * return
		 */
		return new \ResponseData($c);
	}
	/**
	 * 投稿活动列表
	 */
	public function list_action($site, $page = 1, $size = 30) {
		$q = array(
			'*',
			'xxt_contribute',
			"siteid='$site' and state<>0",
		);
		$q2['o'] = 'create_at desc';
		if ($c = $this->model()->query_objs_ss($q, $q2)) {
			$result['apps'] = $c;
			$q[0] = 'count(*)';
			$total = (int) $this->model()->query_val_ss($q);
			$result['total'] = $total;
			return new \ResponseData($result);
		}
		return new \ResponseData(array());
	}
	/**
	 * 创建投稿活动
	 */
	public function create_action($site) {
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}

		$current = time();
		$site = $this->model('site')->byId($site, array('fields' => 'id,heading_pic'));

		$appId = uniqid();
		$newapp['siteid'] = $site->id;
		$newapp['id'] = $appId;
		$newapp['creater'] = $user->id;
		$newapp['creater_name'] = $user->name;
		$newapp['creater_src'] = $user->src;
		$newapp['create_at'] = $current;
		$newapp['modifier'] = $user->id;
		$newapp['modifier_src'] = $user->src;
		$newapp['modifier_name'] = $user->name;
		$newapp['modify_at'] = $current;
		$newapp['title'] = '新投稿活动';
		$newapp['summary'] = '';
		$newapp['pic'] = $site->heading_pic;

		$this->model()->insert('xxt_contribute', $newapp, false);

		$c = $this->model('matter\contribute')->byId($appId);
		/*记录操作日志*/
		$c->type = 'contribute';
		$this->model('log')->matterOp($site->id, $user, $c, 'C');

		return new \ResponseData($c);
	}
	/**
	 * 更新
	 */
	public function update_action($site, $app) {
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}
		$modelCtr = $this->model('matter\contribute');
		$nv = $this->getPostJson();

		if (isset($nv->params)) {
			$nv->params = json_encode($nv->params);
		}
		$nv->modifier = $user->id;
		$nv->modifier_src = $user->src;
		$nv->modifier_name = $user->name;
		$nv->modify_at = time();

		$rst = $modelCtr->update('xxt_contribute', $nv, "id='$app'");
		/*记录操作日志*/
		if ($rst) {
			$app = $modelCtr->byId($app, 'id,title,summary,pic');
			$app->type = 'contribute';
			$this->model('log')->matterOp($site, $user, $app, 'U');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 删除
	 */
	public function remove_action($site, $app) {
		$rst = $this->model()->update(
			'xxt_contribute',
			array('state' => 0),
			"siteid='$site' and id='$app'"
		);

		return new \ResponseData($rst);
	}
}