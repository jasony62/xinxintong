<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 记录活动主控制器
 */
class page extends main_base {
	/**
	 * 添加活动页面
	 *
	 * @param string $app
	 */
	public function add_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oOptions = $this->getPostJson();

		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['fields' => 'id,siteid,state', 'cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelPage = $this->model('matter\enroll\page');
		$oNewPage = $modelPage->add($oUser, $oApp->siteid, $oApp->id, $oOptions);

		return new \ResponseData($oNewPage);
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

		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state', 'cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelPage = $this->model('matter\enroll\page');
		$oPage = $modelPage->byId($oApp, $page);
		if ($oPage === false) {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson(false);

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
					$aUpdated[$prop] = $modelPage->escape($val);
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
	 * @param string $aid
	 * @param string $pid
	 * @param string $cname
	 */
	public function remove_action($app, $pid, $cname) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['fields' => 'id,state', 'cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$modelPage = $this->model('matter\enroll\page');
		$oPage = $modelPage->byId($oApp, $pid);
		if ($oPage === false) {
			return new \ObjectNotFoundError();
		}

		$modelCode = $this->model('code\page');
		$modelCode->removeByName($oPage->siteid, $cname);

		$rst = $modelPage->delete('xxt_enroll_page', ['aid' => $oApp->id, 'id' => $oPage->id]);

		return new \ResponseData($rst);
	}
}