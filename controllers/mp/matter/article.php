<?php
namespace mp\matter;

require_once dirname(__FILE__) . '/matter_ctrl.php';
/**
 * This is the implementation of the server side part of
 * Resumable.js client script, which sends/uploads files
 * to a server in several chunks.
 *
 * The script receives the files in a standard way as if
 * the files were uploaded using standard HTML form (multipart).
 *
 * This PHP script stores all the chunks of a file in a temporary
 * directory (`temp`) with the extension `_part<#ChunkN>`. Once all
 * the parts have been uploaded, a final destination file is
 * being created from all the stored parts (appending one by one).
 *
 * @author Gregory Chris (http://online-php.com)
 * @email www.online.php@gmail.com
 */
class resumable {

	private $mpid;

	private $dest;

	private $modelFs;

	public function __construct($mpid, $dest, $modelFs) {

		$this->mpid = $mpid;

		$this->dest = $dest;

		$this->modelFs = $modelFs;
	}
	/**
	 *
	 * Logging operation
	 *
	 * @param string $str - the logging string
	 */
	private function _log($str) {
	}
	/**
	 *
	 * Delete a directory RECURSIVELY
	 *
	 * @param string $dir - directory path
	 * @link http://php.net/manual/en/function.rmdir.php
	 */
	private function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir . "/" . $object) == "dir") {
						$this->rrmdir($dir . "/" . $object);
					} else {
						unlink($dir . "/" . $object);
					}
				}
			}
			reset($objects);
			rmdir($dir);
		}
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
		// count all the parts of this file
		$total_files = 0;
		foreach (scandir($temp_dir) as $file) {
			if (stripos($file, \TMS_MODEL::toLocalEncoding($fileName)) !== false) {
				$total_files++;
			}
		}
		// check that all the parts are present
		// the size of the last part is between chunkSize and 2*$chunkSize
		if ($total_files * $chunkSize >= ($totalSize - $chunkSize + 1)) {
			// create the final destination file
			if (($fp = fopen($this->modelFs->rootDir . $this->dest, 'w')) !== false) {
				for ($i = 1; $i <= $total_files; $i++) {
					$partname = $temp_dir . '/' . \TMS_MODEL::toLocalEncoding($fileName) . '.part' . $i;
					$content = file_get_contents($partname);
					fwrite($fp, $content);
					$this->_log('writing chunk ' . $i);
				}
				fclose($fp);
			} else {
				$this->_log('cannot create the destination file');
				return false;
			}
			// rename the temporary directory (to avoid access from other
			// concurrent chunks uploads) and than delete it
			if (rename($temp_dir, $temp_dir . '_UNUSED')) {
				$this->rrmdir($temp_dir . '_UNUSED');
			} else {
				$this->rrmdir($temp_dir);
			}
		}
	}
	/**
	 * 处理分段上传的请求
	 */
	public function handleRequest() {
		$filename = str_replace(' ', '_', $_POST['resumableFilename']);
		foreach ($_FILES as $file) {
			// check the error status
			if ($file['error'] != 0) {
				$this->_log('error ' . $file['error'] . ' in file ' . $filename);
				continue;
			}
			// init the destination file (format <filename.ext>.part<#chunk>
			// the file is stored in a temporary directory
			$tmpDir = $_POST['resumableIdentifier'];
			$tmpFile = $filename . '.part' . $_POST['resumableChunkNumber'];
			if (!$this->modelFs->upload($file['tmp_name'], $tmpFile, $tmpDir)) {
				$this->_log('Error saving chunk ' . $_POST['resumableChunkNumber'] . ' for file ' . $filename);
			} else {
				// check if all the parts present, and create the final destination file
				$absTmpDir = $this->modelFs->rootDir . '/' . $tmpDir;
				$this->createFileFromChunks($absTmpDir, $filename, $_POST['resumableChunkSize'], $_POST['resumableTotalSize']);
			}
		}
	}
}
class resumableAliOss {

	private $mpid;

	private $articleid;

