<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户关注
 */
class follow extends \site\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 进入引导关注页
	 *
	 * @param string $site
	 * @param string $sns
	 *
	 */
	public function index_action($site, $sns) {
		if (isset($this->who->sns->{$sns})) {
			/* 如果用户已经绑定过公众号信息，清空后让用户重新绑定 */
			unset($this->who->sns->{$sns});
			$this->model('site\fe\way')->setCookieUser($site, $this->who);
		}
		\TPL::output('/site/fe/user/follow');
		exit;
	}
	/**
	 *
	 * 要求关注页面定义
	 *
	 * @param string $site
	 * @param string $sns
	 * @param string $matter
	 *
	 */
	public function pageGet_action($site, $sns, $matter = null) {
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
}