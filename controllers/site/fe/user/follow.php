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
	 * @param string $site 团队id
	 * @param string $sns 公众号类型
	 *
	 */
	public function index_action($site, $sns) {
		/* 如果用户已经绑定过公众号信息，清空后让用户重新绑定 */
		if (isset($this->who->sns->{$sns})) {
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
	 * @param string $matter 指定素材对应的场景二维码
	 * @param string $sceneid 指定场景二维码
	 *
	 */
	public function pageGet_action($site, $sns, $matter = null, $sceneid = null) {
		$siteId = $site;
		$modelSns = $this->model('sns\\' . $sns);
		/* 公众号配置信息 */
		$snsConfig = $modelSns->bySite($siteId, ['fields' => 'siteid,joined,qrcode,follow_page_id,follow_page_name']);
		if ($snsConfig === false || ($snsConfig->joined === 'N' && $sns === 'wx')) {
			$siteId = 'platform';
			$snsConfig = $modelSns->bySite('platform', ['fields' => 'siteid,joined,qrcode,follow_page_id,follow_page_name']);
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
			'user' => $this->who,
		];
		/* 访问素材信息 */
		if (!empty($sceneid)) {
			$modelQrcode = $this->model('sns\\' . $sns . '\\call\qrcode');
			$qrcode = $modelQrcode->bySceneId($site, $sceneid);
			$param['matterQrcode'] = $qrcode;
		} else if (!empty($matter)) {
			$matter = explode(',', $matter);
			if (count($matter) === 2) {
				/* 素材的url */
				switch ($matter[0]) {
				case 'mschema':
					$param['referer'] = $this->model('site\user\memberschema')->getEntryUrl($site, $matter[1]);
					break;
				case 'enroll':
				case 'signin':
					break;
				}
				/* 加入素材的场景二维码，企业号不支持 */
				if (in_array($sns, ['wx', 'yx'])) {
					$modelQrcode = $this->model('sns\\' . $sns . '\\call\qrcode');
					$qrcodes = $modelQrcode->byMatter($matter[0], $matter[1]);
					if (count($qrcodes) === 1) {
						$param['matterQrcode'] = $qrcodes[0];
					}
				}
			}
		}

		return new \ResponseData($param);
	}
}