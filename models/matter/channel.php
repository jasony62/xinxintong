<?php
namespace matter;

require_once dirname(__FILE__) . '/article_base.php';

class channel_model extends article_base {
	/**
	 * 记录日志时需要的列
	 */
	const LOG_FIELDS = 'siteid,id,title';
	/**
	 *
	 */
	protected function table() {
		return 'xxt_channel';
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
		$id = $this->escape($id);
		$type = $this->escape($type);
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
	 *
	 * 置顶+动态+置底
	 *
	 * return 频道包含的文章，小于等于频道的容量
	 */
	public function &getMatters($channel_id, $channel = null) {
		$matters = []; // 返回结果
		/**
		 * load channel.
		 */
		if (empty($channel)) {
			$channel = $this->byId($channel_id, ['fields' => 'id,siteid,matter_type,orderby,volume,top_type,top_id,bottom_type,bottom_id']);
		}
		if ($channel === false) {
			return $matters;
		}
		if (empty($channel->matter_type)) {
			$matterTypes = [
				'article' => 'xxt_article',
				'enroll' => 'xxt_enroll',
				'signin' => 'xxt_signin',
				'lottery' => 'xxt_lottery',
				//'channel' => 'xxt_channel',
				//'news' => 'xxt_news',
				'link' => 'xxt_link',
				'contribute' => 'xxt_contribute',
				'wall' => 'xxt_wall',
				'mission' => 'xxt_mission',
			];
		} else {
			$matterTypes = [$channel->matter_type => 'xxt_' . $channel->matter_type];
		}

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
		/**
		 * in channel
		 */
		foreach ($matterTypes as $type => $table) {
			$q1 = [];
			$q1[] = $this->matterColumns($type) . ",cm.create_at add_at";
			$q1[] = "$table m,xxt_channel_matter cm";
			$qaw = "cm.channel_id=$channel_id and m.id=cm.matter_id and cm.matter_type='$type'";
			switch ($type) {
			case 'article':
				$qaw .= " and m.state<>0 and m.approved='Y'";
				break;
			case 'enroll':
			case 'signin':
			case 'lottery':
				$qaw .= " and m.state<>0";
				break;
			default:
				$qaw .= " and m.state=1";
			}

			!empty($top) && $top->type === $type && $qaw .= " and m.id<>'$top->id'";

			!empty($bottom) && $bottom->type === $type && $qaw .= " and m.id<>'$bottom->id'";

			$q1[] = $qaw;
			$q2 = [];
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
			$channel = $this->byId($channel_id, ['fields' => 'id,mpid,orderby,volume,top_type,top_id,bottom_type,bottom_id']);
		}

		/**
		 * top matter
		 */
		if (!empty($channel->top_type) && $channel->top_type === 'article') {
			$qt[] = "a.id,a.mpid,a.title,a.summary,a.pic,a.body,a.create_at";
			$qt[] = 'xxt_article a';
			$qt[] = "a.id='$channel->top_id' and a.state=1 and a.approved='Y'";
			$top = $this->query_obj_ss($qt);
		}
		/**
		 * bottom matter
		 */
		if (!empty($channel->bottom_type) && $channel->bottom_type === 'article') {
			$qb[] = 'a.id,a.mpid,a.title,a.summary,a.pic,a.body,a.create_at';
			$qb[] = 'xxt_article a';
			$qb[] = "a.id='$channel->bottom_id' and a.state=1 and a.approved='Y'";
			$bottom = $this->query_obj_ss($qb);
		}
		/**
		 * load articles.
		 */
		$qa1[] = 'a.id,a.mpid,a.title,a.summary,a.pic,a.body,a.create_at,ca.create_at';
		$qa1[] = 'xxt_article a,xxt_channel_matter ca';
		$qaw = "ca.channel_id=$channel_id and a.id=ca.matter_id and ca.matter_type='article' and a.state=1 and a.approved='Y'";
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
	 * @param int $channel_id 频道的id
	 *
	 * @return 频道包含的所有条目
	 */
	public function &getMattersNoLimit($channel_id, $userid, $params) {
		/**
		 * load channel.
		 */
		$channel = $this->byId($channel_id, ['fields' => 'matter_type,orderby,volume']);
		/**
		 * in channel
		 */
		if ($channel->matter_type === 'article') {
			$orderby = $channel->orderby;
			$channel_id = $this->escape($channel_id);
			$q1 = array();
			$q1[] = "m.id,m.title,m.summary,m.pic,m.create_at,m.creater_name,cm.create_at add_at,'article' type,m.score,m.remark_num,s.score myscore,st.name site_name,st.id siteid,st.heading_pic,m.matter_cont_tag,m.matter_mg_tag";
			$q1[] = "xxt_article m left join xxt_article_score s on m.id=s.article_id and s.vid='$userid',xxt_channel_matter cm,xxt_site st";
			$q1[] = "m.state=1 and m.approved='Y' and cm.channel_id=$channel_id and m.id=cm.matter_id and cm.matter_type='article' and m.siteid=st.id";

			$q2 = array();
			$q2['o'] = $this->matterOrderby('article', $orderby, 'cm.create_at desc');

			if (isset($params->page) && isset($params->size)) {
				$q2['r'] = array(
					'o' => ($params->page - 1) * $params->size,
					'l' => $params->size,
				);
			} else if (isset($channel->volume)) {
				$q2['r'] = array(
					'o' => 0,
					'l' => $channel->volume,
				);
			}

			if ($matters = $this->query_objs_ss($q1, $q2)) {
				foreach ($matters as $matter) {
					!empty($matter->matter_cont_tag) && $matter->matter_cont_tag = json_decode($matter->matter_cont_tag);
					!empty($matter->matter_mg_tag) && $matter->matter_mg_tag = json_decode($matter->matter_mg_tag);
				}
			}
			$q1[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q1);

			$data = new \stdClass;
			$data->matters = $matters;
			$data->total = $total;
			return $data;
		} else {
			$q1 = [
				'cm.create_at,cm.matter_type,cm.matter_id',
				'xxt_channel_matter cm',
				["cm.channel_id" => $channel_id],
			];
			$q2['o'] = 'cm.create_at desc , cm.matter_id desc , cm.matter_type desc';

			// 分页获取，如果素材已经删除，或者素材尚未批准的情况下，分页会导致返回的数量不正确
			if (isset($params->page) && isset($params->size)) {
				$q2['r'] = array(
					'o' => ($params->page - 1) * $params->size,
					'l' => $params->size,
				);
			}
			$matters = []; // 可用的素材
			$simpleMatters = $this->query_objs_ss($q1, $q2);
			$q1[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q1);
			foreach ($simpleMatters as $sm) {
				/* 检查素材是否可用 */
				$valid = true;
				if ($sm->matter_type !== 'article') {
					$fullMatter = \TMS_APP::M('matter\\' . $sm->matter_type)->byId($sm->matter_id);
				} else {
					$q = [
						"a.id,a.title,a.creater_name,a.create_at,a.summary,a.pic,a.state,s.name site_name,'article' type,a.matter_cont_tag,a.matter_mg_tag,s.heading_pic",
						'xxt_article a, xxt_site s',
						"a.id = $sm->matter_id and a.state = 1 and a.approved = 'Y' and a.siteid=s.id and s.state = 1",
					];
					$fullMatter = $this->query_obj_ss($q);
				}

				if (false === $fullMatter) {
					continue;
				}

				switch ($sm->matter_type) {
				case 'enroll':
				case 'signin':
				case 'lottery':
					if ($fullMatter->state !== '1' && $fullMatter->state !== '2') {
						$valid = false;
					}
					break;
				default:
					if ($fullMatter->state !== '1') {
						$valid = false;
					}
				}
				if (!$valid) {
					continue;
				}

				$fullMatter->type = $sm->matter_type;
				$fullMatter->add_at = $sm->create_at;
				if (!empty($fullMatter->matter_cont_tag) && is_string($fullMatter->matter_cont_tag)) {
					$fullMatter->matter_cont_tag = json_decode($fullMatter->matter_cont_tag);
				}
				if (!empty($fullMatter->matter_mg_tag) && is_string($fullMatter->matter_mg_tag)) {
					$fullMatter->matter_mg_tag = json_decode($fullMatter->matter_mg_tag);
				}
				$matters[] = $fullMatter;
			}

			$data = new \stdClass;
			$data->matters = $matters;
			$data->total = $total;
			return $data;
		}
	}
	/**
	 * 频道中增加素材
	 *
	 * @param int $id channel's id
	 * @param object $matter
	 */
	public function addMatter($id, $matter, $createrId, $createrName, $createrSrc = 'A') {
		is_array($matter) && $matter = (object) $matter;

		$channel = $this->byId($id);
		$oMatter = $this->model('matter\\' . $matter->type)->byId($matter->id);
		$current = time();

		$newc['matter_id'] = $oMatter->id;
		$newc['matter_type'] = $oMatter->type;
		$newc['create_at'] = $current;
		$newc['creater'] = $createrId;
		$newc['creater_src'] = $createrSrc;
		$newc['creater_name'] = $createrName;

		/* 是否已经加入到频道中 */
		$q = [
			'count(*)',
			'xxt_channel_matter',
			["channel_id" => $id, "matter_id" => $oMatter->id, "matter_type" => $oMatter->type],
		];
		if (1 === (int) $this->query_val_ss($q)) {
			return false;
		}

		// new one
		$newc['channel_id'] = $id;
		$this->insert('xxt_channel_matter', $newc, false);

		/* 如果频道已经发布到团队主页上，频道增加素材时，推送给关注者 */
		if ($this->isAtHome($channel->id)) {
			$modelSite = $this->model('site');
			$site = $modelSite->byId($oMatter->siteid);
			/**
			 * 推送给关注团队的站点用户
			 */
			$modelSite->pushToClient($site, $oMatter);
			/**
			 * 推送给关注团队的团队账号
			 */
			$modelSite->pushToFriend($site, $oMatter);
		}

		return true;
	}
	/**
	 * 从频道中移除素材
	 */
	public function removeMatter($id, $matter) {
		is_array($matter) && $matter = (object) $matter;

		$rst = $this->delete(
			'xxt_channel_matter',
			["matter_id" => $matter->id, "matter_type" => $matter->type, "channel_id" => $id]
		);

		return $rst;
	}
	/**
	 * 频道是否已发布到团队站点首页
	 *
	 * @param int @id channel'is
	 *
	 */
	public function isAtHome($id) {
		$q = [
			'count(*)',
			'xxt_site_home_channel',
			["channel_id" => $id],
		];
		$cnt = (int) $this->query_val_ss($q);

		return $cnt > 0;
	}
}