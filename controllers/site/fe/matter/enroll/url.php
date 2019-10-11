<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';

/**
 * 记录活动上传链接题
 */
class url extends base {
	/**
	 * 获得指定url的摘要
	 */
	public function getUrl_action() {
		$oSummary = new \stdClass;

		$oPosted = $this->getPostJson();
		if (empty($oPosted->url)) {
			return new \ParameterError('没有指定有效参数');
		}
		$targetUrl = $oPosted->url;
		if (false === ($aTargetUrl = parse_url($targetUrl))) {
			return new \ParameterError('指定的URL不合规无法解析');
		}
		if (empty($aTargetUrl['host'])) {
			return new \ParameterError('指定的URL不合规无法解析');
		}
		$hdrs = [
			'http' => ['header' => 
				"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
			],
		];
		$context = stream_context_create($hdrs);
		$html = $this->_iconvConvert(file_get_contents($oPosted->url, 0, $context));

		/* 获得页面的标题 */
		if (preg_match('/<title.*?>(.*?)<\/title>/is', $html, $aTitle)) {
			if (count($aTitle) === 2) {
				$title = trim($aTitle[1]);
				$oSummary->title = $title;
			}
		}
		/* 获得页面的描述 */
		if (preg_match('/<meta[^<]*?name="description".*?content="([^"]*?)">/is', $html, $aDescription)) {
			if (count($aTitle) === 2) {
				$description = trim($aDescription[1]);
				$oSummary->description = $aDescription[1];
			}
		}

		$oSummary->url = $targetUrl;

		return new \ResponseData($oSummary);
	}
	/**
	 *
	 */
	private function _iconvConvert($str) {
		$encode = mb_detect_encoding($str, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));

		if ($encode && $encode != 'UTF-8') {
			$str = iconv($encode, 'UTF-8//IGNORE', $str);
		}

		return $str;
	}
}