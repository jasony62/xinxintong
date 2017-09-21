<?php
namespace pl\fe;

require_once dirname(__FILE__) . '/base.php';
/**
 * 登录用户的入口页面
 */
class main extends \pl\fe\base {
	/**
	 * 用户个人工作台
	 */
	public function index_action($ver = null) {
		\TPL::output('/pl/fe/console/frame');
		exit;
	}
	/**
	 * 列出当前用户最近操作的素材
	 */
	public function recent_action($page = 1, $size = 30) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oFilter = $this->getPostJson();
		$modelLog = $this->model('matter\log');

		// 分页参数
		$p = new \stdClass;
		$p->at = $page;
		$p->size = $size;

		$options = [
			'page' => $p,
		];
		if (!empty($oFilter->byTitle)) {
			$options['byTitle'] = $oFilter->byTitle;
		}
		// 类型参数
		if (!empty($oFilter->byType)) {
			$options['byType'] = $oFilter->byType;
		}
		// 活动场景
		if (!empty($oFilter->scenario)) {
			$options['scenario'] = $oFilter->scenario;
		}

		$matters = $modelLog->recentMattersByUser($oUser, $options);

		return new \ResponseData($matters);
	}
	/**
	 * 个人工作台素材星标
	 *
	 * @param int $id 是日志记录的ID
	 */
	public function top_action($site, $matterId, $matterType, $matterTitle) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		//检查管理员的权限
		$model = $this->model('matter\log');
		$model->setOnlyWriteDbConn(true);
		$data = $model->query_val_ss([
			'urole',
			'xxt_site_admin',
			['siteid' => $site, 'uid' => $user->id],
		]);

		if (empty($data)) {
			return new \ResponseError('当前管理员没有该素材的操作权限！');
		}

		// 记录操作日志
		$matter = new \stdClass;
		$matter->id = $matterId;
		$matter->title = $matterTitle;
		$matter->type = $matterType;
		$model->matterOp($site, $user, $matter, 'top');

		$q = [
			'matter_id,matter_type,matter_title',
			'xxt_log_matter_op',
			['siteid' => $site, 'matter_id' => $matterId, 'matter_type' => $matterType, 'user_last_op' => 'Y', 'operator' => $user->id],
		];

		$one = $model->query_obj_ss($q);

		if (empty($one)) {
			return new \ResponseError('找不到素材的操作记录！');
		}
		//获取当前用户的置顶素材
		$matter = $model->query_obj_ss([
			'id tid,matter_id id,matter_type type,matter_title title',
			'xxt_account_topmatter',
			['siteid' => $site, 'userid' => $user->id, 'matter_id' => $one->matter_id, 'matter_type' => $one->matter_type],
		]);

		if ($matter) {
			return new \ResponseError('已置顶！', 101);
		} else {
			$d['siteid'] = $site;
			$d['userid'] = $user->id;
			$d['top'] = '1';
			$d['top_at'] = time();
			$d['matter_id'] = $one->matter_id;
			$d['matter_type'] = $one->matter_type;
			$d['matter_title'] = $one->matter_title;

			$rst = $model->insert('xxt_account_topmatter', $d);
		}

		return new \ResponseData($rst);
	}
	/**
	 * 置顶列表
	 */
	public function topList_action($site = null, $page = 1, $size = 12) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$p = [
			't.*,l.matter_summary,l.matter_pic,l.matter_scenario',
			'xxt_account_topmatter t,xxt_log_matter_op l',
			"t.siteid=l.siteid and t.matter_id=l.matter_id and t.matter_type=l.matter_type and l.user_last_op='Y' and t.userid='$user->id' and t.userid=l.operator  and (l.operation<>'D' and l.operation<>'Recycle' and l.operation<>'Quit')",
		];
		if (!empty($site)) {
			$site = $model->escape($site);
			$p[2] .= " and l.siteid = '$site'";
		}

		$p2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		$p2['o'] = ['top_at desc'];

		$matters = $model->query_objs_ss($p, $p2);

		$result = new \stdClass;
		$result->matters = $matters;

		if (empty($matters)) {
			$result->total = 0;
		} else {
			$p[0] = 'count(*)';
			$result->total = $model->query_val_ss($p);
		}

		return new \ResponseData($result);
	}
	/**
	 * 删除置顶
	 */
	public function delTop_action($site, $id, $type) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model()->delete(
			'xxt_account_topmatter',
			['siteid' => $site, 'userid' => $oUser->id, 'matter_id' => $id, 'matter_type' => $type]
		);

		return new \ResponseData($rst);
	}
}