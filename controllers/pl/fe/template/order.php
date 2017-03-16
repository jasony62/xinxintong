<?php
namespace pl\fe\template;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 订单管理控制器
 */
class order extends \pl\fe\base {
	/**
	 * [listPurchaser_action 模板使用者列表]
	 * @param  [type] $site [description]
	 * @param  [type] $tid  [description]
	 * @return [type]       [description]
	 */
	public function listPurchaser_action($site, $tid, $page = 1, $size = 30){
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmp = $this->model('matter\template');
		if (false === ($template = $modelTmp->byId($tid, null, ['cascaded'=>'N'])) ) {
			return new \ResponseError('指定的模板不存在，请检查参数是否正确');
		}

		//获得版本所有使用者
		$options = [
			'template_id' => $tid,
			'purchase' => 'Y'
		];
		$q = [
			'*',
			'xxt_template_order',
			$options
		];
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		$q2['o'] = "order by purchase_at desc";
		$modelSite = $this->model('site');
		if($users = $modelSite->query_objs_ss($q, $q2) ){
			foreach ($users as $user) {
				$site = $modelSite->byId($user->siteid, ['fields' => 'name']);
				$user->site_name = $site->name;
			}
		}
		$q[0] = "count(*)";
		$total = (int) $modelSite->query_val_ss($q);

		$purchases = new \stdClass;
		$purchases->users = $users;
		$purchases->total = $total;
		return new \ResponseData($purchases);
	}
}