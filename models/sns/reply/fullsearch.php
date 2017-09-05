<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 根据关键字检索单图文
 * todo 如果搜索不到是否应该给出提示呢？
 */
class fullsearch_model extends MultiArticleReply {

	private $keyword;

	public function __construct($call, $keyword) {
		parent::__construct($call, null);
		$this->keyword = $keyword;
		$this->site = $this->call['siteid'];
		$this->num = \TMS_APP::M('matter\article')->fullsearch_num($this->call['siteid'], $keyword);
	}

	protected function loadMatters() {
		$siteId = $this->call['siteid'];
		$model = \TMS_APP::model('matter\article');
		$page = 1;
		$limit = 5;
		$matters = $model->fullsearch_its($siteId, $this->keyword, $page, $limit);
		foreach ($matters as &$matter) {
			$matter->entryURL = $model->getEntryUrl($this->call['siteid'], $matter->id);
		}
		return $matters;
	}

	/**
	 * 生成回复消息 必须重写 因为可能找不到文章需要用文字信息提示。
	 */
	public function exec() {
		$matters = $this->loadMatters();
		if (empty($matters)) {
			$r = $this->textResponse("找不到包含【" . $this->keyword . "】的文章，请尝试更换关键词继续搜索。");
		} else {
			$r = $this->cardResponse($matters);
		}
		die($r);
	}
	/**
	 * 企业号信息加密处理
	 */
	protected function encrypt($msg) {
		$siteId = $this->call['siteid'];
		$sEncryptMsg = ""; //xml格式的密文
		$timestamp = time();
		$nonce = uniqid();
		$qyConfig = \TMS_APP::model('sns\qy')->bySite($siteId);
		$wxcpt = new \WXBizMsgCrypt($qyConfig->token, $qyConfig->encodingaeskey, $qyConfig->corpid);
		$errCode = $wxcpt->EncryptMsg($msg, $timestamp, $nonce, $sEncryptMsg);
		if ($errCode != 0) {
			\TMS_APP::model('log')->log($siteId, $this->content, $errCode);
			exit;
		}
		return $sEncryptMsg;
	}
	/**
	 * 卡片回复，显示一个包含内容链接的卡片
	 */
	protected function cardResponse($matters) {
		if (!is_array($matters)) {
			$matters = array($matters);
		}

		$r = '<xml>';
		$r .= $this->header();
		$r .= '<MsgType><![CDATA[news]]></MsgType>';
		$r .= '<ArticleCount>' . count($matters) . '</ArticleCount>';
		$r .= '<Articles>';
		$r .= $this->_articleReply($matters);
		$r .= '</Articles>';
		$r .= '</xml>';
		if ($this->call['src'] === 'qy') {
			$r = $this->encrypt($r);
		}
		return $r;
	}
	/**
	 * 拼装图文回复消息 必须重写 因为超过5条 要添加一个搜索按钮
	 */
	private function _articleReply($matters) {
		$r = '';
		foreach ($matters as $matter) {
			$r .= '<item>';
			$r .= '<Title><![CDATA[' . $matter->title . ']]></Title>';
			$r .= '<Description><![CDATA[' . $matter->summary . ']]></Description>';
			if (!empty($matter->pic) && stripos($matter->pic, 'http') === false) {
				$r .= '<PicUrl><![CDATA[' . 'http://' . APP_HTTP_HOST . $matter->pic . ']]></PicUrl>';
			} else {
				$r .= '<PicUrl><![CDATA[' . $matter->pic . ']]></PicUrl>';
			}

			$r .= '<Url><![CDATA[' . $matter->entryURL . ']]></Url>';
			$r .= '</item>';
		}

		if ($this->num > 5) {
			$r .= '<item>';
			$r .= '<Title><![CDATA[查看更多]]></Title>';
			$r .= '<Description><![CDATA[]]></Description>';
			$r .= '<PicUrl><![CDATA[http://developer.189.cn/kcfinder/upload/9dc76342bbd2d4444748416b3ede427d/%E5%9B%BE%E7%89%87/%E5%85%B6%E4%BB%96/%E6%90%9C%E7%B4%A2%E5%9B%BE%E6%A0%87.jpg]]></PicUrl>';
			$r .= '<Url><![CDATA[http://' . APP_HTTP_HOST . '/rest/site/fe/matter/article/search?site=' . $this->site . '&keyword=' . $this->keyword . ']]></Url>';
			$r .= '</item>';
		}
		return $r;
	}
}