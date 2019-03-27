<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 记录活动积分管理控制器
 */
class topic extends main_base {
	/**
	 * 公共专题列表
	 */
	public function listPublicBySite_action($site, $page = '', $size = '') {
		$model = $this->model();
		$post = $this->getPostJson();

		$q = [
			'id,create_at,title,summary,rec_num,userid,group_id,nickname,share_in_group,is_public',
			'xxt_enroll_topic',
			['state' => 1, 'siteid' => $site, 'is_public' => 'Y'],
		];

		$q2 = ['o' => 'create_at desc'];
		if (!empty($post->orderby)) {
			switch ($post->orderby) {
			case 'earliest':
				$q2['o'] = ['create_at asc'];
				break;
			case 'lastest':
				$q2['o'] = ['create_at desc'];
				break;
			}
		}
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		$topics = $model->query_objs_ss($q, $q2);
		foreach ($topics as $topic) {
			$topic->type = 'topic';
		}
		
		$oResult = new \stdClass;
		$oResult->topics = $topics;
		if (!empty($page) && !empty($size) && count($topics) >= $size) {
			$q[0] = 'count(id)';
			$oResult->total = (int) $model->query_val_ss($q);
		} else {
			$oResult->total = count($topics);
		}

		return new \ResponseData($oResult);
	}
}