	public function __construct($mpid, $articleid) {

		$this->mpid = $mpid;

		$this->articleid = $articleid;
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
			$rsp = $fs2->create_mpu_object($$this->mpid . $this->dest, $tmpfname);
			echo (json_encode($rsp));
		}
	}
	/**
	 *
	 */
	public function handleRequest() {
		// loop through files and move the chunks to a temporarily created directory
		if (!empty($_FILES)) {
			foreach ($_FILES as $file) {
				// check the error status
				if ($file['error'] != 0) {
					return array(false, 'error ' . $file['error'] . ' in file ' . $_POST['resumableFilename']);
				}
				// init the destination file (format <filename.ext>.part<#chunk>
				// the file is stored in a temporary directory
				$temp_dir = $_POST['resumableIdentifier'];
				$dest_file = $temp_dir . '/' . $_POST['resumableFilename'] . '.part' . $_POST['resumableChunkNumber'];
				// move the temporary file
				$fs = \TMS_APP::M('fs/saestore', $this->mpid);
				if (!$fs->upload($dest_file, $file['tmp_name'])) {
					return array(false, 'Error saving (move_uploaded_file) chunk ' . $_POST['resumableChunkNumber'] . ' for file ' . $_POST['resumableFilename']);
				} else {
					// check if all the parts present, and create the final destination file
					$this->createFileFromChunks($temp_dir, $_POST['resumableFilename'], $_POST['resumableChunkSize'], $_POST['resumableTotalSize']);
					return array(true);
				}
			}
		}

	}
}
/*
 *
 */
class article extends matter_ctrl {
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
	public function get_action($id = null, $page = 1, $size = 30) {
		if (!($options = $this->getPostJson())) {
			$options = new \stdClass;
		}

		if ($id) {
			$article = $this->getOne($this->mpid, $id);
			return new \ResponseData($article);
		} else {
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
				$fea = $this->model('mp\mpaccount')->getFeatures($mpid, 'matter_visible_to_creater');
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
			if (empty($options->tag)) {
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
				is_array($options->tag) && $options->tag = implode(',', $options->tag);
				$w .= " and a.mpid=at.mpid and a.id=at.res_id and at.tag_id in($options->tag)";
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
				$amount = (int) $this->model()->query_val_ss($q);
				/**
				 * 获得每个图文的tag
				 */
				foreach ($articles as &$a) {
					$ids[] = $a->id;
					$map[$a->id] = &$a;
				}
				$rels = $this->model('tag')->tagsByRes($ids, 'article');
				foreach ($rels as $aid => &$tags) {
					$map[$aid]->tags = $tags;
				}

				return new \ResponseData(array($articles, $amount));
			}
			return new \ResponseData(array(array(), 0));
		}
	}
	/**
	 * 一个单图文的完整信息
	 */
	private function &getOne($mpid, $id, $cascade = true) {
		$uid = \TMS_CLIENT::get_client_uid();

		$pmpid = $this->getParentMpid();

		$q = array(
			"a.*,'$uid' uid",
			'xxt_article a',
			"(a.mpid='$mpid' or a.mpid='$pmpid') and a.state=1 and a.id=$id",
		);
		if (($article = $this->model()->query_obj_ss($q)) && $cascade === true) {
			/**
			 * channels
			 */
			$article->channels = $this->model('matter\channel')->byMatter($id, 'article');
			/**
			 * tags
			 */
			$article->tags = $this->model('tag')->tagsByRes($article->id, 'article');
			/**
			 * acl
			 */
			$article->acl = $this->model('acl')->byMatter($mpid, 'article', $id);
			/**
			 * attachments
			 */
			if ($article->has_attachment === 'Y') {
				$article->attachments = $this->model()->query_objs_ss(array('*', 'xxt_article_attachment', "article_id='$id'"));
			}

		}

		return $article;
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
		$account = \TMS_CLIENT::account();
		if ($account === false) {
			return new \ResponseError('长时间未操作，请重新登陆！');
		}

		$current = time();
		$d['mpid'] = $this->mpid;
		$d['creater'] = \TMS_CLIENT::get_client_uid();
		$d['creater_src'] = 'A';
		$d['creater_name'] = $account->nickname;
		$d['create_at'] = $current;
		$d['modifier'] = \TMS_CLIENT::get_client_uid();
		$d['modifier_src'] = 'A';
		$d['modifier_name'] = $account->nickname;
		$d['modify_at'] = $current;
		$d['title'] = '新单图文';
		$d['author'] = $d['creater_name'];
		$d['pic'] = ''; // 头图
		$d['hide_pic'] = 'N';
		$d['summary'] = '';
		$d['url'] = ''; // 原文链接
		$d['body'] = '';
		$id = $this->model()->insert('xxt_article', $d);

		return new \ResponseData($id);
	}
	/**
	 * 更新单图文的字段
	 *
	 * $id article's id
	 * $nv pair of name and value
	 */
	public function update_action($id) {
		$account = \TMS_CLIENT::account();
		if ($account === false) {
			return new \ResponseError('长时间未操作，请重新登陆！');
		}

		$pmpid = $this->getParentMpid();

		$nv = (array) $this->getPostJson();

		isset($nv['body']) && $nv['body'] = $this->model()->escape(urldecode($nv['body']));

		$nv['modifier'] = \TMS_CLIENT::get_client_uid();
		$nv['modifier_src'] = 'A';
		$nv['modifier_name'] = $account->nickname;
		$nv['modify_at'] = time();

		$rst = $this->model()->update(
			'xxt_article',
			$nv,
			"(mpid='$this->mpid' or mpid='$pmpid') and id='$id'"
		);

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
		} else {
			$dest = '/article_' . $articleid . '_' . $_POST['resumableFilename'];
			$resumable = new resumable($this->mpid, $dest);
		}
		$resumable->handleRequest();
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
			$account = \TMS_CLIENT::account();
			if ($account === false) {
				return new \ResponseError('长时间未操作，请重新登陆！');
			}

