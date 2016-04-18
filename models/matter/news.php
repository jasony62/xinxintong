<?php
namespace matter;

require_once dirname(__FILE__) . '/article_base.php';

class news_model extends article_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_news';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'news';
	}
	/**
	 * $mid member's 仅限认证用户
	 * $phase
	 */
	public function &byReviewer($mid, $phase, $fields = '*', $cascade = false) {
		$q = array(
			'n.*',
			'xxt_news n',
			"exists(select 1 from xxt_news_review_log l where n.id=l.news_id and l.mid='$mid' and l.phase='R')",
		);
		$q2 = array('o' => 'n.create_at desc');

		$news = $this->query_objs_ss($q, $q2);
		if (!empty($news) && $cascade) {
			foreach ($news as &$n) {
				$n->disposer = $this->disposer($n->id);
			}
		}

		return $news;
	}
	/**
	 * 返回多图文包含的素材
	 *
	 * $param int $news_id
	 *
	 * return array(article object)
	 */
	public function &getMatters($news_id) {
		$matters = array();
		/**
		 * 单图文
		 */
		$q = array(
			"a.id,a.siteid,a.title,a.pic,a.summary,a.url,a.create_at,nm.seq,'article' type,a.access_control,a.authapis",
			'xxt_article a,xxt_news_matter nm',
			"a.state=1 and a.approved='Y' and nm.matter_type='article' and nm.news_id=$news_id and nm.matter_id=a.id",
		);
		$q2 = array('o' => 'nm.seq');
		if ($articles = $this->query_objs_ss($q, $q2)) {
			foreach ($articles as $a) {
				$matters[(int) $a->seq] = $a;
			}
		}
		/**
		 * 多图文
		 */
		$q = array(
			"n.id,n.siteid,n.title,n.pic,n.summary,n.create_at,nm.seq,'news' type,n.access_control,n.authapis",
			'xxt_news n,xxt_news_matter nm',
			"n.state=1 and nm.matter_type='news' and nm.news_id=$news_id and nm.matter_id=n.id",
		);
		$q2 = array('o' => 'nm.seq');
		if ($news = $this->query_objs_ss($q, $q2)) {
			foreach ($news as $n) {
				$matters[(int) $n->seq] = $n;
			}
		}
		/**
		 * 频道
		 */
		$q = array(
			"c.id,c.siteid,c.title,c.pic,c.summary,c.create_at,nm.seq,'channel' type,c.access_control,c.authapis",
			'xxt_channel c,xxt_news_matter nm',
			"c.state=1 and nm.matter_type='channel' and nm.news_id=$news_id and nm.matter_id=c.id",
		);
		$q2 = array('o' => 'nm.seq');
		if ($channels = $this->query_objs_ss($q, $q2)) {
			foreach ($channels as $c) {
				$matters[(int) $c->seq] = $c;
			}
		}
		/**
		 * 链接
		 */
		$q = array(
			"l.id,l.siteid,l.title,l.pic,l.summary,l.url,l.urlsrc,l.create_at,nm.seq,'link' type,method,open_directly,l.access_control,l.authapis",
			'xxt_link l,xxt_news_matter nm',
			"l.state=1 and nm.matter_type='link' and nm.news_id=$news_id and nm.matter_id=l.id",
		);
		$q2 = array('o' => 'nm.seq');
		if ($links = $this->query_objs_ss($q, $q2)) {
			foreach ($links as $l) {
				$matters[(int) $l->seq] = $l;
			}
		}
		/**
		 * 登记活动
		 */
		$q = array(
			"e.id,e.siteid,e.title,e.pic,e.summary,e.create_at,nm.seq,'enroll' type,e.access_control,e.authapis",
			'xxt_enroll e,xxt_news_matter nm',
			"e.state<>0 and nm.matter_type='enroll' and nm.news_id=$news_id and nm.matter_id=e.id",
		);
		$q2 = array('o' => 'nm.seq');
		if ($apps = $this->query_objs_ss($q, $q2)) {
			foreach ($apps as &$a) {
				$matters[(int) $a->seq] = $a;
			}
		}
		/**
		 * 抽奖活动
		 */
		$q = array(
			"l.id,l.siteid,l.title,l.pic,l.summary,l.create_at,nm.seq,'lottery' type,l.access_control,l.authapis",
			'xxt_lottery l,xxt_news_matter nm',
			"nm.matter_type='lottery' and nm.news_id=$news_id and nm.matter_id=l.id",
		);
		$q2 = array('o' => 'nm.seq');
		if ($lots = $this->query_objs_ss($q, $q2)) {
			foreach ($lots as &$l) {
				$matters[(int) $l->seq] = $l;
			}

		}
		ksort($matters);

		$matters2 = array();
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
		$articles = array();
		/**
		 * 单图文
		 */
		$q = array(
			"e.id,e.siteid,e.title,e.pic,e.summary,e.body,e.url,e.create_at,nm.seq,'article' type",
			'xxt_article a,xxt_news_matter nm',
			"nm.matter_type='article' and nm.news_id=$news_id and nm.matter_id=e.id",
		);
		$q2['o'] = 'nm.seq';

		$articles = $this->query_objs_ss($q, $q2);

		return $articles;
	}
	/**
	 *
	 */
	public function &byMatter($id, $type) {
		$q = array(
			'*',
			'xxt_news n',
			"exists(select 1 from xxt_news_matter nm where nm.news_id=n.id and nm.matter_id='$id' and nm.matter_type='$type')",
		);
		$news = $this->query_objs_ss($q);

		return $news;
	}
	/**
	 * 删除文稿
	 */
	public function removeMatter($id, $matterId, $matterType) {
		$q = array(
			'seq',
			'xxt_news_matter',
			"news_id='$id' and matter_id='$matterId' and matter_type='$matterType'",
		);
		$seq = $this->query_val_ss($q);

		$rst = $this->delete('xxt_news_matter', "news_id='$id' and matter_id='$matterId' and matter_type='$matterType'");

		$rst && $this->update("update xxt_news_matter set seq=seq-1 where news_id='$id' and seq>$seq");

		return $rst;
	}
	/**
	 * 多图文审核记录
	 *
	 * $mpid
	 * $id news'id
	 * $mid member's id
	 * $phase
	 */
	public function forward($mpid, $id, $mid, $phase) {
		$q = array(
			'*',
			'xxt_news_review_log',
			"mpid='$mpid' and news_id='$id'",
		);
		$q2 = array(
			'o' => 'seq desc',
			'r' => array('o' => 0, 'l' => 1),
		);
		$last = $this->query_objs_ss($q, $q2);
		if (!empty($last)) {
			$last = $last[0];
			$this->update(
				'xxt_news_review_log',
				array('state' => 'F'),
				"id=$last->id"
			);
		}

		$seq = empty($last) ? 1 : $last->seq + 1;

		$newlog = array(
			'mpid' => $mpid,
			'news_id' => $id,
			'seq' => $seq,
			'mid' => $mid,
			'send_at' => time(),
			'state' => 'P',
			'phase' => $phase,
		);
		$newlog['id'] = $this->insert('xxt_news_review_log', $newlog, true);

		return (object) $newlog;
	}
	/**
	 * 获得当前处理人
	 */
	public function &disposer($id) {
		$q = array(
			'id,seq,mid,phase,state,send_at,receive_at,read_at',
			'xxt_news_review_log',
			"news_id='$id' and state in ('P','D')",
		);
		$lastlog = $this->query_obj_ss($q);

		return $lastlog;
	}
}