<?php
namespace pl\fe\matter\custom;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 文章控制器
 */
class main extends \pl\fe\matter\base {
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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		if (!($oOptions = $this->getPostJson())) {
			$oOptions = new \stdClass;
		}

		$model = $this->model();
		$site = $model->escape($site);
		/**
		 * select fields
		 */
		$s = "a.id,a.siteid,a.title,a.summary,a.create_at,a.modify_at,a.approved,a.creater,a.creater_name,a.creater_src,'$oUser->id' uid";
		$s .= ",a.read_num,a.score,a.remark_num,a.share_friend_num,a.share_timeline_num,a.download_num";
		/**
		 * where
		 */
		$w = "a.custom_body='Y' and a.siteid='$site' and a.state=1 and finished='Y'";
		/*按名称过滤*/
		if (!empty($oOptions->byTitle)) {
			$w .= " and a.title like '%" . $model->escape($oOptions->byTitle) . "%'";
		}
		if (!empty($oOptions->byTags)) {
			foreach ($oOptions->byTags as $tag) {
				$w .= " and a.matter_mg_tag like '%" . $model->escape($tag->id) . "%'";
			}
		}

		/**
		 * 按频道过滤
		 */
		if (!empty($oOptions->channel)) {
			is_array($oOptions->channel) && $oOptions->channel = implode(',', $oOptions->channel);
			$whichChannel = "exists (select 1 from xxt_channel_matter c where a.id = c.matter_id and c.matter_type='article' and c.channel_id in ($oOptions->channel))";
			$w .= " and $whichChannel";
		}
		if (isset($oOptions->byStar) && $oOptions->byStar === 'Y') {
			$w .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='custom' and t.matter_id=a.id and userid='{$oUser->id}')";
		}
		/**
		 * 按标签过滤
		 */
		$q = [
			$s,
			'xxt_article a',
			$w,
		];
		/* order */
		!isset($oOptions->order) && $oOptions->order = '';
		switch ($oOptions->order) {
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
		/* limit */
		$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];

		if ($articles = $model->query_objs_ss($q, $q2)) {
			$q[0] = 'count(*)';
			$total = (int) $model->query_val_ss($q);
			foreach ($articles as &$a) {
				$a->type = 'custom';
			}

			return new \ResponseData(array('customs' => $articles, 'docs' => $articles, 'total' => $total));
		}

		return new \ResponseData(array('customs' => [], 'docs' => [], 'total' => 0));
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
			$article->type = 'custom';
			/**
			 * channels
			 */
			$article->channels = $this->model('matter\channel')->byMatter($id, 'article');
			/**
			 * tags
			 */
			!empty($article->matter_cont_tag) && $article->matter_cont_tag = json_decode($article->matter_cont_tag);
			!empty($article->matter_mg_tag) && $article->matter_mg_tag = json_decode($article->matter_mg_tag);
			$article->tags = $article->matter_cont_tag;
			$article->tags2 = $article->matter_mg_tag;
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
	 * 新建定制图文
	 */
	public function create_action($site, $mission) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}

		$oCustomConfig = $this->getPostJson();
		$modelArt = $this->model('matter\article');
		$oCustom = new \stdClass;

		/*从站点或项目获取的定义*/
		if (empty($mission)) {
			$oCustom->pic = $oSite->heading_pic; //使用账号缺省头图
			$oCustom->summary = '';
		} else {
			$modelMis = $this->model('matter\mission');
			$mission = $modelMis->byId($mission);
			$oCustom->summary = $modelArt->escape($mission->summary);
			$oCustom->pic = $oMission->pic;
			$oCustom->mission_id = $oMission->id;
		}

		/* 前端指定的信息 */
		$oCustom->title = empty($oCustomConfig->proto->title) ? '新定制页' : $modelArt->escape($oCustomConfig->proto->title);

		$oCustom->siteid = $oSite->id;
		$oCustom->hide_pic = 'N';
		$oCustom->url = '';
		$oCustom->body = '';
		$oCustom->custom_body = 'Y';
		$oCustom = $modelArt->create($oUser, $oCustom);
		$oCustom->type = 'custom';

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oSite->id, $oUser, $oCustom, 'C');

		return new \ResponseData($oCustom);
	}
	/**
	 * 更新单图文的字段
	 *
	 * $id article's id
	 * $nv pair of name and value
	 */
	public function update_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelArt = $this->model('matter\article');
		$oCustom = $modelArt->byId($id, ['fields' => 'siteid,id,title,summary']);
		if (false === $oCustom) {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();
		isset($oPosted->title) && $oPosted->title = $modelArt->escape($oPosted->title);
		isset($oPosted->summary) && $oPosted->summary = $modelArt->escape($oPosted->summary);
		isset($oPosted->author) && $oPosted->author = $modelArt->escape($oPosted->author);
		isset($oPosted->body) && $oPosted->body = $modelArt->escape(urldecode($oPosted->body));

		if ($oCustom = $modelArt->modify($oUser, $oCustom, $oPosted)) {
			$this->model('matter\log')->matterOp($site, $oUser, $oCustom, 'U');
		}

		return new \ResponseData($oCustom);
	}
	/**
	 * 复制定制图文
	 */
	public function copy_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelArt = $this->model('matter\article')->setOnlyWriteDbConn(true);
		$oCopied = $modelArt->byId($id);
		if (false === $oCopied) {
			return new \ObjectNotFoundError();
		}

		$modelPage = $this->model('code\page')->setOnlyWriteDbConn(true);
		$pageid = $modelPage->copy($oUser->id, $oCopied->page_id);

		$oCustom = new \stdClass;
		$oCustom->siteid = $site;
		$oCustom->title = $oCopied->title . '-副本';
		$oCustom->pic = $oCopied->pic;
		$oCustom->hide_pic = 'Y';
		$oCustom->summary = $oCopied->summary;
		$oCustom->url = '';
		$oCustom->body = '';
		$oCustom->custom_body = 'Y';
		$oCustom->page_id = $pageid;

		$oCustom = $modelArt->create($oUser, $oCustom);
		$oCustom->type = 'custom';

		return new \ResponseData($oCustom);
	}
	/**
	 * 删除定制页
	 * 只是打标记，不真正删除数据
	 */
	public function remove_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelMat = $this->model('matter\article');
		$oMatter = $modelMat->byId($id, 'id,title,summary,pic');
		if (false === $oMatter) {
			return new \ObjectNotFoundError();
		}

		/* 将图文从所属的多图文和频道中删除 */
		$model->delete('xxt_channel_matter', ['matter_id' => $id, 'matter_type' => 'custom']);
		$modelNews = $this->model('matter\news');
		if ($news = $modelNews->byMatter($id, 'custom')) {
			foreach ($news as $n) {
				$modelNews->removeMatter($n->id, $id, 'custom');
			}
		}
		/*记录操作日志*/
		$rst = $modelMat->remove($oUser, $oMatter, 'Recycle');

		return new \ResponseData($rst);
	}
	/**
	 * 用指定的模板替换定制页面内容
	 *
	 * @param int $id article'id
	 *
	 */
	public function pageByTemplate_action($id, $template) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTemplate = $this->model('matter\template');
		$template = $modelTemplate->byId($template);

		$modelArt = $this->model('matter\article');
		$copied = $modelArt->byId($template->matter_id);
		$target = $modelArt->byId($id);

		$modelPage = $this->model('code\page');
		$pageid = $modelPage->copy($oUser->id, $copied->page_id, $target->page_id);

		if ($target->page_id === 0) {
			$this->_update($id, ['page_id' => $pageid]);
		}

		$oTargetPage = $modelPage->byId($pageid);

		return new \ResponseData($oTargetPage);
	}
}