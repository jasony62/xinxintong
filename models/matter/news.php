<?php
namespace matter;

require_once dirname(__FILE__) . '/article_base.php';
/**
 *
 */
class news_model extends article_base {
	/**
	 * 记录日志时需要的列
	 */
	const LOG_FIELDS = 'siteid,id,title';
	/**
	 *
	 */
	protected function table() {
		return 'xxt_news';
	}
	/**
	 * 返回多图文包含的素材
	 *
	 * $param int $news_id
	 *
	 * return array(article object)
	 */
	public function &getMatters($news_id) {
		$matters = [];
		/**
		 * 单图文
		 */
		$q = [
			"a.id,a.siteid,a.title,a.pic,a.summary,a.url,a.create_at,nm.seq,'article' type",
			'xxt_article a,xxt_news_matter nm',
			"a.state=1 and a.approved='Y' and nm.matter_type='article' and nm.news_id=$news_id and nm.matter_id=a.id",
		];
		$q2 = ['o' => 'nm.seq'];
		if ($articles = $this->query_objs_ss($q, $q2)) {
			foreach ($articles as $a) {
				$matters[(int) $a->seq] = $a;
			}
		}
		/**
		 * 多图文
		 */
		$q = [
			"n.id,n.siteid,n.title,n.pic,n.summary,n.create_at,nm.seq,'news' type",
			'xxt_news n,xxt_news_matter nm',
			"n.state=1 and nm.matter_type='news' and nm.news_id=$news_id and nm.matter_id=n.id",
		];
		$q2 = ['o' => 'nm.seq'];
		if ($news = $this->query_objs_ss($q, $q2)) {
			foreach ($news as $n) {
				$matters[(int) $n->seq] = $n;
			}
		}
		/**
		 * 频道
		 */
		$q = [
			"c.id,c.siteid,c.title,c.pic,c.summary,c.create_at,nm.seq,'channel' type",
			'xxt_channel c,xxt_news_matter nm',
			"c.state=1 and nm.matter_type='channel' and nm.news_id=$news_id and nm.matter_id=c.id",
		];
		$q2 = ['o' => 'nm.seq'];
		if ($channels = $this->query_objs_ss($q, $q2)) {
			foreach ($channels as $c) {
				$matters[(int) $c->seq] = $c;
			}
		}
		/**
		 * 链接
		 */
		$q = [
			"l.id,l.siteid,l.title,l.pic,l.summary,l.url,l.urlsrc,l.create_at,nm.seq,'link' type,method,open_directly",
			'xxt_link l,xxt_news_matter nm',
			"l.state=1 and nm.matter_type='link' and nm.news_id=$news_id and nm.matter_id=l.id",
		];
		$q2 = ['o' => 'nm.seq'];
		if ($links = $this->query_objs_ss($q, $q2)) {
			foreach ($links as $l) {
				$matters[(int) $l->seq] = $l;
			}
		}
		/**
		 * 登记活动
		 */
		$q = [
			"e.id,e.siteid,e.title,e.pic,e.summary,e.create_at,nm.seq,'enroll' type",
			'xxt_enroll e,xxt_news_matter nm',
			"e.state<>0 and nm.matter_type='enroll' and nm.news_id=$news_id and nm.matter_id=e.id",
		];
		$q2 = ['o' => 'nm.seq'];
		if ($apps = $this->query_objs_ss($q, $q2)) {
			foreach ($apps as &$a) {
				$matters[(int) $a->seq] = $a;
			}
		}
		/**
		 * 抽奖活动
		 */
		$q = [
			"l.id,l.siteid,l.title,l.pic,l.summary,l.create_at,nm.seq,'lottery' type",
			'xxt_lottery l,xxt_news_matter nm',
			"nm.matter_type='lottery' and nm.news_id=$news_id and nm.matter_id=l.id",
		];
		$q2 = ['o' => 'nm.seq'];
		if ($lots = $this->query_objs_ss($q, $q2)) {
			foreach ($lots as &$l) {
				$matters[(int) $l->seq] = $l;
			}

		}
		ksort($matters);

		$matters2 = [];
		foreach ($matters as $m) {
			$matters2[] = $m;
		}

		return $matters2;
	}
	/**
	 * 返回多图文包含的单图文
	 *
	 * $param int $news_id
	 *
	 * return array(article object)
	 */
	public function &getArticles($news_id) {
		$articles = [];
		/**
		 * 单图文
		 */
		$q = [
			"a.id,a.siteid,a.title,a.pic,a.summary,a.body,a.url,a.create_at,nm.seq,'article' type",
			'xxt_article a,xxt_news_matter nm',
			"nm.matter_type='article' and nm.news_id=$news_id and nm.matter_id=a.id",
		];
		$q2['o'] = 'nm.seq';

		$articles = $this->query_objs_ss($q, $q2);

		return $articles;
	}
	/**
	 *
	 */
	public function &byMatter($id, $type) {
		$q = [
			'*',
			'xxt_news n',
			"exists(select 1 from xxt_news_matter nm where nm.news_id=n.id and nm.matter_id='$id' and nm.matter_type='$type')",
		];
		$news = $this->query_objs_ss($q);

		return $news;
	}
	/**
	 * 删除文稿
	 */
	public function removeMatter($id, $matterId, $matterType) {
		$q = [
			'seq',
			'xxt_news_matter',
			"news_id='$id' and matter_id='$matterId' and matter_type='$matterType'",
		];
		$seq = $this->query_val_ss($q);

		$rst = $this->delete('xxt_news_matter', "news_id='$id' and matter_id='$matterId' and matter_type='$matterType'");

		$rst && $this->update("update xxt_news_matter set seq=seq-1 where news_id='$id' and seq>$seq");

		return $rst;
	}
}