			$posted = $this->getPostJson();
			$file = $posted->file;

			$modelFs = \TMS_APP::M('fs/local', $this->mpid, '_resumable');
			$fileUploaded = $modelFs->rootDir . '/article_' . $file->uniqueIdentifier;

			$current = time();
			$uid = \TMS_CLIENT::get_client_uid();
			$uname = $account->nickname;
			$filename = str_replace(' ', '_', $file->name);

			$d = array();
			$d['mpid'] = $this->mpid;
			$d['creater'] = $uid;
			$d['creater_src'] = 'A';
			$d['creater_name'] = $uname;
			$d['create_at'] = $current;
			$d['modifier'] = $uid;
			$d['modifier_src'] = 'A';
			$d['modifier_name'] = $uname;
			$d['modify_at'] = $current;
			$d['title'] = substr($filename, 0, strrpos($filename, '.'));
			$d['author'] = $uname;
			$d['url'] = '';
			$d['hide_pic'] = 'Y';
			$d['can_picviewer'] = 'Y';
			$d['has_attachment'] = 'Y';
			$d['pic'] = '';
			$d['summary'] = '';
			$d['body'] = '';

			$id = $this->model()->insert('xxt_article', $d, true);
			/**
			 * 保存附件
			 */
			$att = array();
			$att['article_id'] = $id;
			$att['name'] = $filename;
			$att['type'] = $file->type;
			$att['size'] = $file->size;
			$att['last_modified'] = $file->lastModified;
			$att['url'] = 'local://article_' . $id . '_' . $filename;

			$this->model()->insert('xxt_article_attachment', $att, true);

			$modelFs = \TMS_APP::M('fs/local', $this->mpid, '附件');
			$attachment = $modelFs->rootDir . '/article_' . $id . '_' . \TMS_MODEL::toLocalEncoding($filename);
			rename($fileUploaded, $attachment);
			/**
			 * 获取附件的内容
			 */
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

			return new \ResponseData($id);
		} else {
			/**
			 * 分块上传文件
			 */
			$modelFs = \TMS_APP::M('fs/local', $this->mpid, '_resumable');
			$dest = '/article_' . $_POST['resumableIdentifier'];
			$resumable = new resumable($this->mpid, $dest, $modelFs);
			$resumable->handleRequest();
			exit;
		}
	}
	/**
	 * 删除一个单图文
	 */
	public function remove_action($id) {
		$pmpid = $this->getParentMpid();

		$model = $this->model();

		$rst = $model->update(
			'xxt_article',
			array('state' => 0, 'modify_at' => time()),
			"(mpid='$this->mpid' or mpid='$pmpid') and id='$id'");
		/**
		 * 将图文从所属的多图文和频道中删除
		 */
		if ($rst) {
			$model->delete('xxt_channel_matter', "matter_id='$id' and matter_type='article'");
			$modelNews = $this->model('matter\news');
			if ($news = $modelNews->byMatter($id, 'article')) {
				foreach ($news as $n) {
					$modelNews->removeMatter($n->id, $id, 'article');
				}
			}
		}

		return new \ResponseData($rst);
	}
	/**
	 * 添加图文的标签
	 */
	public function addTag_action($id) {
		$tags = $this->getPostJson();

		$this->model('tag')->save(
			$this->mpid, $id, 'article', $tags, null);

		return new \ResponseData('success');
	}
	/**
	 * 删除图文的标签
	 */
	public function removeTag_action($id) {
		$tags = $this->getPostJson();

		$this->model('tag')->save(
			$this->mpid, $id, 'article', null, $tags
		);

		return new \ResponseData('success');
	}
	/**
	 *
	 */
	protected function getMatterType() {
		return 'article';
	}
}
