<?php
namespace pl\fe\template;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 订单管理控制器
 */
class order extends \pl\fe\base {
	/**
	 * 
	 */
	public function index_action($site){
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		
		\TPL::output('/pl/fe/site/template/enroll/frame');
		exit;
	}

	public function listPurchaser_action($site, $tid){
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
		$q2['o'] = "order by purchase_at desc";

		$template->users = $modelTmp->query_objs_ss($q, $q2);


		return new \ResponseData($template);
	}
}