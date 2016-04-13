<?php
namespace matter;

require_once dirname(__FILE__) . '/base.php';
/**
 * 图文消息基类
 */
abstract class article_base extends base_model {
	/**
	 * 返回进行推送的客服消息格式
	 *
	 * $runningMpid
	 * $id
	 *
	 */
	public function &forCustomPush($runningMpid, $id) {
		$matters = $this->getMatters($id);
		$ma = array();
		foreach ($matters as $m) {
			if (!empty($m->pic) && stripos($m->pic, 'http') === false) {
				$pic = 'http://' . $_SERVER['HTTP_HOST'] . $m->pic;
			} else {
				$pic = $m->pic;
			}
			$ma[] = array(
				'title' => $m->title,
				'description' => $m->summary,
				'url' => \TMS_APP::model('matter\\' . $m->type)->getEntryUrl($runningMpid, $m->id),
				'picurl' => $pic,
			);
		}

		$msg = array(
			'msgtype' => 'news',
			'news' => array(
				'articles' => $ma,
			),
		);

		return $msg;
	}
	/**
	 * 返回进行推送的群发消息格式
	 *
	 * 群发的图文消息要上传的微信的服务器上，内容是必填项，因此只能发送图文消息
	 *
	 * 微信的群发消息不需要进行urlencode
	 */
	public function &forWxGroupPush($runningMpid, $id) {
		$ma = array();
		$articles = $this->getArticles($id);
		foreach ($articles as $a) {
			if (empty($a->title) || empty($a->pic) || empty($a->body)) {
				die('文章的标题、头图或者正文为空，不能向微信用户群发！');
			}

			if (!empty($a->pic) && stripos($a->pic, 'http') === false) {
				$pic = 'http://' . $_SERVER['HTTP_HOST'] . $a->pic;
			} else {
				$pic = $a->pic;
			}
			$ma[] = array(
				'title' => $a->title,
				'author' => empty($a->author) ? '' : $a->author,
				'description' => $a->summary,
				'url' => empty($a->url) ? '' : $a->url,
				'picurl' => $pic,
				'body' => $a->body,
			);
		}
		$msg = array(
			'msgtype' => 'news',
			'news' => array(
				'articles' => $ma,
			),
		);

		return $msg;
	}
	/**
	 *
	 */
	public function getEntryUrl($siteId, $id) {
		$url = "http://" . $_SERVER['HTTP_HOST'];
		$url .= "/rest/site/fe/matter";
		$url .= "?site={$siteId}&id={$id}&type=" . $this->getTypeName();

		return $url;
	}
}