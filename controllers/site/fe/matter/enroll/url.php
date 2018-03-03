<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';

/**
 * 登记活动上传链接题
 */
class url extends base {
	/**
	 * 获得指定url的摘要
	 */
	public function index_action() {
		$oSummary = new \stdClass;

		$oPosted = $this->getPostJson();
		$targetUrl = $oPosted->url;

		if (false === ($aTargetUrl = parse_url($targetUrl))) {
			return new \ParameterError('指定的URL不合规无法解析');
		}
		if (empty($aTargetUrl['host'])) {
			return new \ParameterError('指定的URL不合规无法解析');
		}
		$html = file_get_contents($oPosted->url);

		/* 获得页面的标题 */
		if (preg_match('/<title.*?>(.*?)<\/title>/i', $html, $aTitle)) {
			if (count($aTitle) === 2) {
				$oSummary->title = $aTitle[1];
			}
		}
		/* 获得页面的描述 */
		if (preg_match('/<meta.*?name="description".*?content="(.*)".*?>/i', $html, $aDescription)) {
			if (count($aTitle) === 2) {
				$oSummary->description = $aDescription[1];
			}
		}

		$oSummary->url = $targetUrl;

		return new \ResponseData($oSummary);
	}
}