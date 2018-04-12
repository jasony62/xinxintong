<?php
namespace pl\fe\site\sns\wxa;

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/base.php';
/**
 * 微信小程序码
 */
class wxacode extends \pl\fe\base {
	/**
	 * 创建
	 *
	 * @param string $matter
	 *
	 */
	public function createByMatter_action($matter) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/**
		 * 素材
		 */
		$oMatter = new \stdClass;
		list($oMatter->type, $oMatter->id) = explode(',', $matter);
		$modelMat = $this->model('matter\\' . $oMatter->type);
		$oMatter = $modelMat->byId($oMatter->id, ['cascaded' => 'N']);
		if (false === $oMatter) {
			return new \ObjectNotFoundError('（1）访问的对象不存在');
		}
		/**
		 * 小程序配置
		 */
		$modelWxa = $this->model('sns\wxa');
		$oWxaConfig = $modelWxa->bySite('platform');
		if (false === $oWxaConfig) {
			return new \ObjectNotFoundError('（2）访问的对象不存在');
		}
		/**
		 * 给素材生成唯一的scene
		 */
		$sceneId = $oMatter->type . '_' . $oMatter->id;

		$rst = $this->model('sns\wxa\proxy', $oWxaConfig)->wxacodeCreate($sceneId, 'pages/matter/index');
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		$wxacode = $rst[1];

		$filename = date('Ym') . '/' . $oMatter->type . '/' . $oMatter->id . '.png';
		$fs = $this->model('fs/local', $oMatter->siteid, 'wxacode');
		$fs->write($filename, $wxacode);
		$url = $fs->getPath($filename);
		$modelMat->modify($oUser, $oMatter, (object) ['wxacode_url' => $url]);

		return new \ResponseData($url);
	}
}