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
		$site = $this->escape($site);
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
		$matterSiteId = $snsSiteId = $this->escape($site);
		$modelSns = $this->model('sns\\' . $sns);
		/* 公众号配置信息 */
		$snsConfig = $modelSns->bySite($snsSiteId, ['fields' => 'siteid,joined,qrcode,follow_page_id,follow_page_name']);
		if ($snsConfig === false || ($snsConfig->joined === 'N' && $sns === 'wx')) {
			$snsSiteId = 'platform';
			$snsConfig = $modelSns->bySite('platform', ['fields' => 'siteid,joined,qrcode,follow_page_id,follow_page_name']);
		}

		$oSite = $this->model('site')->byId($snsSiteId, ['fields' => 'state,name,summary']);
		if (empty($snsConfig->follow_page_name)) {
			$oPage = new \stdClass;
			$oPage->html = '请关注公众号：' . $oSite->name;
		} else {
			$oPage = $this->model('code\page')->lastPublishedByName($snsSiteId, $snsConfig->follow_page_name);
		}
		$aParams = [
			'page' => $oPage,
			'snsConfig' => $snsConfig,
			'site' => $oSite,
			'user' => $this->who,
		];

		/* 根据场景二维码设置参数 */
		$fnSetMatterByQrcode = function ($oQrcode, &$aParams) {
			$oMatter = $this->_getMatterByQrcode($oQrcode);
			$aParams['matter'] = $oMatter;
			if ($oMatter->type === 'mschema') {
				$modelMs = $this->model('site\user\memberschema');
				$aParams['referer'] = $modelMs->getEntryUrl($oMatter->siteid, $oMatter->id);
			} else {
				$modelMat = $this->model('matter\\' . $oMatter->type);
				$aParams['referer'] = $modelMat->getEntryUrl($oMatter->siteid, $oMatter->id);
			}
		};

		/* 访问素材信息 */
		if (!empty($sceneid)) {
			$modelQrcode = $this->model('sns\\' . $sns . '\\call\qrcode');
			$oQrcode = $modelQrcode->bySceneId($snsSiteId, $sceneid);
			if ($oQrcode) {
				$aParams['matterQrcode'] = $oQrcode;
				$fnSetMatterByQrcode($oQrcode, $aParams);
			}
		} else if (!empty($matter)) {
			$matter = explode(',', $matter);
			if (count($matter) === 2) {
				list($type, $id) = $matter;
				/* 加入素材的场景二维码 */
				if (in_array($sns, ['wx', 'yx'])) {
					$modelQrcode = $this->model('sns\\' . $sns . '\\call\qrcode');
					$qrcodes = $modelQrcode->byMatter($type, $id);
					if (count($qrcodes) === 1) {
						$oQrcode = $qrcodes[0];
						$aParams['matterQrcode'] = $oQrcode;
						if ($oQrcode) {
							$fnSetMatterByQrcode($oQrcode, $aParams);
						}
					}
				}
				if (!isset($oQrcode)) {
					if ($type === 'mschema') {
						$modelMs = $this->model('site\user\memberschema');
						$aParams['matter'] = $modelMs->byId($id, ['fields' => 'id,title']);
						$aParams['referer'] = $modelMs->getEntryUrl($matterSiteId, $id);
					} else {
						$modelMat = $this->model('matter\\' . $type);
						$aParams['matter'] = $modelMat->byId($id, ['fields' => 'id,title,pic,summary', 'cascaded' => 'N']);
						$aParams['referer'] = $modelMat->getEntryUrl($matterSiteId, $id);
					}
				}
			}
		}

		return new \ResponseData($aParams);
	}
	/**
	 * 场景二维码对应的素材
	 */
	private function _getMatterByQrcode($oQrcode) {
		if (!empty($oQrcode->matter_type) && !empty($oQrcode->matter_id)) {
			if ($oQrcode->matter_type === 'mschema') {
				$modelMs = $this->model('site\user\memberschema');
				$oMschema = $modelMs->byId($oQrcode->matter_id);
				$oMatter = new \stdClass;
				$oMatter->id = $oMschema->id;
				$oMatter->siteid = $oMschema->siteid;
				$oMatter->title = $oMschema->title;
				$oMatter->type = 'mschema';
				$aParams['referer'] = $modelMs->getEntryUrl($oMschema->siteid, $oMschema->id);
			} else {
				$oMatter = $this->model('matter\\' . $oQrcode->matter_type)->byId($oQrcode->matter_id, ['fields' => 'id,siteid,state,title,summary,pic']);
			}
			return $oMatter;
		}

		return false;
	}
}