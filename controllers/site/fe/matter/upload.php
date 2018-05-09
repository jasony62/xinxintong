<?php
namespace site\fe\matter;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户上传内容
 */
class upload extends \site\fe\base {
	/**
	 * 上传图片
	 */
	public function image_action() {
		$oUser = $this->who;

		$oImage = $this->getPostJson();

		$userDir = $oUser->uid . '/' . date('Ym');

		$fsuser = $this->model('fs/user', $userDir);
		$rst = $fsuser->storeImg($oImage);
		if (false === $rst[0]) {
			return $rst;
		}
		$url = $rst[1];
		if (strpos($url, 'http') === false) {
			if (strpos($url, '/') !== 0) {
				$url = '/' . $url;
			}
		}
		$oImage = (object) ['url' => $url];

		return new \ResponseData($oImage);
	}
}