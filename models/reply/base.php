<?php
namespace reply;
/**
 *
 */
abstract class Reply {
	/**
	 *
	 */
	protected $call;
	/**
	 *
	 */
	public function __construct($call) {
		$this->call = $call;
	}
	/**
	 *
	 */
	protected function header() {
		$r = '<ToUserName><![CDATA[' . $this->call['from_user'] . ']]></ToUserName>';
		$r .= '<FromUserName><![CDATA[' . $this->call['to_user'] . ']]></FromUserName>';
		$r .= '<CreateTime>' . round(microtime(true)) . '</CreateTime>';
		return $r;
	}
	/**
	 * 文本回复，直接显示回复的文本
	 */
	protected function textResponse($content) {
		$r = '<xml>';
		$r .= $this->header();
		$r .= '<MsgType><![CDATA[text]]></MsgType>';
		$r .= '<Content><![CDATA[' . $this->text_reply($content) . ']]></Content>';
		$r .= '</xml> ';
		if ($this->call['src'] === 'qy') {
			$r = $this->encrypt($r);
		}
		return $r;
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
		$r .= $this->article_reply($matters);
		$r .= '</Articles>';
		$r .= '</xml>';
		if ($this->call['src'] === 'qy') {
			$r = $this->encrypt($r);
		}
		return $r;
	}
	/**
	 * 组装文本回复消息
	 *
	 * 文本消息中允许动态参数，需要对这些参数进行转换
	 * 内置参数：
	 * {{mpid}}
	 * {{openid}}
	 * {{src}}
	 */
	private function text_reply($content) {
		$content = str_replace(
			array(
				'{{mpid}}',
				'{{openid}}',
				'{{src}}',
			),
			array(
				$this->call['mpid'],
				$this->call['from_user'],
				$this->call['src'],
			),
			$content
		);
		return $content;
	}
	/**
	 * 拼装图文回复消息
	 */
	private function article_reply($matters) {
		$r = '';
		foreach ($matters as $matter) {
			$matter->mpid = $this->call['mpid'];
			$runningMpid = $this->call['mpid'];
			$url = \TMS_APP::model('matter\\' . $matter->type)->getEntryUrl($runningMpid, $matter->id, $this->call['from_user'], $this->call);
			$r .= '<item>';
			$r .= '<Title><![CDATA[' . $matter->title . ']]></Title>';
			$r .= '<Description><![CDATA[' . $matter->summary . ']]></Description>';
			if (!empty($matter->pic) && stripos($matter->pic, 'http') === false) {
				$r .= '<PicUrl><![CDATA[' . 'http://' . $_SERVER['HTTP_HOST'] . $matter->pic . ']]></PicUrl>';
			} else {
				$r .= '<PicUrl><![CDATA[' . $matter->pic . ']]></PicUrl>';
			}

			$r .= '<Url><![CDATA[' . $url . ']]></Url>';
			$r .= '</item>';
		}
		return $r;
	}
	/**
	 * 企业号信息加密处理
	 */
	protected function encrypt($msg) {
		$sEncryptMsg = ""; //xml格式的密文
		$timestamp = time();
		$nonce = uniqid();
		$app = \TMS_APP::model('mp\mpaccount')->byId($this->call['mpid']);
		$wxcpt = new \WXBizMsgCrypt($app->token, $app->qy_encodingaeskey, $app->qy_corpid);
		$errCode = $wxcpt->EncryptMsg($msg, $timestamp, $nonce, $sEncryptMsg);
		if ($errCode != 0) {
			\TMS_APP::model('log')->log($this->call['mpid'], $this->content, $errCode);
			exit;
		}
		return $sEncryptMsg;
	}
	/**
	 *
	 */
	abstract public function exec();
}
/**
 * 图文回复
 */
abstract class MultiArticleReply extends Reply {
	/**
	 * articles belong to (article/news/channel)
	 */
	protected $set_id;
	/**
	 *
	 */
	public function __construct($call, $set_id) {
		parent::__construct($call);
		$this->set_id = $set_id;
	}
	/**
	 * 生成回复消息
	 */
	public function exec() {
		$matters = $this->loadMatters();
		$r = $this->cardResponse($matters);
		die($r);
	}
	/**
	 * 回复中包含的图文信息
	 */
	abstract protected function loadMatters();
}