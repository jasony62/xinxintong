<?php
namespace reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 根据关键字检索单图文
 * todo 如果搜索不到是否应该给出提示呢？
 */
class fullsearch_model extends MultiArticleReply {

	private $keyword;
	private $site;
	private $num;

	public function __construct($call, $keyword) {
		parent::__construct($call, null);
		$this->keyword = $keyword;
		$this->site = $this->call['siteid'];
		$num = \TMS_APP::model('matter\article2')->fullsearch_num($this->call['siteid'], $this->keyword);
		$this->num = $num;
	}

	protected function loadMatters() {
		$site = $this->call['siteid'];
		$page = 1;
		$limit = 5;
		$matters = \TMS_APP::model('matter\article2')->fullsearch_its($site, $this->keyword, $page, $limit);
		return $matters;
	}
	/**
	 * 生成回复消息，找不到文章的时候回复文本提示
	 */
	public function exec() {
		$matters = $this->loadMatters();
		if ($matters) {
			$r = $this->cardResponse($matters);
		} else {
			$r = $this->textResponse('找不到包含【' . $this->keyword . '】的文章，请更换关键词继续搜索。');
		}
		die($r);
	}

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
		$num = $this->num;
		if ($num > 5) {
			$r .= "<item>";
			$r .= "<Title><![CDATA[查看更多]]></Title>";
			$r .= "<Description><![CDATA[查看更多]]></Description>";
			$r .= "<PicUrl><![CDATA[http://xinxintong.oss-cn-hangzhou.aliyuncs.com/9dc76342bbd2d4444748416b3ede427d/%E5%9B%BE%E7%89%87/%E5%A4%B4%E5%9B%BE/%E6%90%9C%E7%B4%A2%E5%9B%BE%E6%A0%87.jpg]]></PicUrl>";
			$r .= "<Url><![CDATA[http://" . $_SERVER['HTTP_HOST'] . "/rest/site/fe/matter/article/search?site=" . $this->site . "&keyword=" . $this->keyword . "]]></Url>";
			$r .= "</item>";
		}
		$r .= '</Articles>';
		$r .= '</xml>';
		if ($this->call['src'] === 'qy') {
			$r = $this->encrypt($r);
		}
		return $r;
	}

	/**
	 * 拼装图文回复消息
	 */
	private function article_reply($matters) {
		$r = '';
		foreach ($matters as $matter) {
			$matter->siteid = $this->call['siteid'];
			$runningMpid = $this->call['siteid'];
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
}