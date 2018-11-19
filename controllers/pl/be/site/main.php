<?php
namespace pl\be\site;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台站点
 */
class main extends \pl\be\base {
	/**
	 * 团队列表
	 */
	public function list_action() {
		$modelSite = $this->model('site');

		$oPosted = $this->getPostJson();
		if (empty($oPosted->name)) {
			return new \ResponseError('需要指定团队查询参数');
		}
		$q = [
			'id,name',
			'xxt_site',
			['name' => (object) ['op' => 'like', 'pat' => '%' . $oPosted->name . '%']],
		];
		$sites = $modelSite->query_objs_ss($q);

		$oResult = new \stdClass;
		$oResult->sites = $sites;

		return new \ResponseData($oResult);
	}
}