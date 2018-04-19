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
				$aTitle[1] = $this->iconvConvert($aTitle[1]);
				$oSummary->title = $aTitle[1];
			}
		}
		/* 获得页面的描述 */
		if (preg_match('/<meta[^<]*?name="description".*?content="([^"]*?)">/i', $html, $aDescription)) {
			if (count($aTitle) === 2) {
				$aDescription[1] = $this->iconvConvert($aDescription[1]);
				$oSummary->description = $aDescription[1];
			}
		}

		$oSummary->url = $targetUrl;

		return new \ResponseData($oSummary);
	}
	/**
	 *
	 */
	private function iconvConvert($str) {
		$encode = mb_detect_encoding($str, array('ASCII','UTF-8','GB2312','GBK','BIG5'));

		if ($encode && $encode != 'UTF-8') {
			$str = iconv($encode, 'UTF-8//IGNORE', $str);
		}

		return $str;
	}
}