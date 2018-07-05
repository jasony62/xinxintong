<?php
namespace pl\fe\matter\signin;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记活动主控制器
 */
class page extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/signin/frame');
		exit;
	}
	/**
	 * 添加活动页面
	 *
	 * $aid 获动的id
	 */
	public function add_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$options = $this->getPostJson();

		$newPage = $this->model('matter\signin\page')->add($user, $site, $app, $options);

		return new \ResponseData($newPage);
	}
	/**
	 * 更新活动的页面的属性信息
	 *
	 * $aid 活动的id
	 * $page 页面的id，如果id==0，是固定页面
	 * $cid 页面对应code page id
	 */
	public function update_action($app, $page, $cname) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\signin');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state', 'cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelPage = $this->model('matter\signin\page');
		$oPage = $modelPage->byId($oApp->id, $page);
		if ($oPage === false) {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();

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
				'xxt_signin_page',
				$aUpdated,
				["id" => $oPage->id]
			);
		}

		return new \ResponseData($rst);
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

		$page = $this->model('matter\signin\page')->byId($app, $pid);

		$modelCode = $this->model('code\page');
		$modelCode->removeByName($site, $cname);

		$rst = $this->model()->delete('xxt_signin_page', "aid='$app' and id=$pid");

		return new \ResponseData($rst);
	}
}