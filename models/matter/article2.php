<?php
namespace matter;

require_once dirname(__FILE__) . '/article_base.php';

class article2_model extends article_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_article';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'article';
	}
	/**
	 *
	 */
	public function getEntryUrl($siteId, $id, $userid = null) {
		$url = "http://" . $_SERVER['HTTP_HOST'];
		$url .= "/rest/site/fe/matter";
		$url .= "?site={$siteId}&id={$id}&type=article";

		return $url;
	}
	/**
	 *
	 */
	public function &byCreater($site, $creater, $fields = '*', $cascade = false) {
		$q = array(
			$fields,
			'xxt_article',
			"siteid='$site' and creater='$creater' and state=1");
		$q2 = array('o' => 'modify_at desc');

		$articles = $this->query_objs_ss($q, $q2);

		if (!empty($articles) && $cascade) {
			foreach ($articles as &$a) {
				$a->channels = \TMS_APP::M('matter\channel')->byMatter($a->id, 'article');
			}
		}

		return $articles;
	}
	/**
	 * 根据投稿来源
	 */
	public function &byEntry($site, $entry, $creater, $fields = '*', $cascade = false) {
		$q = array(
			$fields,
			'xxt_article',
			"siteid='$site' and entry='$entry' and creater='$creater' and state=1");
		$q2 = array('o' => 'modify_at desc');

		$articles = $this->query_objs_ss($q, $q2);

		if (!empty($articles) && $cascade) {
			$modelChn = \TMS_APP::M('matter\channel');
			foreach ($articles as &$a) {
				$a->channels = $modelChn->byMatter($a->id, 'article');
			}
		}

		return $articles;
	}
	/**
	 * $mid member's 仅限认证用户
	 * $entry 指定的投稿活动
	 * $phase
	 */
	public function &byReviewer($siteId, $userid, $entry, $phase, $fields = '*', $cascade = false) {
		$members = \TMS_APP::M('site\user\member')->byUser($siteId, $userid, array('fields' => 'id'));
		if (!empty($members)) {
			$mids = array();
			foreach ($members as $member) {
				$mids[] = $member->id;
			}
			$mids = implode(',', $mids);
			$q = array(
				'a.*',
				'xxt_article a',
				"a.entry='$entry' and exists(select 1 from xxt_article_review_log l where a.id=l.article_id and l.mid in($mids) and phase='R')",
			);
			$q2 = array('o' => 'a.create_at desc');

			$articles = $this->query_objs_ss($q, $q2);
			if (!empty($articles) && $cascade) {
				foreach ($articles as &$a) {
					$a->disposer = $this->disposer($a->id);
				}
			}
		} else {
			$articles = false;
		}
		return $articles;
	}
	/**
	 * 获得审核通过的文稿
	 *
	 * $site
	 */
	public function &getApproved($site, $entry = null, $page = 1, $size = 30) {
		$q = array(
			'a.*',
			'xxt_article a',
			"a.siteid='$site' and a.approved='Y' and state=1",
		);
		!empty($entry) && $q[2] .= " and a.entry='$entry'";

		$q2 = array('o' => 'a.create_at desc');

		$articles = $this->query_objs_ss($q, $q2);

		return $articles;
	}
	/**
	 * 这个是基类要求的方法
	 * todo 应该用抽象类的机制处理
	 */
	public function &getMatters($id) {
		$article = $this->byId($id, "id,siteid,title,author,summary,pic,body,url");
		$article->type = 'article';
		$articles = array($article);

		return $articles;
	}
	/**
	 * 返回进行推送的消息格式
	 */
	public function &getArticles($id) {
		$article = $this->byId($id, 'id,siteid,title,author,summary,pic,body,url');
		$article->type = 'article';
		$articles = array($article);

		return $articles;
	}
	/**
	 * 获得文章的标签
	 */
	public function &tags($id) {
		$tags = $this->M('tag')->tagsByRes($id, 'article');
		return $tags;
	}
	/**
	 * 当前访问用户是否已经点了赞
	 */
	public function praised(&$user, $articleId) {
		$q = array(
			'id,score,userid,nickname',
			'xxt_article_score',
			"article_id='$articleId' and userid='{$user->uid}'",
		);
		$log = $this->query_obj_ss($q);
		if ($log) {
			$updated = array();
			if (empty($log->nickname) && !empty($user->nickname)) {
				$updated['nickname'] = $user->nickname;
			}
			if (!empty($updated)) {
				$this->update('xxt_article_score', $updated, "id=$log->id");
			}
			return 1 === (int) $log->score;
		} else {
			return false;
		}
	}
	/**
	 * 文章评论
	 *
	 * $range 分页参数
	 */
	public function remarks($articleId, $remarkId = null, $range = false) {
		$q = array(
			'r.*',
			'xxt_article_remark r',
			"r.article_id='$articleId'",
		);

		if (!$range) {
			/**
			 * 全部数据
			 */
			if (empty($remarkId)) {
				$q2 = array('o' => 'r.create_at desc');
				$remarks = $this->query_objs_ss($q, $q2);
			} else {
				$q[2] .= " and id='$remarkId'";
				$remarks = $this->query_obj_ss($q);
			}
			return $remarks;
		} else {
			/**
			 * 分页数据
			 */
			$q2 = array(
				'o' => 'r.create_at desc',
				'r' => array(
					'o' => (($range['p'] - 1) * $range['s']),
					'l' => ($range['s']),
				),
			);
			$remarks = $this->query_objs_ss($q, $q2);
			/**
			 * 总数
			 */
			$q[0] = 'count(*)';
			$amount = $this->query_val_ss($q);

			return array($remarks, $amount);
		}
	}
	/**
	 * 文章的评论用户
	 */
	public function remarkers($articleId) {
		$q = array(
			'distinct fid,openid,nickname',
			'xxt_article_remark r',
			"r.article_id='$articleId'",
		);
		$remarkers = $this->query_objs_ss($q);

		return $remarkers;
	}
	/**
	 * 按条件查找单图文
	 */
	public function find($site, $user, $page = 1, $size = 10, $options) {
		$s = "a.id,a.title,a.modify_at,a.author,a.summary,a.pic,a.url";
		$w = "a.siteid='$site' and a.state=1 and finished='Y'";
		if (empty($options->tag)) {
			$q = array(
				$s,
				'xxt_article a',
				$w,
			);
		} else {
			/* 按标签过滤 */
			is_array($options->tag) && $options->tag = implode(',', $options->tag);
			$w .= " and a.siteid=at.siteid and a.id=at.res_id and at.tag_id in($options->tag)";
			$q = array(
				$s,
				'xxt_article a,xxt_article_tag at',
				$w,
			);
		}
		$q2['o'] = 'a.modify_at desc';
		$q2['r'] = array('o' => ($page - 1) * $size, 'l' => $size);

		if ($articles = $this->query_objs_ss($q)) {
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);

			return array('articles' => $articles, 'total' => $total);
		}

		return array('articles' => array(), 'total' => 0);
	}
	/**
	 * 全文检索单图文，将符合条件的结果组成多图文
	 */
	public function fullsearch_its($site, $keyword, $page = 1, $limit = 5) {
		$s = "id,siteid,title,author,summary,pic,body,url,'article' type";
		$f = 'xxt_article';
		$w = "siteid='$site' and state=1 and approved='Y' and can_fullsearch='Y'";
		$w .= " and (title like '%$keyword%'";
		$w .= "or summary like '%$keyword%'";
		$w .= "or body like '%$keyword%')";
		$q = array($s, $f, $w);

		$q2['o'] = 'create_at desc';
		$q2['r']['o'] = ($page - 1) * $limit;
		$q2['r']['l'] = $limit;

		$articles = parent::query_objs_ss($q, $q2);

		return $articles;
	}
	/**
	 * 审核记录
	 *
	 * $site
	 * $id article'id
	 * $mid member's id
	 * $phase
	 */
	public function forward($site, $id, $mid, $phase, $remark = '') {
		$q = array(
			'*',
			'xxt_article_review_log',
			"siteid='$site' and article_id='$id'",
		);
		$q2 = array(
			'o' => 'seq desc',
			'r' => array('o' => 0, 'l' => 1),
		);
		$last = $this->query_objs_ss($q, $q2);
		if (!empty($last)) {
			$last = $last[0];
			$this->update(
				'xxt_article_review_log',
				array('state' => 'F'),
				"id=$last->id"
			);
		}

		$member = \TMS_APP::M('site\user\member')->byId($mid);
		$seq = empty($last) ? 1 : $last->seq + 1;

		$newlog = array(
			'siteid' => $site,
			'article_id' => $id,
			'seq' => $seq,
			'mid' => $mid,
			'disposer_name' => $member->name,
			'send_at' => time(),
			'state' => 'P',
			'phase' => $phase,
			'remark' => $remark,
		);
		$newlog['id'] = $this->insert('xxt_article_review_log', $newlog, true);

		return (object) $newlog;
	}
	/**
	 * 获得当前处理人
	 * 状态为等待处理（Pending），或正在处理（Dispose）
	 */
	public function &disposer($id) {
		$q = array(
			'id,seq,mid,phase,state,send_at,receive_at,read_at',
			'xxt_article_review_log',
			"article_id='$id' and state in ('P','D')",
		);
		$lastlog = $this->query_obj_ss($q);

		return $lastlog;
	}
	/**
	 *
	 */
	public function &reviewlogs($id) {
		$q = array(
			'id,seq,mid,phase,state,disposer_name,send_at,receive_at,read_at,remark',
			'xxt_article_review_log',
			"article_id='$id'",
		);
		$q2 = array('o' => 'seq desc');

		$logs = $this->query_objs_ss($q);

		return $logs;
	}
	/**
	 * 返回投稿信息
	 * @param int $id
	 */
	public function &getContributionInfo($id) {
		$info = $this->byId($id, 'entry,creater,creater_name,creater_src,create_at');
		if (!empty($info->entry)) {
			if ($info->creater_src === 'M') {
				$member = \TMS_APP::M('user/member')->byId($info->creater, 'openid');
				$info->openid = $member->openid;
			}
		}
		return $info;
	}
}