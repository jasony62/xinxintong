<?php
namespace pl\fe\matter\article;

require_once dirname(dirname(__FILE__)) . '/base.php';

/*
 * 文章控制器
 */
class main extends \pl\fe\matter\base {
	/**
	 *
	 */
	protected function getMatterType() {
		return 'article';
	}
	/**
	 * 返回单图文视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/article/frame');
		exit;
	}
	/**
	 * 获得可见的图文列表
	 *
	 * @param $id article's id
	 * $page
	 * $size
	 * post options
	 * --$src p:从父账号检索图文
	 * --$tag
	 * --$channel
	 * --$order
	 *
	 */
	public function list_action($site = null, $mission = null, $page = 1, $size = 30) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		if (!($oOptions = $this->getPostJson())) {
			$oOptions = new \stdClass;
		}
		$modelArt = $this->model('matter\article');
		/**
		 * select fields
		 */
		$s = "a.id,a.siteid,a.title,a.summary,a.approved,a.mission_id";
		$s .= ",a.create_at,a.modify_at,a.creater,a.creater_name,a.creater_src";
		$s .= ",a.read_num,a.score,a.remark_num,a.share_friend_num,a.share_timeline_num,a.download_num";
		/**
		 * where
		 */
		$w = "a.custom_body='N' and a.state=1 and finished='Y'";
		/* 按名称过滤 */
		if (!empty($oOptions->byTitle)) {
			$w .= " and a.title like '%" . $modelArt->escape($oOptions->byTitle) . "%'";
		}
		if (!empty($oOptions->byTags)) {
			foreach ($oOptions->byTags as $tag) {
				$w .= " and a.matter_mg_tag like '%" . $modelArt->escape($tag->id) . "%'";
			}
		}
		/* 按项目过滤 */
		if (!empty($mission)) {
			$mission = $modelArt->escape($mission);
			$w .= " and a.mission_id=$mission";
			//按项目阶段过滤
			if (isset($oOptions->mission_phase_id) && !empty($oOptions->mission_phase_id) && $oOptions->mission_phase_id !== "ALL") {
				$mission_phase_id = $modelArt->escape($oOptions->mission_phase_id);
				$w .= " and a.mission_phase_id = '" . $mission_phase_id . "'";
			}
		} else {
			$site = $modelArt->escape($site);
			$w .= " and a.siteid='$site'";
		}
		/* 按频道过滤 */
		if (!empty($oOptions->channel)) {
			is_array($oOptions->channel) && $oOptions->channel = implode(',', $oOptions->channel);
			$whichChannel = "exists (select 1 from xxt_channel_matter c where a.id = c.matter_id and c.matter_type='article' and c.channel_id in ($oOptions->channel))";
			$w .= " and $whichChannel";
		}
		/* 按星标过滤 */
		if (isset($oOptions->byStar) && $oOptions->byStar === 'Y') {
			$w .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='article' and t.matter_id=a.id and userid='{$oUser->id}')";
		}
		$q = [
			$s,
			'xxt_article a',
			$w,
		];
		/**
		 * order
		 */
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
		case 'like':
			$q2['o'] = 'a.score desc';
			break;
		case 'remark':
			$q2['o'] = 'a.remark_num desc';
			break;
		case 'download':
			$q2['o'] = 'a.download_num desc';
			break;
		default:
			$q2['o'] = 'a.modify_at desc';
		}
		/**
		 * limit
		 */
		$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];

		if ($articles = $modelArt->query_objs_ss($q, $q2)) {
			$q[0] = 'count(*)';
			$total = (int) $modelArt->query_val_ss($q);
			foreach ($articles as $a) {
				$a->type = 'article';
				$a->url = $modelArt->getEntryUrl($a->siteid, $a->id);
			}
		}
		$q[0] = 'count(*)';
		$total = (int) $modelArt->query_val_ss($q);

		return new \ResponseData(['articles' => $articles, 'docs' => $articles, 'total' => $total]);
	}
	/**
	 * 获得指定的图文
	 *
	 * @param int $id article's id
	 *
	 */
	public function get_action($id, $cascade = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelAct = $this->model('matter\article');
		$article = $modelAct->byId($id);
		if ($article === false) {
			return new \ObjectNotFoundError();
		}

		$article->uid = $user->id;
		if ($cascade === 'Y') {
			/* channels */
			$article->channels = $this->model('matter\channel')->byMatter($id, 'article');
			/* acl */
			$article->acl = $this->model('acl')->byMatter($article->siteid, 'article', $id);
			/* attachments */
			if ($article->has_attachment === 'Y') {
				$article->attachments = $modelAct->query_objs_ss(array('*', 'xxt_article_attachment', "article_id='$id'"));
			}
			/* 所属项目 */
			if ($article->mission_id) {
				$article->mission = $this->model('matter\mission')->byId($article->mission_id, ['cascaded' => 'phase']);
			}
		}
		/*如果此单图文属于引用那么需要返回被引用的单图文*/
		if ($article->from_mode === 'C') {
			$id2 = $article->from_id;
			$article2 = $modelAct->byId($id2, ['fields' => 'body,author,siteid,id']);
			$article->body = $article2->body;
			$article->author = $article2->author;
		}

		return new \ResponseData($article);
	}
	/**
	 * 获得指定文章的所有评论
	 *
	 * $id article's id
	 */
	public function remarkGet_action($id, $page = 1, $size = 30) {
		$range = array(
			'p' => $page,
			's' => $size,
		);
		$rst = $this->model('matter\article')->remarks($id, null, $range);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function remarkDel_action($id) {
		$rst = $this->model()->delete('xxt_article_remark', "id=$id");

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function remarkClean_action($articleid) {
		$rst = $this->model()->delete('xxt_article_remark', "article_id=$articleid");

		return new \ResponseData($rst);
	}
	/**
	 * 创建新图文
	 * 在站点下或项目下创建图文
	 *
	 * @param string $site
	 * @param int $mission
	 *
	 */
	public function create_action($site = null, $mission = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$article = [];
		$current = time();
		$customConfig = $this->getPostJson();

		/*从站点或项目获取的定义*/
		if (empty($mission)) {
			$site = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
			$article['siteid'] = $site->id;
			$article['mpid'] = $site->id;
			$article['pic'] = $site->heading_pic; //使用站点的缺省头图
			$article['summary'] = '';
		} else {
			$modelMis = $this->model('matter\mission');
			$mission = $modelMis->byId($mission);
			$article['siteid'] = $mission->siteid;
			$article['mpid'] = $mission->siteid;
			$article['summary'] = $mission->summary;
			$article['pic'] = $mission->pic;
			$article['mission_id'] = $mission->id;
		}
		/* 前端指定的信息 */
		$article['title'] = empty($customConfig->proto->title) ? '新图文' : $customConfig->proto->title;

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
		$article['can_siteuser'] = 'Y';
		$id = $this->model()->insert('xxt_article', $article, true);

		/* 记录操作日志 */
		$matter = (object) $article;
		$matter->id = $id;
		$matter->type = 'article';
		$this->model('matter\log')->matterOp($matter->siteid, $user, $matter, 'C');

		/* 记录和任务的关系 */
		if (isset($mission->id)) {
			$modelMis->addMatter($user, $matter->siteid, $mission->id, $matter);
		}

		return new \ResponseData($matter);
	}
	/**
	 * 复制单图文
	 * @param string $site 被复制单图文所在的团队siteid
	 * @param char $mode 复制模式O:origin C:cite  D:duplicate
	 * @param int $id
	 */
	public function copy_action($site, $id, $mission = null, $mode = 'D') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$sites = $this->getPostJson();
		$modelArt = $this->model('matter\article');
		$modelArt->setOnlyWriteDbConn(true);
		$modelLog = $this->model('matter\log');

		$copied = $modelArt->byId($id);
		/*获取元图文的团队名称*/
		$fromSite = $this->model('site')->byId($site, ['fields' => 'name']);
		$current = time();

		$article = new \stdClass;
		$article->creater = $user->id;
		$article->creater_src = 'A';
		$article->creater_name = $modelArt->escape($user->name);
		$article->create_at = $current;
		$article->modifier = $user->id;
		$article->modifier_src = 'A';
		$article->modifier_name = $modelArt->escape($user->name);
		$article->modify_at = $current;
		$article->author = $modelArt->escape($user->name);
		$article->summary = $modelArt->escape($copied->summary);
		$article->hide_pic = $copied->hide_pic;
		$article->url = $copied->url;
		$article->can_siteuser = $copied->can_siteuser;
		$article->matter_cont_tag = empty($copied->matter_cont_tag) ? '' : json_encode($copied->matter_cont_tag);
		$article->from_siteid = $modelArt->escape($site);
		$article->from_site_name = $modelArt->escape($fromSite->name);
		$article->from_id = $modelArt->escape($id);
		if ($mode === 'D') {
			$article->title = $modelArt->escape($copied->title . '（副本）');
			$article->body = $modelArt->escape($copied->body);
		} else {
			$article->title = $modelArt->escape($copied->title . '（引用）');
		}
		if (!empty($mission)) {
			$article->mission_id = $mission;
		}

		if (empty($sites)) {
			$site = $modelArt->escape($site);
			$article->siteid = $site;
			$article->from_mode = 'S';
			$article->id = $modelArt->insert('xxt_article', $article, true);
			/* 记录操作日志 */
			$article->type = 'article';
			$modelLog->matterOp($site, $user, $article, 'C');

			/* 记录和任务的关系 */
			if (!empty($mission)) {
				$modelMis = $this->model('matter\mission');
				$modelMis->addMatter($user, $site, $mission, $article);
			}

		} else {
			if ($mode === 'D') {
				$article->from_mode = 'D';
			} else {
				$article->from_mode = 'C';
			}
			foreach ($sites as $site2) {
				$siteid = $modelArt->escape($site2->siteid);
				$article->siteid = $siteid;
				if ($copied->siteid === $siteid) {
					continue;
				}
				if (isset($article->type)) {
					unset($article->type);
				}
				if (isset($article->id)) {
					unset($article->id);
				}

				$article->id = $modelArt->insert('xxt_article', $article, true);

				/* 记录操作日志 */
				$article->type = 'article';
				$modelLog->matterOp($siteid, $user, $article, 'C');
				/* 增加原图文的复制数 */
				if ($site !== $siteid) {
					$modelArt->update("update xxt_article set copy_num = copy_num +1 where id = $id");
				}
			}
		}

		return new \ResponseData($article);
	}
	/**
	 * 更新单图文的字段
	 *
	 * @param int $id article's id
	 */
	public function update_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$oArticle = $this->model('matter\article')->byId($id, ['fields' => 'from_mode,siteid,id,mission_id,title,summary,pic']);
		if ($oArticle === false) {
			return new \ObjectNotFoundError();
		}

		$nv = (array) $this->getPostJson();
		isset($nv['title']) && $nv['title'] = $model->escape($nv['title']);
		isset($nv['summary']) && $nv['summary'] = $model->escape($nv['summary']);
		isset($nv['author']) && $nv['author'] = $model->escape($nv['author']);
		isset($nv['body']) && $nv['body'] = $model->escape(urldecode($nv['body']));
		if ($oArticle->from_mode === 'C') {
			if (isset($nv['body'])) {
				unset($nv['body']);
			}
			if (isset($nv['author'])) {
				unset($nv['author']);
			}
		}

		$rst = $this->_update($site, $id, $nv);
		if ($rst) {
			// 记录操作日志并更新信息
			isset($nv['title']) && $oArticle->title = $nv['title'];
			isset($nv['summary']) && $oArticle->summary = $nv['summary'];
			isset($nv['pic']) && $oArticle->pic = $nv['pic'];
			// 更新所在项目信息
			if ($oArticle->mission_id) {
				$this->model('matter\mission')->updateMatter($oArticle->mission_id, $oArticle);
			}
			// 记录日志
			$this->model('matter\log')->matterOp($site, $oUser, $oArticle, 'U');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 上传单图文到公众号后台
	 */
	public function upload2Mp_action($site, $id, $mediaId = null) {
		$article = $this->model('matter\article')->forWxGroupPush($site, $id);

		if (empty($mediaId)) {
			$rsp = $this->model('mpproxy/wx', $site)->materialAddNews($article);
			if ($rsp[0] === false) {
				return new \ResponseError($rsp[1]);
			}

			$data = array(
				'media_id' => $rsp[1],
				'uploaded_at' => time(),
			);
		} else {
			$article = $article['news']['articles'][0];
			$rsp = $this->model('mpproxy/wx', $site)->materialUpdateNews($mediaId, $article);
			if ($rsp[0] === false) {
				return new \ResponseError($rsp[1]);
			}

			$data = array(
				'uploaded_at' => time(),
			);
		}

		$pmpid = $this->getParentMpid();
		$rst = $this->model()->update(
			'xxt_article',
			$data,
			"(mpid='$site' or mpid='$pmpid') and id='$id'"
		);

		return new \ResponseData($data);
	}
	/**
	 * 上传附件
	 */
	public function upload_action($site, $articleid) {
		if (defined('SAE_TMP_PATH')) {
			$dest = '/article/' . $articleid . '/' . $_POST['resumableFilename'];
			$resumable = $this->model('fs/resumableAliOss', $site, $dest);
			$resumable->handleRequest();
		} else {
			$modelFs = $this->model('fs/local', $site, '_resumable');
			$dest = '/article_' . $articleid . '_' . $_POST['resumableIdentifier'];
			$resumable = $this->model('fs/resumable', $site, $dest, $modelFs);
			$resumable->handleRequest($_POST);
		}
		exit;
	}
	/**
	 * 上传成功后将附件信息保存到数据库中
	 */
	public function attachmentAdd_action($site, $id) {
		$file = $this->getPostJson();

		if (defined('SAE_TMP_PATH')) {
			$url = 'alioss://article/' . $id . '/' . $file->name;
		} else {
			$modelRes = $this->model('fs/local', $site, '_resumable');
			$modelAtt = $this->model('fs/local', $site, '附件');
			$fileUploaded = $modelRes->rootDir . '/article_' . $id . '_' . $file->uniqueIdentifier;
			$fileUploaded2 = $modelAtt->rootDir . '/article_' . $id . '_' . $file->name;
			if (false === rename($fileUploaded, $fileUploaded2)) {
				return new ResponseError('移动上传文件失败');
			}
			$url = 'local://article_' . $id . '_' . $file->name;
		}
		$att = array();
		$att['article_id'] = $id;
		$att['name'] = $file->name;
		$att['type'] = $file->type;
		$att['size'] = $file->size;
		$att['last_modified'] = $file->lastModified;
		$att['url'] = $url;

		$att['id'] = $this->model()->insert('xxt_article_attachment', $att, true);

		$this->model()->update(
			'xxt_article',
			array('has_attachment' => 'Y'),
			"id='$id'"
		);

		return new \ResponseData($att);
	}
	/**
	 * 删除附件
	 */
	public function attachmentDel_action($site, $id) {
		// 附件对象
		$att = $this->model()->query_obj_ss(array('article_id,name,url', 'xxt_article_attachment', "id='$id'"));
		/**
		 * remove from fs
		 */
		if (strpos($att->url, 'alioss') === 0) {
			$fs = $this->model('fs/alioss', $site, 'xxt-attachment');
			$object = $site . '/article/' . $att->article_id . '/' . $att->name;
			$rsp = $fs->delete_object($object);
		} else if (strpos($att->url, 'local') === 0) {
			$fs = $this->model('fs/local', $site, '附件');
			$path = 'article_' . $att->article_id . '_' . $att->name;
			$rsp = $fs->delete($path);
		} else {
			$fs = $this->model('fs/saestore', $site);
			$fs->delete($att->url);
		}
		/**
		 * remove from local
		 */
		$rst = $this->model()->delete('xxt_article_attachment', "id='$id'");
		if ($rst == 1) {
			$q = array(
				'1',
				'xxt_article_attachment',
				"id='$id'",
			);
			$cnt = $this->model()->query_val_ss($q);
			if ($cnt == 0) {
				$this->model()->update(
					'xxt_article',
					array('has_attachment' => 'N'),
					"id='$id'"
				);
			}
		}

		return new \ResponseData($rst);
	}
	/**
	 * 将文件生成的图片转为正文
	 */
	private function setBodyByAtt($articleid, $dir) {
		$body = '';
		$files = scandir($dir);
		$dir = \TMS_MODEL::toUTF8($dir);
		for ($i = 0, $l = count($files) - 2; $i < $l; $i++) {
			$body .= '<p>';
			$body .= '<img src="' . '/' . $dir . '/' . $i . '.jpg">';
			$body .= '</p>';
		}

		$rst = $this->model()->update(
			'xxt_article',
			array('body' => $body),
			"id='$articleid'"
		);

		return $body;
	}
	/**
	 * 将文件生成的图片的第一张设置为头图
	 */
	private function setCoverByAtt($articleid, $dir) {
		$dir = \TMS_MODEL::toUTF8($dir);
		$url = '/' . $dir . '/0.jpg';
		$rst = $this->model()->update(
			'xxt_article',
			array('pic' => $url),
			"id='$articleid'"
		);

		return $url;
	}
	/**
	 * 上传文件并创建图文
	 */
	public function uploadAndCreate_action($site, $state = null) {
		if ($state === 'done') {
			$user = $this->accountUser();
			$posted = $this->getPostJson();
			$file = $posted->file;

			$current = time();
			$filename = str_replace(' ', '_', $file->name);

			/* 生成图文*/
			$article = array();
			$article['siteid'] = $site;
			$article['creater'] = $user->id;
			$article['creater_src'] = $user->src;
			$article['creater_name'] = $user->name;
			$article['create_at'] = $current;
			$article['modifier'] = $user->id;
			$article['modifier_src'] = $user->src;
			$article['modifier_name'] = $user->name;
			$article['modify_at'] = $current;
			$article['title'] = substr($filename, 0, strrpos($filename, '.'));
			$article['author'] = $user->name;
			$article['url'] = '';
			$article['hide_pic'] = 'Y';
			$article['can_picviewer'] = 'Y';
			$article['has_attachment'] = 'Y';
			$article['pic'] = '';
			$article['summary'] = '';
			$article['body'] = '';
			$article['can_siteuser'] = 'Y';
			$id = $this->model()->insert('xxt_article', $article, true);
			/**保存附件*/
			$att = array();
			$att['article_id'] = $id;
			$att['name'] = $filename;
			$att['type'] = $file->type;
			$att['size'] = $file->size;
			$att['last_modified'] = $file->lastModified;
			$att['url'] = 'local://article_' . $id . '_' . $filename;
			$this->model()->insert('xxt_article_attachment', $att, true);
			/* 处理附件 */
			$modelRes = $this->model('fs/local', $site, '_resumable');
			$modelAtt = $this->model('fs/local', $site, '附件');
			$fileUploaded = $modelRes->rootDir . '/article_' . $file->uniqueIdentifier;
			$attachment = $modelAtt->rootDir . '/article_' . $id . '_' . \TMS_MODEL::toLocalEncoding($filename);
			if (false === rename($fileUploaded, $attachment)) {
				return new ResponseError('移动上传文件失败');
			}
			/**获取附件的内容*/
			$appRoot = $_SERVER['DOCUMENT_ROOT'];
			$ext = explode('.', $filename);
			$ext = array_pop($ext);
			$attAbs = $appRoot . '/' . $attachment;
			if (in_array($ext, array('doc', 'docx', 'ppt', 'pptx'))) {
				/* 存放附件转换结果 */
				$attDir = str_replace('.' . $ext, '', $attachment);
				mkdir($appRoot . '/' . $attDir);
				/* 执行转换操作 */
				$output = array();
				$attPng = $appRoot . '/' . $attDir . '/%d.jpg';
				$cmd = $appRoot . '/cus/conv2pdf2img ' . $attAbs . ' ' . $attPng;
				$rsp = exec($cmd, $output, $status);
				if ($status == 1) {
					return new \ResponseError('转换文件失败：' . $rsp);
				}
				$this->setBodyByAtt($id, $attDir);
				if (in_array($ext, array('ppt', 'pptx'))) {
					$this->setCoverByAtt($id, $attDir);
				}
			} else if ($ext === 'pdf') {
				/* 存放附件转换结果 */
				$attDir = str_replace('.' . $ext, '', $attachment);
				mkdir($appRoot . '/' . $attDir);
				$attPng = $appRoot . '/' . $attDir . '/%d.jpg';
				$cmd = $appRoot . '/cus/conv2img ' . $attAbs . ' ' . $attPng;
				$rsp = exec($cmd);
				$this->setBodyByAtt($id, $attDir);
			}
			/*记录操作日志*/
			$matter = (object) $article;
			$matter->id = $id;
			$matter->type = 'article';
			$this->model('matter\log')->matterOp($site, $user, $matter, 'C');

			return new \ResponseData($matter);
		} else {
			/**
			 * 分块上传文件
			 */
			$modelFs = $this->model('fs/local', $site, '_resumable');
			$dest = '/article_' . $_POST['resumableIdentifier'];
			$resumable = $this->model('fs/resumable', $site, $dest, $modelFs);
			$resumable->handleRequest($_POST);
			return new \ResponseData('ok');
		}
	}
	/**
	 * 删除单图文
	 */
	public function remove_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelArt = $this->model('matter\article');
		$article = $modelArt->byId($id, 'id,title,summary,pic,mission_id,creater');
		if ($article->creater !== $user->id) {
			return new \ResponseError('没有删除数据的权限');
		}
		if ($article->mission_id) {
			$this->model('matter\mission')->removeMatter($article->id, 'article');
		}
		$rst = $modelArt->update(
			'xxt_article',
			[
				'state' => 0,
				'modifier' => $user->id,
				'modifier_src' => $user->src,
				'modifier_name' => $user->name,
				'modify_at' => time(),
			],
			["id" => $id]
		);
		if ($rst) {
			/**
			 * 将图文从所属的多图文和频道中删除
			 */
			$modelArt->delete('xxt_channel_matter', "matter_id='$id' and matter_type='article'");
			$modelNews = $this->model('matter\news');
			if ($news = $modelNews->byMatter($id, 'article')) {
				foreach ($news as $n) {
					$modelNews->removeMatter($n->id, $id, 'article');
				}
			}
			/**
			 * 记录操作日志
			 */
			$this->model('matter\log')->matterOp($site, $user, $article, 'Recycle');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 恢复被删除的单图文
	 */
	public function restore_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('matter\article');
		if (false === ($article = $model->byId($id, 'id,title,summary,pic,mission_id'))) {
			return new \ResponseError('数据已经被彻底删除，无法恢复');
		}
		if ($article->mission_id) {
			$modelMis = $this->model('matter\mission');
			$modelMis->addMatter($user, $site, $article->mission_id, $article);
		}

		/* 恢复数据 */
		$rst = $model->update(
			'xxt_article',
			['state' => 1],
			["id" => $article->id]
		);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $user, $article, 'Restore');

		return new \ResponseData($rst);
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
			["id" => $id]
		);
		/*记录操作日志*/
		$article = $this->model('matter\article')->byId($id, 'id,title,summary,pic');
		$article->type = 'article';
		$this->model('matter\log')->matterOp($siteId, $user, $article, 'U');

		return $rst;
	}
}