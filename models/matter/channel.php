<?php
namespace matter;

require_once dirname(__FILE__) . '/article_base.php';

class channel_model extends article_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_channel';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'channel';
	}
	/**
	 * 获得一个账号下的频道
	 */
	public function &byMpid($mpid, $acceptType = null) {
		$q = array(
			"c.*",
			'xxt_channel c',
			"c.mpid='$mpid' and c.state=1",
		);
		!empty($acceptType) && $q[2] .= " and (c.matter_type='' or c.matter_type='$acceptType')";

		$q2['o'] = 'c.create_at desc';

		$channels = $this->query_objs_ss($q, $q2);

		return $channels;
	}
	/**
	 * 获得素材的所有频道
	 */
	public function &byMatter($id, $type) {
		$q = array(
			'c.id,c.title,cm.create_at,c.style_page_id,c.header_page_id,c.footer_page_id,c.style_page_name,c.header_page_name,c.footer_page_name',
			'xxt_channel_matter cm,xxt_channel c',
			"cm.matter_id='$id' and cm.matter_type='$type' and cm.channel_id=c.id and c.state=1",
		);
		$q2['o'] = 'cm.create_at desc';

		$channels = $this->query_objs_ss($q, $q2);

		return $channels;
	}
	/**
	 * 获得返回素材的列列表
	 */
	private function matterColumns($type, $prefix = 'm') {
		$columns = array('id', 'title', 'summary', 'pic', 'create_at');
		switch ($type) {
		case 'article':
			$columns[] = 'read_num';
			$columns[] = 'share_friend_num';
			$columns[] = 'share_timeline_num';
			$columns[] = 'score';
			$columns[] = 'remark_num';
			break;
		}

		if (!empty($prefix)) {
			$columns = $prefix . '.' . implode(",$prefix.", $columns) . ',"' . $type . '" type';
		} else {
			$columns = implode(",", $columns) . ',"' . $type . '" type';
		}

		return $columns;
	}
	/**
	 * 获得素材的排序字段
	 */
	private function matterOrderby($type, $ordeby, $default = '', $prefix = 'm') {
		$schema = '';
		if ($type === 'article') {
			switch ($ordeby) {
			case 'read':
				$schema = $prefix . '.read_num desc';
				break;
			case 'like':
				$schema = $prefix . '.score desc';
				break;
			case 'remark':
				$schema = $prefix . '.remark_num desc';
				break;
			case 'share':
				$schema = "($prefix.share_friend_num+$prefix.share_timeline_num*50) desc";
				break;
			}
		}
		if (!empty($default)) {
			if (!empty($schema)) {
				$schema = $schema . ',' . $default;
			} else {
				$schema = $default;
			}
		}

		return $schema;
	}
	/**
	 * 获得指定频道下的素材
	 *
	 * $channel_id int 频道的id
	 * $channel 频道
	 * $runningMpid
	 *
	 * 置顶+动态+置底
	 *
	 * return 频道包含的文章，小于等于频道的容量
	 */
	public function &getMatters($channel_id, $channel = null, $runningMpid = null) {
		/**
		 * load channel.
		 */
		if (empty($channel)) {
			$channel = $this->byId($channel_id, 'id,mpid,matter_type,orderby,volume,top_type,top_id,bottom_type,bottom_id');
		}

		if (empty($channel->matter_type)) {
			$matterTypes = array(
				'article' => 'xxt_article',
				//'channel' => 'xxt_channel',
				//'news' => 'xxt_news',
				'link' => 'xxt_link',
				'enroll' => 'xxt_enroll',
				'contribute' => 'xxt_contribute',
				//'wall'=>'xxt_wall',
				//'lottery'=>'xxt_lottery'
			);
		} else {
			$matterTypes = array($channel->matter_type => 'xxt_' . $channel->matter_type);
		}

		$matters = array(); // 返回结果
		$fixed_num = 0;
		/**
		 * top matter
		 */
		if (!empty($channel->top_type)) {
			$qt[] = $this->matterColumns($channel->top_type, '');
			$qt[] = $matterTypes[$channel->top_type];
			$qt[] = "id='$channel->top_id'";
			$top = $this->query_obj_ss($qt);
			$fixed_num++;
		}
		/**
		 * bottom matter
		 */
		if (!empty($channel->bottom_type)) {
			$qb[] = $this->matterColumns($channel->bottom_type, '');
			$qb[] = $matterTypes[$channel->bottom_type];
			$qb[] = "id='$channel->bottom_id'";
			$bottom = $this->query_obj_ss($qb);
			$fixed_num++;
		}
		if ($runningMpid !== null && $runningMpid !== $channel->mpid) {
			$pmpid = $channel->mpid;
		}

		/**
		 * in channel
		 */
		foreach ($matterTypes as $type => $table) {
			$q1 = array();
			$q1[] = $this->matterColumns($type) . ",cm.create_at add_at";
			$q1[] = "$table m,xxt_channel_matter cm";
			$qaw = "m.state=1 and cm.channel_id=$channel_id and m.id=cm.matter_id and cm.matter_type='$type'";

			!empty($top) && $top->type === $type && $qaw .= " and m.id<>$top->id";

			!empty($bottom) && $bottom->type === $type && $qaw .= " and m.id<>$bottom->id";

			$q1[] = $qaw;
			$q2 = array();
			/**
			 * order by
			 */
			$q2['o'] = $this->matterOrderby($type, $channel->orderby, 'cm.create_at desc');
			/**
			 * $size
			 */
			$q2['r']['o'] = 0;
			$q2['r']['l'] = $channel->volume - $fixed_num;
			$typeMatters = $this->query_objs_ss($q1, $q2);

			$matters = array_merge($matters, $typeMatters);
		}
		if (count($matterTypes) > 1) {
			/**
			 * order by add_at
			 */
			usort($matters, function ($a, $b) {
				return $b->add_at - $a->add_at;
			});
		}
		/**
		 * add top and bottom.
		 */
		!empty($top) && $matters = array_merge(array($top), $matters);
		!empty($bottom) && $matters = array_merge($matters, array($bottom));

		return $matters;
	}
	/**
	 * 只返回频道内包含的图文不包括连接
	 * 因为微信的群发消息只能发送图文
	 *
	 * $channel_id int 频道的id
	 * $channel 置顶频道
	 *
	 * return 频道包含的文章，小于等于频道的容量
	 */
	public function &getArticles($channel_id, $channel = null) {
		$articles = array();
		/**
		 * load channel.
		 */
		if (empty($channel)) {
			$channel = $this->byId($channel_id, 'id,mpid,orderby,volume,top_type,top_id,bottom_type,bottom_id');
		}

		/**
		 * top matter
		 */
		if (!empty($channel->top_type) && $channel->top_type === 'article') {
			$qt[] = "a.id,a.mpid,a.title,a.summary,a.pic,a.body,a.create_at";
			$qt[] = 'xxt_article a';
			$qt[] = "a.id='$channel->top_id' and a.state=1";
			$top = $this->query_obj_ss($qt);
		}
		/**
		 * bottom matter
		 */
		if (!empty($channel->bottom_type) && $channel->bottom_type === 'article') {
			$qb[] = 'a.id,a.mpid,a.title,a.summary,a.pic,a.body,a.create_at';
			$qb[] = 'xxt_article a';
			$qb[] = "a.id='$channel->bottom_id' and a.state=1";
			$bottom = $this->query_obj_ss($qb);
		}
		/**
		 * load articles.
		 */
		$qa1[] = 'a.id,a.mpid,a.title,a.summary,a.pic,a.body,a.create_at,ca.create_at';
		$qa1[] = 'xxt_article a,xxt_channel_matter ca';
		$qaw = "ca.channel_id=$channel_id and a.id=ca.matter_id and ca.matter_type='article' and a.state=1";
		if (!empty($top)) {
			$qaw .= " and a.id<>$top->id";
		}
		if (!empty($bottom)) {
			$qaw .= " and a.id<>$bottom->id";
		}
		$qa1[] = $qaw;
		$qa2['o'] = $this->matterOrderby('article', $channel->orderby, 'ca.create_at desc');
		$qa2['r']['o'] = 0;
		$qa2['r']['l'] = $channel->volume;
		$articles = $this->query_objs_ss($qa1, $qa2);
		/**
		 * add top and bottom.
		 */
		!empty($top) && $articles = array_merge(array($top), $articles);
		!empty($bottom) && $articles = array_merge($articles, array($bottom));
		/**
		 * size
		 */
		$articles = array_slice($articles, 0, $channel->volume);

		return $articles;
	}
	/**
	 * 直接打开频道的情况下（不是返回信息卡片），忽略置顶和置底，返回频道中的所有条目
	 *
	 * $channel_id int 频道的id
	 * $channel 频道
	 *
	 * return 频道包含的所有条目
	 */
	public function &getMattersNoLimit($channel_id, $userid, $params) {
		/**
		 * load channel.
		 */
		$channel = $this->byId($channel_id, 'matter_type');
		/**
		 * in channel
		 */
		if ($channel->matter_type === 'article') {
			$orderby = $params->orderby || $channel->orderby;
			$q1 = array();
			$q1[] = "m.id,m.title,m.summary,m.pic,m.create_at,m.creater_name,cm.create_at add_at,'article' type,m.score,m.remark_num,s.score myscore";
			$q1[] = "xxt_article m left join xxt_article_score s on m.id=s.article_id and s.vid='$userid',xxt_channel_matter cm";
			$q1[] = "m.state=1 and m.approved='Y' and cm.channel_id=$channel_id and m.id=cm.matter_id and cm.matter_type='article'";

			$q2 = array();
			$q2['o'] = $this->matterOrderby('article', $orderby, 'cm.create_at desc');

			if (isset($params->page) && isset($params->size)) {
				$q2['r'] = array(
					'o' => ($params->page - 1) * $params->size,
					'l' => $params->size,
				);
			}

			$matters = $this->query_objs_ss($q1, $q2);
		} else {
			$matters = array();

			$q1 = array();
			$q1[] = 'cm.create_at,cm.matter_type,cm.matter_id';
			$q1[] = 'xxt_channel_matter cm';
			$q1[] = "cm.channel_id='$channel_id'";

			$q2['o'] = 'cm.create_at desc';
			if (isset($params->page) && isset($params->size)) {
				$q2['r'] = array(
					'o' => ($params->page - 1) * $params->size,
					'l' => $params->size,
				);
			}

			$simpleMatters = $this->query_objs_ss($q1, $q2);
			foreach ($simpleMatters as $sm) {
				$fullMatter = \TMS_APP::M('matter\\' . $sm->matter_type)->byId($sm->matter_id);
				$fullMatter->type = $sm->matter_type;
				$fullMatter->add_at = $sm->create_at;
				$matters[] = $fullMatter;
			}
		}

		return $matters;
	}
	/**
	 * 频道中增加素材
	 *
	 * $id
	 * $matter
	 */
	public function addMatter($id, $matter, $creater, $createrName, $createrSrc = 'A') {
		is_array($matter) && $matter = (object) $matter;

		$current = time();

		$newc['matter_id'] = $matter->id;
		$newc['matter_type'] = $matter->type;
		$newc['create_at'] = $current;
		$newc['creater'] = $creater;
		$newc['creater_src'] = $createrSrc;
		$newc['creater_name'] = $createrName;
		// check
		$q = array(
			'count(*)',
			'xxt_channel_matter',
			"channel_id=$id and matter_id='$matter->id' and matter_type='matter->type'",
		);
		if ('1' === $this->query_val_ss($q)) {
			return false;
		}

		// new one
		$newc['channel_id'] = $id;

		$this->insert('xxt_channel_matter', $newc, false);

		return true;
	}
	/**
	 * 从频道中移除素材
	 */
	public function removeMatter($id, $matter) {
		is_array($matter) && $matter = (object) $matter;

		$rst = $this->delete(
			'xxt_channel_matter',
			"matter_id='$matter->id' and matter_type='$matter->type' and channel_id=$id");

		return $rst;
	}
}