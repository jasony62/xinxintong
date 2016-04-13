<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 *
 */
class record extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 活动报名名单
	 *
	 * 1、如果活动仅限会员报名，那么要叠加会员信息
	 * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 */
	public function list_action($site, $app, $page = 1, $size = 30, $signinStartAt = null, $signinEndAt = null, $tags = null, $rid = null, $kw = null, $by = null, $orderby = null, $contain = null) {
		/*应用*/
		$modelApp = $this->model('matter\enroll');
		$app = $modelApp->byId($app);
		/*参数*/
		$options = array(
			'page' => $page,
			'size' => $size,
			'tags' => $tags,
			'signinStartAt' => $signinStartAt,
			'signinEndAt' => $signinEndAt,
			'rid' => $rid,
			'kw' => $kw,
			'by' => $by,
			'orderby' => $orderby,
			'contain' => $contain,
		);
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->find($site, $app, $options);
		/* 获得数据项定义 */
		//$result->schema = json_decode($app->data_schemas);

		return new \ResponseData($result);
	}
	/**
	 * 给符合条件的登记记录打标签
	 */
	public function exportByData_action($app) {
		$posted = $this->getPostJson();
		$filter = $posted->filter;
		$target = $posted->target;
		$includeData = isset($posted->includeData) ? $posted->includeData : 'N';

		if (!empty($target)) {
			/*更新应用标签*/
			$modelApp = $this->model('app\enroll');
			/*给符合条件的记录打标签*/
			$modelRec = $this->model('app\enroll\record');
			$q = array(
				'distinct enroll_key',
				'xxt_enroll_record_data',
				"aid='$app' and state=1",
			);
			$eks = null;
			foreach ($filter as $k => $v) {
				$w = "(name='$k' and ";
				$w .= "concat(',',value,',') like '%,$v,%'";
				$w .= ')';
				$q2 = $q;
				$q2[2] .= ' and ' . $w;
				$eks2 = $modelRec->query_vals_ss($q2);
				$eks = ($eks === null) ? $eks2 : array_intersect($eks, $eks2);
			}
			if (!empty($eks)) {
				$objApp = $modelApp->byId($target, array('cascaded' => 'N'));
				$options = array('cascaded' => $includeData);
				foreach ($eks as $ek) {
					$record = $modelRec->byId($ek, $options);
					$user = new \stdClass;
					$user->openid = $record->openid;
					$user->nickname = $record->nickname;
					$user->vid = '';
					$newek = $modelRec->add($this->mpid, $objApp, $user);
					if ($includeData === 'Y') {
						$modelRec->setData($user, $objApp->mpid, $objApp->id, $newek, $record->data);
					}
				}
			}
		}

		return new \ResponseData('ok');
	}
	/**
	 * 手工添加登记信息
	 *
	 * @param string $aid
	 */
	public function add_action($site, $app) {
		$posted = $this->getPostJson();
		$current = time();
		$modelRec = $this->model('app\enroll\record');
		$ek = $modelRec->genKey($site, $app);

		$r = array();
		$r['aid'] = $app;
		$r['siteid'] = $site;
		$r['enroll_key'] = $ek;
		$r['enroll_at'] = $current;
		$r['signin_at'] = $current;
		if (isset($posted->tags)) {
			$r['tags'] = $posted->tags;
			$this->model('app\enroll')->updateTags($app, $posted->tags);
		}
		$id = $modelRec->insert('xxt_enroll_record', $r, true);
		$r['id'] = $id;
		/**
		 * 登记数据
		 */
		if (isset($posted->data)) {
			foreach ($posted->data as $n => $v) {
				if (in_array($n, array('signin_at', 'comment'))) {
					continue;
				}
				$cd = array(
					'aid' => $app,
					'enroll_key' => $ek,
					'name' => $n,
					'value' => $v,
				);
				$modelRec->insert('xxt_enroll_record_data', $cd, false);
				$r['data'][$n] = $v;
			}
		}

		return new \ResponseData($r);
	}
	/**
	 * 清空一条登记信息
	 */
	public function remove_action($site, $app, $key) {
		$rst = $this->model('app\enroll\record')->remove($app, $key);

		return new \ResponseData($rst);
	}
	/**
	 * 更新登记记录
	 *
	 * @param string $app
	 * @param $ek record's key
	 */
	public function update_action($site, $app, $ek) {
		$record = $this->getPostJson();
		$model = $this->model();

		foreach ($record as $k => $v) {
			if (in_array($k, array('signin_at', 'tags', 'comment'))) {
				$model->update(
					'xxt_enroll_record',
					array($k => $v),
					"enroll_key='$ek'"
				);
				if ($k === 'tags') {
					$this->model('app\enroll')->updateTags($app, $v);
				}
			} else if ($k === 'data' and is_object($v)) {
				foreach ($v as $cn => $cv) {
					/**
					 * 检查数据项是否存在，如果不存在就先创建一条
					 */
					$q = array(
						'count(*)',
						'xxt_enroll_record_data',
						"enroll_key='$ek' and name='$cn'",
					);
					if (1 === (int) $model->query_val_ss($q)) {
						$model->update(
							'xxt_enroll_record_data',
							array('value' => $cv),
							"enroll_key='$ek' and name='$cn'"
						);
					} else {
						$cd = array(
							'aid' => $app,
							'enroll_key' => $ek,
							'name' => $cn,
							'value' => $cv,
						);
						$model->insert('xxt_enroll_record_data', $cd, false);
					}
				}
			}
		}

		return new \ResponseData('ok');
	}
}