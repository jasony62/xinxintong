<?php
namespace pl\fe\template;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 登记活动模板管理控制器
 */
class enroll extends \pl\fe\base {
	/**
	 * 
	 */
	public function index_action($site){
		\TPL::output('/pl/fe/site/template/enroll/frame');
		exit;
	}
	/**
	 * 获得模板列表
	 *
	 * @param string $matterType
	 * @param int $page
	 * @param int $size
	 *
	 */
	public function list_action($site, $pub, $matterType = null, $scenario = null, $scope = null, $page = 1, $size = 20) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();

		$q = [
			'*',
			"xxt_template",
			"siteid = '".$model->escape($site)."' and state = 1",
		];
		if($pub === 'N'){
			$q[2] .=" and pub_version = ''";
		}else{
			$q[2] .=" and pub_version <> ''";
		}
		if(!empty($matterType)){
			$matterType = $model->escape($matterType);
			$q[2] .=" and matter_type = '{$matterType}'";
		}
		if (!empty($scenario)) {
			$scenario = $model->escape($scenario);
			$q[2] .=" and scenario = '{$scenario}'";
		}
		if(!empty($scope)){
			if($scope === 'P'){
				$q[2] .=" and visible_scope = 'P'";
				$q2['o'] = 'put_at desc';
			}else{
				$q[2] .=" and visible_scope = 'S'";
				$q2['o'] = 'put_at desc';
			}
		}

		$q2 = [
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];

		$orders = $model->query_objs_ss($q, $q2);
		$q[0] = "count(*)";
		$total = $model->query_val_ss($q);

		return new \ResponseData(['templates' => $orders, 'total' => $total]);
	}
	/**
	 * 更新活动的页面的属性信息
	 *
	 * string $app 版本的id
	 * $page 页面的id
	 * $cname 页面对应code page id
	 */
	public function updatePage_action($site, $vid, $pageId, $cname) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$version = $this->model('matter\template\enroll')->checkVersion($site, $vid);
		if($version[0]){
			return new \ResponseError('当前版本已发布，不可更改');
		}

		$nv = $this->getPostJson();
		$vid = 'template:'.$vid;
		$modelPage = $this->model('matter\enroll\page');
		$page = $modelPage->byId($vid, $pageId);
		if ($page === false) {
			return new \ResponseError('指定的页面不存在');
		}
		/* 更新页面内容 */
		if (isset($nv->html)) {
			$data = [
				'html' => urldecode($nv->html),
			];
			$modelCode = $this->model('code\page');
			$code = $modelCode->lastByName($site, $cname);
			$rst = $modelCode->modify($code->id, $data);
			unset($nv->html);
		}
		/* 更新了除内容外，页面的其他属性 */
		if (count(array_keys(get_object_vars($nv)))) {
			if (isset($nv->data_schemas)) {
				$nv->data_schemas = $modelPage->escape($modelPage->toJson($nv->data_schemas));
			}
			if (isset($nv->act_schemas)) {
				$nv->act_schemas = $modelPage->escape($modelPage->toJson($nv->act_schemas));
			}
			if (isset($nv->user_schemas)) {
				$nv->user_schemas = $modelPage->escape($modelPage->toJson($nv->user_schemas));
			}
			$rst = $modelPage->update(
				'xxt_enroll_page',
				$nv,
				["id" => $page->id]
			);
		}

		// 记录操作日志
		$matter = $this->model('matter\template')->byId($tid, $vid, ['fields'=>'id,title,summary,pic','cascaded'=>'N']);
		$this->model('matter\log')->matterOp($site, $loginUser, $matter, 'U');
		return new \ResponseData($rst);
	}
}