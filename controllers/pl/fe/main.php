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
	 */
	public function top_action($site, $matterId, $matterType, $matterTitle) {
		if (false === ($oOperator = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('matter\log')->setOnlyWriteDbConn(true);

		// 当前用户是否已经星标
		$oTopMatter = $model->query_obj_ss([
			'id tid,matter_id id,matter_type type,matter_title title',
			'xxt_account_topmatter',
			['siteid' => $site, 'userid' => $oOperator->id, 'matter_id' => $matterId, 'matter_type' => $matterType],
		]);
		if ($oTopMatter) {
			return new \ResponseError('已星标！', 101);
		}

		$aNewTopMatter['siteid'] = $site;
		$aNewTopMatter['userid'] = $oOperator->id;
		$aNewTopMatter['top'] = '1';
		$aNewTopMatter['top_at'] = time();
		$aNewTopMatter['matter_id'] = $matterId;
		$aNewTopMatter['matter_type'] = $matterType;
		$aNewTopMatter['matter_title'] = $matterTitle;

		$rst = $model->insert('xxt_account_topmatter', $aNewTopMatter);

		// 记录操作日志
		$oMatter = new \stdClass;
		$oMatter->id = $matterId;
		$oMatter->title = $matterTitle;
		$oMatter->type = $matterType;
		$model->matterOp($site, $oOperator, $oMatter, 'top');

		return new \ResponseData($rst);
	}
	/**
	 * 置顶列表
	 */
	public function topList_action($site = null, $page = 1, $size = 12) {
		if (false === ($oOperator = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$p = [
			't.*,l.matter_summary,l.matter_pic,l.matter_scenario',
			'xxt_account_topmatter t,xxt_log_matter_op l',
			"t.siteid=l.siteid and t.matter_id=l.matter_id and t.matter_type=l.matter_type and l.user_last_op='Y' and t.userid='$oOperator->id' and t.userid=l.operator  and (l.operation<>'D' and l.operation<>'Recycle' and l.operation<>'Quit')",
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
		if (false === ($oOperator = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model()->delete(
			'xxt_account_topmatter',
			['siteid' => $site, 'userid' => $oOperator->id, 'matter_id' => $id, 'matter_type' => $type]
		);

		return new \ResponseData($rst);
	}
}