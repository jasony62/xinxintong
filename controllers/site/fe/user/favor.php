<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户收藏
 */
class favor extends \site\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/favor/main');
		exit;
	}
	/**
	 * 返回当前用户收藏的素材
	 */
	public function list_action($page = 1, $size = 10) {
		$model = $this->model();
		$userid = $this->who->uid;
		$q = array(
			'id,favor_at,matter_id,matter_type,matter_title',
			'xxt_site_favor',
			"siteid='$this->siteId' and userid='$userid'",
		);
		$q2 = array(
			'o' => 'favor_at desc',
			'r' => array('o' => ($page - 1) * $size, $size, 'l' => $size),
		);
		$matters = $model->query_objs_ss($q, $q2);
		$result = new \stdClass;
		$result->matters = $matters;
		if (empty($matters)) {
			$result->total = 0;
		} else {
			$q[0] = 'count(*)';
			$result->total = $model->query_val_ss($q);
		}

		return new \ResponseData($result);
	}
	/**
	 * 加入收藏
	 */
	public function add_action($id, $type, $title) {
		$model = $this->model();
		$userid = $this->who->uid;
		$q = array(
			'id',
			'xxt_site_favor',
			"siteid='$this->siteId' and userid='$userid' and matter_id='$id' and matter_type='$type'",
		);
		if (false === $model->query_obj_ss($q)) {
			$log = array(
				'site_id' => $this->siteId,
				'userid' => $this->who->uid,
				'nickname' => empty($this->who->nickname) ? '' : $this->who->nickname,
				'favor_at' => time(),
				'matter_id' => $id,
				'matter_type' => $type,
				'matter_title' => $title,
			);

			$id = $this->model()->insert('xxt_site_favor', $log, false);

			return new \ResponseData($id);
		} else {
			return new \ResponseError('已经收藏过', 101);
		}
	}
	/**
	 * 取消收藏
	 */
	public function remove_action($id) {
		$userid = $this->who->uid;
		$rst = $this->model()->delete(
			'xxt_site_favor',
			"siteid='$this->siteId' and userid='$userid' and id=$id"
		);

		return new \ResponseData($rst);
	}
}