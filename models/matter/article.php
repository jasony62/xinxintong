<?php
namespace matter;

require_once dirname(__FILE__) . '/article_base.php';

class article_model extends article_base {
	/**
	 * 记录日志时需要的列
	 */
	const LOG_FIELDS = 'siteid,id,title,summary,pic,mission_id';
	/**
	 *
	 */
	protected function table() {
		return 'xxt_article';
	}
	/**
	 *
	 */
	public function getEntryUrl($siteId, $id) {
		$url = "http://" . APP_HTTP_HOST;
		$url .= "/rest/site/fe/matter";
		if ($siteId === 'platform') {
			if ($oArticle = $this->byId($id)) {
				$url .= "?site={$oArticle->siteid}&id={$id}&type=article";
			} else {
				$url = "http://" . APP_HTTP_HOST;
			}
		} else {
			$url .= "?site={$siteId}&id={$id}&type=article";
		}

		return $url;
	}
	/**
	 *
	 */
	public function &byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = [
			$fields,
			$this->table(),
			["id" => $id],
		];
		if ($matter = $this->query_obj_ss($q)) {
			!empty($matter->matter_cont_tag) && $matter->matter_cont_tag = json_decode($matter->matter_cont_tag);
			!empty($matter->matter_mg_tag) && $matter->matter_mg_tag = json_decode($matter->matter_mg_tag);
			$matter->type = $this->getTypeName();
			$matter->entryUrl = $this->getEntryUrl($matter->siteid, $id);
		}

		return $matter;
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
		$members = \TMS_APP::M('site\user\member')->byUser($userid, array('fields' => 'id'));
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
		$articles = array($article);

		return $articles;
	}
	/**
	 * 返回进行推送的消息格式
	 */
	public function &getArticles($id) {
		$article = $this->byId($id, 'id,siteid,title,author,summary,pic,body,url');
		$articles = array($article);

		return $articles;
	}
	/**
	 * 当前访问用户是否已经点了赞
	 */
	public function praised(&$user, $articleId) {
		$q = array(
			'id,score,userid,nickname',
			'xxt_article_score',
			['article_id' => $articleId, 'userid' => $user->uid],
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
			['r.article_id' => $articleId],
		);

		if (!$range) {
			/**
			 * 全部数据
			 */
			if (empty($remarkId)) {
				$q2 = array('o' => 'r.create_at desc');
				$remarks = $this->query_objs_ss($q, $q2);
			} else {
				$q[2]['id'] = $remarkId;
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
			if (is_array($options->tag)) {
				foreach ($options->tag as $tag) {
					$w .= " and a.matter_cont_tag like '%" . $tag . "%'";
				}
			} else {
				$w .= " and a.matter_cont_tag like '%" . $options->tag . "%'";
			}
			$q = array(
				$s,
				'xxt_article a',
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

		$articles = $this->query_objs_ss($q, $q2);

		return $articles;
	}
	/*
		     * 返回全部检索内容
	*/
	public function &search_all($site, $keyword) {
		$s = "id,mpid,title,author,summary,pic,body,url,read_num,create_at,has_attachment,download_num,'article' type,matter_cont_tag";
		$f = 'xxt_article';
		$w = "siteid='$site' and state=1 and approved='Y' and can_fullsearch='Y'";
		$w .= " and (title like '%$keyword%'";
		$w .= "or summary like '%$keyword%'";
		$w .= "or body like '%$keyword%')";

		$q = array($s, $f, $w);

		$q2['o'] = 'create_at desc';

		$articles = $this->query_objs_ss($q, $q2);
		$articles = json_encode($articles);
		$articles = json_decode($articles, 1);

		//内容标签
		$q3 = [
			'id,title',
			'xxt_tag',
			['siteid' => $site, 'sub_type' => 'C'],
		];
		$tagSiteCs = $this->query_objs_ss($q3);

		//频道标签
		$q4 = "select m.matter_id,m.channel_id,c.siteid,c.title from xxt_channel_matter m left join xxt_channel c on m.channel_id=c.id where c.siteid='$site' and m.matter_type='article' ";
		$tag_channel = $this->query_objs($q4);

		//将一篇文章所有标签放到tag下
		$b = array();
		foreach ($articles as $k => $v) {
			$a = array();
			if (!empty($v['matter_cont_tag'])) {
				$v['matter_cont_tag'] = json_decode($v['matter_cont_tag']);
				foreach ($v['matter_cont_tag'] as $tagMatterC) {
					foreach ($tagSiteCs as $tagSiteC) {
						if ($tagMatterC == $tagSiteC->id) {
							$a['content'][] = $tagSiteC->title;
						}
					}
				}
			}
			foreach ($tag_channel as $kl => $vl) {
				if ($v['id'] == $vl->matter_id) {
					$a['channel'][] = $vl->title;
				}
			}
			$v['tag'] = $a;
			$b[$k] = $v;
		}

		return $b;
	}
	/*
		     * 返回全文检索（统计）数目
	*/
	public function fullsearch_num($site, $keyword) {
		$s = "count(*) as c";
		$f = 'xxt_article';
		$w = "siteid='$site' and state=1 and approved='Y' and can_fullsearch='Y'";
		$w .= " and (title like '%$keyword%'";
		$w .= "or summary like '%$keyword%'";
		$w .= "or body like '%$keyword%')";

		$q = array($s, $f, $w);

		$q2['o'] = 'create_at desc';

		$r = $this->query_objs_ss($q, $q2);
		$one = (array) $r[0];
		$num = $one['c'];

		return $num;
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