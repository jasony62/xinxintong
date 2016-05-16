<?php
namespace pl\fe\matter\custom;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 文章控制器
 */
class main extends \pl\fe\matter\base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'custom';
	}
	/**
	 * 返回单图文视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/custom/frame');
		exit;
	}
	/**
	 * 返回单图文视图
	 */
	public function edit_action() {
		\TPL::output('/pl/fe/matter/custom/frame');
		exit;
	}
	/**
	 *
	 */
	public function read_action() {
		\TPL::output('/pl/fe/matter/custom/frame');
		exit;
	}
	/**
	 *
	 */
	public function stat_action() {
		\TPL::output('/pl/fe/matter/custom/frame');
		exit;
	}
	/**
	 * 获得可见的图文列表
	 *
	 * $id article's id
	 * $page
	 * $size
	 * post options
	 * --$src p:从父账号检索图文
	 * --$tag
	 * --$channel
	 * --$order
	 *
	 */
	public function list_action($site, $page = 1, $size = 30) {
		if (!($options = $this->getPostJson())) {
			$options = new \stdClass;
		}

		$uid = \TMS_CLIENT::get_client_uid();
		/**
		 * select fields
		 */
		$s = "a.id,a.siteid,a.title,a.summary,a.create_at,a.modify_at,a.approved,a.creater,a.creater_name,a.creater_src,'$uid' uid";
		$s .= ",a.read_num,a.score,a.remark_num,a.share_friend_num,a.share_timeline_num,a.download_num";
		/**
		 * where
		 */
		$w = "a.custom_body='Y' and a.siteid='$site' and a.state=1 and finished='Y'";
		/**
		 * 按频道过滤
		 */
		if (!empty($options->channel)) {
			is_array($options->channel) && $options->channel = implode(',', $options->channel);
			$whichChannel = "exists (select 1 from xxt_channel_matter c where a.id = c.matter_id and c.matter_type='article' and c.channel_id in ($options->channel))";
			$w .= " and $whichChannel";
		}
		/**
		 * 按标签过滤
		 */
		!isset($options->order) && $options->order = '';
		if (empty($options->tag) && empty($options->tag2)) {
			$q = array(
				$s,
				'xxt_article a',
				$w,
			);
			switch ($options->order) {
			case 'title':
				$q2['o'] = 'CONVERT(a.title USING gbk ) COLLATE gbk_chinese_ci';
				break;
			case 'read':
				$q2['o'] = 'a.read_num desc';
				break;
			case 'share_friend':
				$q2['o'] = 'a.share_friend_num desc';
				break;
			case 'share_timeline':
				$q2['o'] = 'a.share_timeline_num desc';
				break;
			default:
				$q2['o'] = 'a.modify_at desc';
			}
		} else {
			/**
			 * 按标签过滤
			 */
			$w .= " and a.siteid=at.siteid and a.id=at.res_id";
			$tags = implode(',', array_merge($options->tag, $options->tag2));
			$w .= " and at.tag_id in($tags)";
			$q = array(
				$s,
				'xxt_article a,xxt_article_tag at',
				$w,
			);
			$q2['g'] = 'a.id';
			switch ($options->order) {
			case 'title':
				$q2['o'] = 'count(*),CONVERT(a.title USING gbk ) COLLATE gbk_chinese_ci';
				break;
			case 'read':
				$q2['o'] = 'a.read_num desc';
				break;
			case 'share_friend':
				$q2['o'] = 'a.share_friend_num desc';
				break;
			case 'share_timeline':
				$q2['o'] = 'a.share_timeline_num desc';
				break;
			default:
				$q2['o'] = 'a.modify_at desc';
			}
		}
		/**
		 * limit
		 */
		$q2['r'] = array('o' => ($page - 1) * $size, 'l' => $size);

		if ($articles = $this->model()->query_objs_ss($q, $q2)) {
			/**
			 * amount
			 */
			$q[0] = 'count(*)';
			$total = (int) $this->model()->query_val_ss($q);
			/**
			 * 获得每个图文的tag
			 */
			foreach ($articles as &$a) {
				$ids[] = $a->id;
				$map[$a->id] = &$a;
			}
			$rels = $this->model('tag')->tagsByRes($ids, 'article', 0);
			foreach ($rels as $aid => &$tags) {
				$map[$aid]->tags = $tags;
			}
			$rels = $this->model('tag')->tagsByRes($ids, 'article', 1);
			foreach ($rels as $aid => &$tags) {
				$map[$aid]->tags2 = $tags;
			}

			return new \ResponseData(array('customs' => $articles, 'total' => $total));
		}
		return new \ResponseData(array('customs' => array(), 'total' => 0));
	}
	/**
	 * 获得指定的图文
	 *
	 * @param int $id article's id
	 */
	public function get_action($site, $id, $cascade = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$q = array(
			"a.*,'{$user->id}' uid",
			'xxt_article a',
			"a.siteid='$site' and a.state=1 and a.id=$id",
		);
		if (($article = $this->model()->query_obj_ss($q)) && $cascade === 'Y') {
			/**
			 * channels
			 */
			$article->channels = $this->model('matter\channel')->byMatter($id, 'article');
			/**
			 * tags
			 */
			$modelTag = $this->model('tag');
			$article->tags = $modelTag->tagsByRes($article->id, 'article', 0);
			$article->tags2 = $modelTag->tagsByRes($article->id, 'article', 1);
			/**
			 * acl
			 */
			$article->acl = $this->model('acl')->byMatter($site, 'article', $id);
			/*所属项目*/
			if ($article->mission_id) {
				$article->mission = $this->model('matter\mission')->byMatter($site, $app->id, 'custom');
			}
		}

		return new \ResponseData($article);
	}
	/**
	 * 创建新图文
	 */
	public function create_action($site, $mission) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$article = array();
		$current = time();
		$customConfig = $this->getPostJson();
		$site = $this->model('site')->byId($site, array('fields' => 'id,heading_pic'));

		/*从站点或项目获取的定义*/
		if (empty($mission)) {
			$article['pic'] = $site->heading_pic; //使用账号缺省头图
			$article['summary'] = '';
		} else {
			$modelMis = $this->model('mission');
			$mission = $modelMis->byId($mission);
			$article['summary'] = $mission->summary;
			$article['pic'] = $mission->pic;
			$article['mission_id'] = $mission->id;
		}

		/*前端指定的信息*/
		$article['title'] = empty($customConfig->proto->title) ? '新定制页' : $customConfig->proto->title;

		$article['siteid'] = $site->id;
		$article['creater'] = $user->id;
		$article['creater_src'] = 'A';
		$article['creater_name'] = $user->name;
		$article['create_at'] = $current;
		$article['modifier'] = $user->id;
		$article['modifier_src'] = 'A';
		$article['modifier_name'] = $user->name;
		$article['modify_at'] = $current;
		$article['author'] = $user->name;
		$article['hide_pic'] = 'N';
		$article['url'] = '';
		$article['body'] = '';
		$article['custom_body'] = 'Y';
		$id = $this->model()->insert('xxt_article', $article, true);

		/* 记录操作日志 */
		$matter = (object) $article;
		$matter->id = $id;
		$matter->type = 'custom';
		$this->model('log')->matterOp($site->id, $user, $matter, 'C');

		/* 记录和任务的关系 */
		if (isset($mission->id)) {
			$modelMis->addMatter($user, $site, $mission->id, $matter);
		}

		return new \ResponseData($id);
	}
	/**
	 * 更新单图文的字段
	 *
	 * $id article's id
	 * $nv pair of name and value
	 */
	public function update_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();

		$nv = (array) $this->getPostJson();
		isset($nv['title']) && $nv['title'] = $model->escape($nv['title']);
		isset($nv['summary']) && $nv['summary'] = $model->escape($nv['summary']);
		isset($nv['author']) && $nv['author'] = $model->escape($nv['author']);
		isset($nv['body']) && $nv['body'] = $model->escape(urldecode($nv['body']));

		$rst = $this->_update($site, $id, $nv);

		return new \ResponseData($rst);
	}
	/**
	 * 复制定制页
	 */
	public function copy_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelArt = $this->model('matter\article');
		$modelPage = $this->model('code\page');

		$copied = $modelArt->byId($id);

		$pageid = $modelPage->copy($user->id, $copied->page_id);

		$current = time();
		$article = array();
		$article['siteid'] = $site;
		$article['creater'] = $user->id;
		$article['creater_src'] = 'A';
		$article['creater_name'] = $user->name;
		$article['create_at'] = $current;
		$article['modifier'] = $user->id;
		$article['modifier_src'] = 'A';
		$article['modifier_name'] = $user->name;
		$article['modify_at'] = $current;
		$article['title'] = $copied->title . '-副本';
		$article['author'] = $user->name;
		$article['pic'] = $copied->pic;
		$article['hide_pic'] = 'Y';
		$article['summary'] = $copied->summary;
		$article['url'] = '';
		$article['body'] = '';
		$article['custom_body'] = 'Y';
		$article['page_id'] = $pageid;
		$id = $this->model()->insert('xxt_article', $article, true);

		return new \ResponseData($id);
	}
	/**
	 * 删除定制页
	 * 只是打标记，不真正删除数据
	 */
	public function remove_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();

		$rst = $model->update(
			'xxt_article',
			array('state' => 0, 'modify_at' => time()),
			"siteid='$site' and id='$id'"
		);
		/** 将图文从所属的多图文和频道中删除 */
		if ($rst) {
			$model->delete('xxt_channel_matter', "matter_id='$id' and matter_type='custom'");
			$modelNews = $this->model('matter\news');
			if ($news = $modelNews->byMatter($id, 'custom')) {
				foreach ($news as $n) {
					$modelNews->removeMatter($n->id, $id, 'custom');
				}
			}
			/*记录操作日志*/
			$matter = $this->model('matter\article2')->byId($id, 'id,title,summary,pic');
			$matter->type = 'custom';
			$this->model('log')->matterOp($site, $user, $matter, 'D');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 用指定的模板替换定制页面内容
	 *
	 * @param int $id article'id
	 *
	 */
	public function pageByTemplate_action($id, $template) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTemplate = $this->model('shop\shelf');
		$template = $modelTemplate->byId($template);

		$modelArt = $this->model('matter\article');
		$copied = $modelArt->byId($template->matter_id);
		$target = $modelArt->byId($id);

		$modelPage = $this->model('code\page');
		$pageid = $modelPage->copy($user->id, $copied->page_id, $target->page_id);

		if ($target->page_id === 0) {
			$this->_update($id, array('page_id' => $pageid));
		}

		return new \ResponseData($pageid);
	}
	/**
	 * 更新图文信息并记录操作日志
	 */
	private function _update($siteId, $id, $nv) {
		$user = $this->accountUser();
		$current = time();

		$nv['modifier'] = $user->id;
		$nv['modifier_src'] = $user->src;
		$nv['modifier_name'] = $user->name;
		$nv['modify_at'] = $current;

		$rst = $this->model()->update(
			'xxt_article',
			$nv,
			"siteid='$siteId' and id='$id'"
		);
		/*记录操作日志*/
		$article = $this->model('matter\article')->byId($id, 'id,title,summary,pic');
		$article->type = 'custom';
		$this->model('log')->matterOp($siteId, $user, $article, 'U');

		return $rst;
	}
}