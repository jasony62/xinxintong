<?php
namespace mp\matter;

require_once dirname(__FILE__) . '/matter_ctrl.php';
/**
 *
 */
class resumableAliOss {

	private $mpid;

	private $articleid;

	public function __construct($mpid, $dest) {

		$this->mpid = $mpid;

		$this->dest = $dest;
	}
	/**
	 *
	 * Check if all the parts exist, and
	 * gather all the parts of the file together
	 *
	 * @param string $temp_dir - the temporary directory holding all the parts of the file
	 * @param string $fileName - the original file name
	 * @param string $chunkSize - each chunk size (in bytes)
	 * @param string $totalSize - original file size (in bytes)
	 */
	private function createFileFromChunks($temp_dir, $fileName, $chunkSize, $totalSize) {
		$fs = \TMS_APP::M('fs/saestore', $this->mpid);
		// count all the parts of this file
		$total_files = 0;
		$rst = $fs->getListByPath($temp_dir);
		foreach ($rst['files'] as $file) {
			if (stripos($file['Name'], $fileName) !== false) {
				$total_files++;
			}
		}
		// check that all the parts are present
		// the size of the last part is between chunkSize and 2*$chunkSize
		if ($total_files * $chunkSize >= ($totalSize - $chunkSize + 1)) {
			$fs2 = \TMS_APP::M('fs/alioss', $this->mpid, 'xxt-attachment');
			// create the final destination file
			$tmpfname = tempnam(sys_get_temp_dir(), 'xxt');
			$handle = fopen($tmpfname, "w");
			for ($i = 1; $i <= $total_files; $i++) {
				$content = $fs->read($temp_dir . '/' . $fileName . '.part' . $i);
				fwrite($handle, $content);
				$fs->delete($temp_dir . '/' . $fileName . '.part' . $i);
			}
			fclose($handle);
			//
			$rsp = $fs2->create_mpu_object($this->mpid . $this->dest, $tmpfname);
			echo (json_encode($rsp));
		}
	}
	/**
	 *
	 */
	public function handleRequest() {
		// init the destination file (format <filename.ext>.part<#chunk>
		// the file is stored in a temporary directory
		$temp_dir = $_POST['resumableIdentifier'];
		$dest_file = $temp_dir . '/' . $_POST['resumableFilename'] . '.part' . $_POST['resumableChunkNumber'];
		$content = base64_decode(preg_replace('/data:(.*?)base64\,/', '', $_POST['resumableChunkContent']));
		// move the temporary file
		$fs = \TMS_APP::M('fs/saestore', $this->mpid);
		if (!$fs->write($dest_file, $content)) {
			return array(false, 'Error saving (move_uploaded_file) chunk ' . $_POST['resumableChunkNumber'] . ' for file ' . $_POST['resumableFilename']);
		} else {
			// check if all the parts present, and create the final destination file
			$this->createFileFromChunks($temp_dir, $_POST['resumableFilename'], $_POST['resumableChunkSize'], $_POST['resumableTotalSize']);
			return array(true);
		}
	}
}
/*
 *
 */
