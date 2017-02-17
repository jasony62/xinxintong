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
	 * 返回当前用户收藏的素材,增加了素材的标题、头图、摘要
	 */
	public function list_action($page = 1, $size = 10) {
		if (false === ($userid = $this->who->uid)) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		
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
		foreach ($matters as $k => $v) {
			if($v->matter_type=='custom'){
				$type='article';	
			}else{
				$type=$v->matter_type;
			}
			
			$d=$this->model()->query_obj_ss(['id,title,summary,pic','xxt_'.$type,"siteid='$this->siteId' and id='$v->matter_id'"]);
			$v->data=$d;
			$b[$k]=$v;
		}
		if($b){
			$matters=(object)$b;
		}
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
		$q = [
			'id',
			'xxt_site_favor',
			"siteid='$this->siteId' and userid='$userid' and matter_id='$id' and matter_type='$type'",
		];
		if (false === $model->query_obj_ss($q)) {
			$log = [
				'siteid' => $this->siteId,
				'userid' => $this->who->uid,
				'nickname' => empty($this->who->nickname) ? '' : $model->escape($this->who->nickname),
				'favor_at' => time(),
				'matter_id' => $id,
				'matter_type' => $type,
				'matter_title' => $model->escape($title),
			];

			$id = $model->insert('xxt_site_favor', $log, true);

			return new \ResponseData($id);
		} else {
			return new \ResponseError('已经收藏过', 101);
		}
	}
	/**
	 * 检查用户是否收藏了指定素材
	 */
	public function byUser_action($id, $type) {
		$model = $this->model();
		$userid = $this->who->uid;
		$q = [
			'id,favor_at',
			'xxt_site_favor',
			"siteid='$this->siteId' and userid='$userid' and matter_id='$id' and matter_type='$type'",
		];
		$log = $model->query_obj_ss($q);

		return new \ResponseData($log);
	}
	/**
	 * 取消收藏
	 */
	public function remove_action($id, $type) {
		$userid = $this->who->uid;

		$rst = $this->model()->delete(
			'xxt_site_favor',
			"siteid='$this->siteId' and userid='$userid' and matter_id='$id' and matter_type='$type'"
		);

		return new \ResponseData($rst);
	}
}