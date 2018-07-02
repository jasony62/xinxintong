<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动主控制器
 */
class page extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action($id) {
		$access = $this->accessControlUser('enroll', $id);
		if ($access[0] === false) {
			die($access[1]);
		}

		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 添加活动页面
	 *
	 * @param string $site
	 * @param string $app
	 */
	public function add_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$options = $this->getPostJson();

		$newPage = $this->model('matter\enroll\page')->add($user, $site, $app, $options);

		return new \ResponseData($newPage);
	}
	/**
	 * 更新活动的页面的属性信息
	 *
	 * @param string $app 活动的id
	 * @param string $page 页面的id
	 * @param $cname 页面对应code page id
	 *
	 */
	public function update_action($app, $page, $cname) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();

		$modelPage = $this->model('matter\enroll\page');
		$oPage = $modelPage->byId($app, $page);
		if ($oPage === false) {
			return new \ResponseError('指定的页面不存在');
		}
		/* 更新页面内容 */
		if (isset($oPosted->html)) {
			$data = [
				'html' => urldecode($oPosted->html),
			];
			$modelCode = $this->model('code\page');
			$oCode = $modelCode->lastByName($oPage->siteid, $cname);
			$rst = $modelCode->modify($oCode->id, $data);
			unset($oPosted->html);
		}
		/* 更新了除内容外，页面的其他属性 */
		if (count((array) $oPosted)) {
			$aUpdated = [];
			foreach ($oPosted as $prop => $val) {
				switch ($prop) {
				case 'dataSchemas':
					$aUpdated['data_schemas'] = $modelPage->escape($modelPage->toJson($val));
					break;
				case 'actSchemas':
					$aUpdated['act_schemas'] = $modelPage->escape($modelPage->toJson($val));
					break;
				case 'title':
					$aUpdated[$prop] = $modelPage->escape($val);
					break;
				default:
					$aUpdated[$prop] = $val;
				}
			}
			$rst = $modelPage->update(
				'xxt_enroll_page',
				$aUpdated,
				["id" => $oPage->id]
			);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 删除活动的页面
	 *
	 * @param string $site
	 * @param string $aid
	 * @param string $pid
	 */
	public function remove_action($site, $app, $pid, $cname) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$page = $this->model('matter\enroll\page')->byId($app, $pid);

		$modelCode = $this->model('code\page');
		$modelCode->removeByName($site, $cname);

		$rst = $modelCode->delete('xxt_enroll_page', "aid='$app' and id=$pid");

		return new \ResponseData($rst);
	}
}