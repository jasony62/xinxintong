<?php
namespace site\fe;

require_once dirname(__FILE__) . '/base.php';
/**
 * 站点首页
 */
class main extends base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		$site = $this->model('site')->byId($this->siteId);
		\TPL::assign('title', $site->name);
		\TPL::output('/site/fe/main');
		exit;
	}
	/**
	 *
	 */
	public function get_action() {
		$site = $this->model('site')->byId($this->siteId, ['fields' => 'id,name,summary,heading_pic,creater,creater_name']);

		return new \ResponseData($site);
	}
	/**
	 * 站点首页页面定义
	 */
	public function pageGet_action() {
		$site = $this->model('site')->byId($this->siteId);
		$page = $this->model('code\page')->byId($site->home_page_id);

		$param = array(
			'page' => $page,
		);

		return new \ResponseData($param);
	}
	/**
	 * 进入引导关注页
	 */
	public function follow_action() {
		\TPL::output('/site/fe/follow');
		exit;
	}
	/**
	 *
	 * 要求关注页面定义
	 *
	 * @param string $siteId
	 * @param string $snsName
	 * @param string $matter
	 *
	 */
	public function followPageGet_action($site, $sns, $matter = null) {
		$siteId = $site;
		$modelSns = $this->model('sns\\' . $sns);
		/* 公众号配置信息 */
		$snsConfig = $modelSns->bySite($siteId, ['fields' => 'joined,qrcode,follow_page_id,follow_page_name']);
		if ($snsConfig === false || $snsConfig->joined === 'N') {
			$siteId = 'platform';
			$snsConfig = $modelSns->bySite('platform', ['fields' => 'joined,qrcode,follow_page_id,follow_page_name']);
		}
		if (empty($snsConfig->follow_page_name)) {
			$page = new \stdClass;
			if ($siteId !== 'platform') {
				$site = $this->model('site')->byId($siteId);
				$page->html = '请关注公众号：' . $site->name;
			}
		} else {
			$page = $this->model('code\page')->lastPublishedByName($siteId, $snsConfig->follow_page_name);
		}
		$param = [
			'page' => $page,
			'snsConfig' => $snsConfig,
		];

		/* 访问素材信息 */
		if (!empty($matter)) {
			$matter = explode(',', $matter);
			if (count($matter) === 2) {
				$modelQrcode = $this->model('sns\\' . $sns . '\\call\qrcode');
				$qrcodes = $modelQrcode->byMatter($matter[0], $matter[1]);
				if (count($qrcodes) === 1) {
					$param['matterQrcode'] = $qrcodes[0];
				}
			}
		}

		return new \ResponseData($param);
	}
	/**
	 * 获得站点自定义用户定义
	 *
	 */
	public function memberSchemalist_action() {
		$modelSchema = $this->model('site\user\memberschema');

		$schemas = $modelSchema->bySite($this->siteId, 'Y');

		return new \ResponseData($schemas);
	}
}