class article extends matter_ctrl {
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
		$this->view_action('/mp/matter/article');
	}
	/**
	 * 返回单图文视图
	 */
	public function edit_action() {
		$this->view_action('/mp/matter/article');
	}
	/**
	 *
	 */
	public function read_action() {
		$this->view_action('/mp/matter/article');
	}
	/**
	 *
	 */
	public function stat_action() {
		$this->view_action('/mp/matter/article');
	}
	/**
	 *
	 */
	public function remark_action() {
		$this->view_action('/mp/matter/article');
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
	public function list_action($page = 1, $size = 30) {
		if (!($options = $this->getPostJson())) {
			$options = new \stdClass;
		}

		$uid = \TMS_CLIENT::get_client_uid();
		/**
		 * 单图文来源
		 */
		$mpid = (!empty($options->src) && $options->src === 'p') ? $this->getParentMpid() : $this->mpid;
		/**
		 * select fields
		 */
		$s = "a.id,a.mpid,a.title,a.summary,a.custom_body,a.create_at,a.modify_at,a.approved,a.creater,a.creater_name,a.creater_src,'$uid' uid";
		$s .= ",a.read_num,a.score,a.remark_num,a.share_friend_num,a.share_timeline_num,a.download_num";
		/**
		 * where
		 */
		$w = "a.mpid='$mpid' and a.state=1 and finished='Y'";
		/**
		 * 限作者和管理员
		 */
		if (!$this->model('mp\permission')->isAdmin($mpid, $uid, true)) {
			$fea = $this->model('mp\mpaccount')->getFeature($mpid, 'matter_visible_to_creater');
			if ($fea->matter_visible_to_creater === 'Y') {
				$w .= " and (a.creater='$uid' or a.public_visible='Y')";
			}
		}
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
		} else {
			/**
			 * 按标签过滤
			 */
			$w .= " and a.mpid=at.mpid and a.id=at.res_id";
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

			return new \ResponseData(array('articles' => $articles, 'total' => $total));
		}
		return new \ResponseData(array('articles' => array(), 'total' => 0));
	}
	/**
	 * 获得可见的图文列表
	 *
	 * @param int $id article's id
	 */
	public function get_action($id, $cascade = 'Y') {
		$uid = \TMS_CLIENT::get_client_uid();

		$pmpid = $this->getParentMpid();

		$q = array(
			"a.*,'$uid' uid",
			'xxt_article a',
			"(a.mpid='$this->mpid' or a.mpid='$pmpid') and a.state=1 and a.id=$id",
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
			$article->acl = $this->model('acl')->byMatter($this->mpid, 'article', $id);
			/**
			 * attachments
			 */
			if ($article->has_attachment === 'Y') {
				$article->attachments = $this->model()->query_objs_ss(array('*', 'xxt_article_attachment', "article_id='$id'"));
			}

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
	 */
	public function create_action() {
		/*用户*/
		$user = $this->accountUser();
		$mpa = $this->model('mp\mpaccount')->getFeature($this->mpid, 'heading_pic');
		$current = time();

		$article = array();
		$article['mpid'] = $this->mpid;
		$article['siteid'] = $this->mpid;
		$article['creater'] = $user->id;
		$article['creater_src'] = 'A';
		$article['creater_name'] = $user->name;
		$article['create_at'] = $current;
		$article['modifier'] = $user->id;
		$article['modifier_src'] = 'A';
		$article['modifier_name'] = $user->name;
		$article['modify_at'] = $current;
		$article['title'] = '新单图文';
		$article['author'] = $user->name;
		$article['pic'] = $mpa->heading_pic; //使用账号缺省头图
		$article['hide_pic'] = 'N';
		$article['summary'] = '';
		$article['url'] = '';
		$article['body'] = '';
		$id = $this->model()->insert('xxt_article', $article, true);
		/*记录操作日志*/
		$matter = (object) $article;
		$matter->id = $id;
		$matter->type = 'article';
		$this->model('log')->matterOp($this->mpid, $user, $matter, 'C');

		return new \ResponseData($id);
	}
	/**
	 *
	 * @param int $id mission'is
	 */
	public function createByMission_action($id) {
		$modelMis = $this->model('mission');
		$mission = $modelMis->byId($id);
		$user = $this->accountUser();
		$current = time();

		$article = array();
		$article['mpid'] = $this->mpid;
		$article['creater'] = $user->id;
		$article['creater_src'] = 'A';
		$article['creater_name'] = $user->name;
		$article['create_at'] = $current;
		$article['modifier'] = $user->id;
		$article['modifier_src'] = 'A';
		$article['modifier_name'] = $user->name;
		$article['modify_at'] = $current;
		$article['title'] = '新单图文';
		$article['author'] = $user->name;
		$article['pic'] = $mission->pic; //使用任务的头图
		$article['hide_pic'] = 'N';
		$article['summary'] = $mission->summary;
		$article['url'] = '';
		$article['body'] = '';
		$articleId = $this->model()->insert('xxt_article', $article, true);
		/*记录操作日志*/
		$matter = (object) $article;
		$matter->id = $articleId;
		$matter->type = 'article';
		$this->model('log')->matterOp($this->mpid, $user, $matter, 'C');
		/*记录和任务的关系*/
		$modelMis->addMatter($user, $this->mpid, $id, $matter);

		return new \ResponseData($matter);
	}
	/**
	 * 更新单图文的字段
	 *
	 * $id article's id
	 * $nv pair of name and value
	 */
	public function update_action($id) {
		$nv = (array) $this->getPostJson();

		isset($nv['title']) && $nv['title'] = $this->model()->escape($nv['title']);
		isset($nv['summary']) && $nv['summary'] = $this->model()->escape($nv['summary']);
		isset($nv['author']) && $nv['author'] = $this->model()->escape($nv['author']);
		isset($nv['body']) && $nv['body'] = $this->model()->escape(urldecode($nv['body']));

		$rst = $this->_update($id, $nv);

		return new \ResponseData($rst);
	}
	/**
	 * 上传单图文到公众号后台
	 */
	public function upload2Mp_action($id, $mediaId = null) {
		$article = $this->model('matter\article')->forWxGroupPush($this->mpid, $id);

		if (empty($mediaId)) {
			$rsp = $this->model('mpproxy/wx', $this->mpid)->materialAddNews($article);
			if ($rsp[0] === false) {
				return new \ResponseError($rsp[1]);
			}

			$data = array(
				'media_id' => $rsp[1],
				'uploaded_at' => time(),
			);
		} else {
			$article = $article['news']['articles'][0];
			$rsp = $this->model('mpproxy/wx', $this->mpid)->materialUpdateNews($mediaId, $article);
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
			"(mpid='$this->mpid' or mpid='$pmpid') and id='$id'"
		);

		return new \ResponseData($data);
	}
	/**
	 * 上传附件
	 */
	public function upload_action($articleid) {
		if (defined('SAE_TMP_PATH')) {
			$dest = '/article/' . $articleid . '/' . $_POST['resumableFilename'];
			$resumable = new resumableAliOss($this->mpid, $dest);
			$resumable->handleRequest();
		} else {
			$modelFs = $this->model('fs/local', $this->mpid, '_resumable');
			$dest = '/article_' . $articleid . '_' . $_POST['resumableIdentifier'];
			$resumable = $this->model('fs/resumable', $this->mpid, $dest, $modelFs);
			$resumable->handleRequest($_POST);
		}
		exit;
	}
	/**
	 * 上传成功后将附件信息保存到数据库中
	 */
	public function attachmentAdd_action($id) {
		$file = $this->getPostJson();

		if (defined('SAE_TMP_PATH')) {
			$url = 'alioss://article/' . $id . '/' . $file->name;
		} else {
			$modelRes = $this->model('fs/local', $this->mpid, '_resumable');
			$modelAtt = $this->model('fs/local', $this->mpid, '附件');
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
	public function attachmentDel_action($id) {
		// 附件对象
		$att = $this->model()->query_obj_ss(array('article_id,name,url', 'xxt_article_attachment', "id='$id'"));
		/**
		 * remove from fs
		 */
		if (strpos($att->url, 'alioss') === 0) {
			$fs = $this->model('fs/alioss', $this->mpid, 'xxt-attachment');
			$object = $this->mpid . '/article/' . $att->article_id . '/' . $att->name;
			$rsp = $fs->delete_object($object);
		} else if (strpos($att->url, 'local') === 0) {
			$fs = $this->model('fs/local', $this->mpid, '附件');
			$path = 'article_' . $att->article_id . '_' . $att->name;
			$rsp = $fs->delete($path);
		} else {
			$fs = $this->model('fs/saestore', $this->mpid);
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
	public function uploadAndCreate_action($state = null) {
		if ($state === 'done') {
			$user = $this->accountUser();
			$posted = $this->getPostJson();
			$file = $posted->file;

			$current = time();
			$filename = str_replace(' ', '_', $file->name);

			/* 生成图文*/
			$article = array();
			$article['mpid'] = $this->mpid;
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
			$modelRes = $this->model('fs/local', $this->mpid, '_resumable');
			$modelAtt = $this->model('fs/local', $this->mpid, '附件');
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
			$this->model('log')->matterOp($this->mpid, $user, $matter, 'C');

			return new \ResponseData($id);
		} else {
			/**
			 * 分块上传文件
			 */
			$modelFs = $this->model('fs/local', $this->mpid, '_resumable');
			$dest = '/article_' . $_POST['resumableIdentifier'];
			$resumable = $this->model('fs/resumable', $this->mpid, $dest, $modelFs);
			$resumable->handleRequest($_POST);
			return new \ResponseData('ok');
		}
	}
	/**
	 * 删除单图文
	 */
	public function remove_action($id) {
		$pmpid = $this->getParentMpid();
		$model = $this->model();

		$rst = $model->update(
			'xxt_article',
			array('state' => 0, 'modify_at' => time()),
			"(mpid='$this->mpid' or mpid='$pmpid') and id='$id'"
		);
		/** 将图文从所属的多图文和频道中删除 */
		if ($rst) {
			$model->delete('xxt_channel_matter', "matter_id='$id' and matter_type='article'");
			$modelNews = $this->model('matter\news');
			if ($news = $modelNews->byMatter($id, 'article')) {
				foreach ($news as $n) {
					$modelNews->removeMatter($n->id, $id, 'article');
				}
			}
			/*记录操作日志*/
			$user = $this->accountUser();
			$article = $this->model('matter\\' . 'article')->byId($id, 'id,title,summary,pic');
			$article->type = 'article';
			$this->model('log')->matterOp($this->mpid, $user, $article, 'D');
		}

		return new \ResponseData($rst);
	}
	/**
	 * 添加图文的标签
	 */
	public function addTag_action($id) {
		$tags = $this->getPostJson();

		$this->model('tag')->save(
			$this->mpid, $id, 'article', 0, $tags, null);

		return new \ResponseData('success');
	}
	/**
	 * 添加图文的标签
	 */
	public function addTag2_action($id) {
		$tags = $this->getPostJson();

		$this->model('tag')->save(
			$this->mpid, $id, 'article', 1, $tags, null
		);

		return new \ResponseData('success');
	}
	/**
	 * 删除图文的标签
	 */
	public function removeTag_action($id) {
		$tags = $this->getPostJson();

		$this->model('tag')->save(
			$this->mpid, $id, 'article', 0, null, $tags
		);

		return new \ResponseData('success');
	}
	/**
	 * 删除图文的标签
	 */
	public function removeTag2_action($id) {
		$tags = $this->getPostJson();

		$this->model('tag')->save(
			$this->mpid, $id, 'article', 1, null, $tags
		);

		return new \ResponseData('success');
	}
	/**
	 * 用指定的模板替换定制页面内容
	 * @param int $id article'id
	 *
	 */
	public function pageByTemplate_action($id, $template) {
		$uid = \TMS_CLIENT::get_client_uid();

		$modelTemplate = $this->model('shop\shelf');
		$template = $modelTemplate->byId($template);

		$modelArt = $this->model('matter\article');
		$copied = $modelArt->byId($template->matter_id);
		$target = $modelArt->byId($id);

		$modelPage = $this->model('code\page');
		$pageid = $modelPage->copy($uid, $copied->page_id, $target->page_id);

		if ($target->page_id === 0) {
			$this->_update($id, array('page_id' => $pageid));
		}

		return new \ResponseData($pageid);
	}
	/**
	 * 更新图文信息并记录操作日志
	 */
	private function _update($id, $nv) {
		$user = $this->accountUser();
		$current = time();
		$pmpid = $this->getParentMpid();

		$nv['modifier'] = $user->id;
		$nv['modifier_src'] = $user->src;
		$nv['modifier_name'] = $user->name;
		$nv['modify_at'] = $current;

		$rst = $this->model()->update(
			'xxt_article',
			$nv,
			"(mpid='$this->mpid' or mpid='$pmpid') and id='$id'"
		);
		/*记录操作日志*/
		$article = $this->model('matter\\' . 'article')->byId($id, 'id,title,summary,pic');
		$article->type = 'article';
		$rst = $this->model('log')->matterOp($this->mpid, $user, $article, 'U');

		return $rst;
	}
}