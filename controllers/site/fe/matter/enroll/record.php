<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 *
 */
class resumableAliOss {

	private $site;

	public function __construct($site, $dest, $domain = '_user') {

		$this->siteId = $site;

		$this->dest = $dest;

		$this->domain = $domain;
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
		/*检查文件是否都已经上传*/
		$fs = \TMS_APP::M('fs/saestore', $this->siteId);
		$total_files = 0;
		$rst = $fs->getListByPath($temp_dir);
		foreach ($rst['files'] as $file) {
			if (stripos($file['Name'], $fileName) !== false) {
				$total_files++;
			}
		}
		/*如果都已经上传，合并分块文件*/
		if ($total_files * $chunkSize >= ($totalSize - $chunkSize + 1)) {
			$fsAli = \TMS_APP::M('fs/alioss', $this->siteId, 'xinxintong', $this->domain);
			// 合并后的临时文件
			if (defined('SAE_TMP_PATH')) {
				$tmpfname = tempnam(SAE_TMP_PATH, 'xxt');
			} else {
				$tmpfname = tempnam(sys_get_temp_dir(), 'xxt');
			}
			$handle = fopen($tmpfname, "w");
			for ($i = 1; $i <= $total_files; $i++) {
				$content = $fs->read($temp_dir . '/' . $fileName . '.part' . $i);
				fwrite($handle, $content);
				$fs->delete($temp_dir . '/' . $fileName . '.part' . $i);
			}
			fclose($handle);
			/*将文件上传到alioss*/
			$aliURL = $fsAli->getRootDir() . $this->dest;
			$rsp = $fsAli->create_mpu_object($aliURL, $tmpfname);
			echo (json_encode($rsp));
		}
	}
	/**
	 * 将接收到的分块数据存储在sae的存储中
	 * 检查是否所有的分块数据都已经上传完成
	 */
	public function handleRequest() {
		$temp_dir = $_POST['resumableIdentifier'];
		$dest_file = $temp_dir . '/' . $_POST['resumableFilename'] . '.part' . $_POST['resumableChunkNumber'];
		$content = base64_decode(preg_replace('/data:(.*?)base64\,/', '', $_POST['resumableChunkContent']));
		$fsSae = \TMS_APP::M('fs/saestore', $this->siteId);
		if (!$fsSae->write($dest_file, $content)) {
			return array(false, 'Error saving (move_uploaded_file) chunk ' . $_POST['resumableChunkNumber'] . ' for file ' . $_POST['resumableFilename']);
		} else {
			$this->createFileFromChunks($temp_dir, $_POST['resumableFilename'], $_POST['resumableChunkSize'], $_POST['resumableTotalSize']);
			return array(true);
		}
	}
}
/**
 * 登记活动记录
 */
class record extends base {
	/**
	 * 解决跨域异步提交问题
	 */
	public function submitkeyGet_action() {
		/* support CORS */
		header('Access-Control-Allow-Origin:*');
		$key = md5(uniqid() . mt_rand());

		return new \ResponseData($key);
	}
	/**
	 * 报名登记页，记录登记信息
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $ek enrollKey 如果要更新之前已经提交的数据，需要指定
	 * @param string $submitkey 支持文件分段上传
	 */
	public function submit_action($site, $app, $ek = null, $submitkey = '') {
		/* support CORS */
		header('Access-Control-Allow-Origin:*');
		header('Access-Control-Allow-Methods:POST');
		header('Access-Control-Allow-Headers:Content-Type');
		$_SERVER['REQUEST_METHOD'] === 'OPTIONS' && exit;

		if (empty($site)) {
			header('HTTP/1.0 500 parameter error:site is empty.');
			die('参数错误！');
		}
		if (empty($app)) {
			header('HTTP/1.0 500 parameter error:app is empty.');
			die('参数错误！');
		}

		$modelApp = $this->model('matter\enroll');
		if (false === ($app = $modelApp->byId($app))) {
			header('HTTP/1.0 500 parameter error:app dosen\'t exist.');
			die('活动不存在');
		}
		/**
		 * 当前访问用户的基本信息
		 */
		$user = $this->who;
		/**
		 * 当前用户是否可以进行提交操作
		 */
		//$this->checkActionRule($site, $app, $user);
		/**
		 * 处理提交数据
		 */
		$posted = $this->getPostJson();
		/**
		 * 包含用户身份信息
		 */
		if (isset($posted->member) && isset($posted->member->schema_id)) {
			$member = clone $posted->member;
			$rst = $this->_submitMember($site, $member, $user);
			if ($rst[0] === false) {
				return new \ParameterError($rst[1]);
			}
		}
		/**
		 * 处理提交数据
		 */
		if (empty($ek)) {
			/*插入登记数据*/
			$modelRec = $this->model('matter\enroll\record');
			$ek = $modelRec->enroll($site, $app, $user);
			/*处理自定义信息*/
			$rst = $modelRec->setData($user, $site, $app, $ek, $posted, $submitkey);
			/*登记提交的积分奖励*/
			$modelCoin = $this->model('coin\log');
			$action = 'app.enroll,' . $app->id . '.record.submit';
			$modelCoin->income($site, $action, $app->id, 'sys', $user->uid);
		} else {
			$modelRec = $this->model('matter\enroll\record');
			/* 已经登记，更新原先提交的数据 */
			$modelRec->update('xxt_enroll_record',
				array('enroll_at' => time()),
				"enroll_key='$ek'"
			);
			/* 重新插入新提交的数据 */
			$rst = $modelRec->setData($user, $site, $app, $ek, $posted, $submitkey);
		}
		if (false === $rst[0]) {
			return new \ResponseError($rst[1]);
		}
		/**
		 * 通知登记活动的管理员
		 */
		//!empty($app->receiver_page) && $this->_notifyAdmin($site, $app, $ek, $user);

		return new \ResponseData($ek);
	}
	/**
	 * 提交信息中包含的自定义用户信息
	 */
	private function _submitMember($siteId, &$member, &$user) {
		$schemaId = $member->schema_id;
		$schema = $this->model('site\user\memberschema')->byId($schemaId, 'attr_mobile,attr_email,attr_name,extattr');
		$modelMem = $this->model('site\user\member');

		$existentMember = $modelMem->byUser($siteId, $user->uid, array('schemas' => $schemaId));
		if (count($existentMember)) {
			$memberId = $existentMember[0]->id;
			$member->id = $memberId;
			$rst = $modelMem->modify($siteId, $schema, $memberId, $member);
		} else {
			$rst = $modelMem->createByApp($siteId, $schema, $user->uid, $member);
		}
		$member->schema_id = $schemaId;

		return $rst;
	}
	/**
	 * 通知活动管理员
	 *
	 * @todo 应该改为模版消息
	 */
	private function _notifyAdmin($site, $app, $ek, $user) {
		$admins = \TMS_APP::model('acl')->enrollReceivers($site, $app->id);
		if (false !== ($key = array_search($user->openid, $admins))) {
			/* 管理员是登记人，不再通知 */
			unset($admins[$key]);
		}
		if (!empty($admins)) {
			$mpa = $this->model('mp\mpaccount')->byId($site, 'mpsrc');
			$url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/enroll?mpid=$site&aid=$app->id&ek=$ek&page=$app->receiver_page";
			$txt = "【" . $app->title . "】有新登记数据，";
			if ($mpa->mpsrc === 'yx') {
				$txt .= '<a href="$url">';
			} else {
				$txt .= "<a href='$url'>";
			}
			$txt .= "请处理";
			$txt .= "</a>";
			$message = array(
				"msgtype" => "text",
				"text" => array(
					"content" => $txt,
				),
			);
			foreach ($admins as $admin) {
				$this->sendByOpenid($site, $admin, $message);
			}
		}

		return true;
	}
	/**
	 * 分段上传文件
	 * @param string $site
	 * @param string $app
	 * @param string $submitKey
	 */
	public function uploadFile_action($site, $app, $submitkey = '') {
		/* support CORS */
		header('Access-Control-Allow-Origin:*');
		header('Access-Control-Allow-Methods:POST');
		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			exit;
		}
		if (empty($submitkey)) {
			$user = $this->getUser($site);
			$submitkey = $user->vid;
		}
		/** 分块上传文件 */
		if (defined('SAE_TMP_PATH')) {
			$dest = '/' . $app . '/' . $submitkey . '_' . $_POST['resumableFilename'];
			$resumable = new resumableAliOss($site, $dest);
			$resumable->handleRequest();
		} else {
			$modelFs = \TMS_APP::M('fs/local', $site, '_resumable');
			$dest = $submitkey . '_' . $_POST['resumableIdentifier'];
			$resumable = \TMS_APP::M('fs/resumable', $site, $dest, $modelFs);
			$resumable->handleRequest($_POST);
		}

		return new \ResponseData('ok');
	}
	/**
	 * 给当前用户产生一条空的登记记录，记录传递的数据，并返回这条记录
	 * 适用于抽奖后记录兑奖信息
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $once 如果已经有登记记录，不生成新的登记记录
	 */
	public function emptyGet_action($site, $app, $once = 'N') {
		$posted = $this->getPostJson();

		$model = $this->model('matter\enroll');
		if (false === ($app = $model->byId($app))) {
			return new \ParameterError("指定的活动（$app）不存在");
		}
		/**
		 * 当前访问用户的基本信息
		 */
		$user = $this->who;
		/* 如果已经有登记记录则不登记 */
		$modelRec = $this->model('matter\enroll\record');
		if ($once === 'Y') {
			$ek = $modelRec->getLastKey($site, $app, $user);
		}
		/* 创建登记记录*/
		if (empty($ek)) {
			$ek = $modelRec->enroll($site, $app, $user, time(), (empty($posted->referrer) ? '' : $posted->referrer));
			/**
			 * 处理提交数据
			 */
			$data = $_GET;
			unset($data['site']);
			unset($data['app']);
			if (!empty($data)) {
				$data = (object) $data;
				$rst = $modelRec->setData($user, $site, $app, $ek, $data);
				if (false === $rst[0]) {
					return new ResponseError($rst[1]);
				}
			}
		}
		/*登记记录的URL*/
		$url = '/rest/site/fe/matter/enroll';
		$url .= '?site=' . $site;
		$url .= '&app=' . $app->id;
		$url .= '&ek=' . $ek;

		$rsp = new \stdClass;
		$rsp->url = $url;
		$rsp->ek = $ek;

		return new \ResponseData($rsp);
	}
	/**
	 * 发出邀请
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $invitee
	 * @param string $page
	 */
	public function inviteSend_action($site, $app, $ek, $invitee, $page = '') {
		/*获得被邀请人的信息*/
		$options = array('fields' => 'openid');
		$members = $this->model('user/member')->search($site, $invitee, $options);
		if (empty($members)) {
			return new \ResponseError("指定的用户不存在");
		}
		$openid = $members[0]->openid;

		/*给邀请人发消息*/
		$message = \TMS_APP::M('matter\enroll')->forCustomPush($site, $app);
		$url = $message['news']['articles'][0]['url'];
		$url .= "&ek=$ek";
		!empty($page) && $url .= "&page=$page";
		$message['news']['articles'][0]['url'] = $url;
		$rst = $this->sendByOpenid($site, $openid, $message);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		return new \ResponseData($members);
	}
	/**
	 * 记录参加登记活动的用户之间的邀请关系
	 * 邀请必须依赖于某条已经存在的登记记录
	 *
	 * $param inviter enroll_key
	 */
	public function acceptInvite_action($site, $app, $inviter, $state = '1') {
		$model = $this->model('app\enroll');
		if (false === ($app = $model->byId($app))) {
			return new \ParameterError("指定的活动（$app）不存在");
		}
		/* 当前访问用户的基本信息 */
		$user = $this->getUser($site,
			array(
				'authapis' => $app->authapis,
				'matter' => $app,
				'verbose' => array('member' => 'Y', 'fan' => 'Y'),
			)
		);
		/* 如果已经有登记记录则不登记 */
		$modelRec = $this->model('app\enroll\record');
		if ($state === '1') {
			$ek = $modelRec->getLastKey($site, $app, $user);
			if (!empty($ek)) {
				$rsp = new \stdClass;
				$rsp->ek = $ek;
				return new \ResponseData($rsp);
			}
		} else {
			$ek = $modelRec->hasAcceptedInvite($app, $user->openid, $inviter);
		}
		if (false === $ek) {
			/* 创建登记记录*/
			$ek = $modelRec->add($site, $app, $user, 'ek:' . $inviter);
			if ($state !== '1') {
				/*不作为独立的记录，只是接收邀请的日志*/
				$modelRec->modify($ek, array('state' => 2));
			}
			/** 处理提交数据 */
			$data = $_GET;
			unset($data['site']);
			unset($data['app']);
			if (!empty($data)) {
				$data = (object) $data;
				$rst = $modelRec->setData($user, $site, $app, $ek, $data);
				if (false === $rst[0]) {
					return new ResponseError($rst[1]);
				}
			}
			/*记录邀请数*/
			$modelRec->update("update xxt_enroll_record set follower_num=follower_num+1 where enroll_key='$inviter'");
			/*邀请成功的积分奖励*/
			$inviteRecord = $modelRec->byId($inviter, array('cascaded' => 'N', 'fields' => 'openid'));
			$modelCoin = $this->model('coin\log');
			$action = 'app.enroll,' . $app . '.invite.success';
			$modelCoin->income($site, $action, $app, 'sys', $inviteRecord->openid);
		}
		$rsp = new \stdClass;
		$rsp->ek = $ek;

		return new \ResponseData($rsp);
	}
	/**
	 * 返回指定记录或最后一条记录
	 * @param string $site
	 * @param string $app
	 * @param string $ek
	 */
	public function get_action($site, $app, $ek = '') {
		$modelApp = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');
		$openedek = $ek;
		$record = null;
		$options = array('cascaded' => 'N');
		$app = $modelApp->byId($app, $options);
		/*当前访问用户的基本信息*/
		$user = $this->who;
		/**登记数据*/
		if (empty($openedek)) {
			/*获得最后一条登记数据。登记记录有可能未进行过登记*/
			$options = array(
				'fields' => '*',
			);
			$record = $modelRec->getLast($site, $app, $user, $options);
			if ($record) {
				$openedek = $record->enroll_key;
				if ($record->enroll_at) {
					$record->data = $modelRec->dataById($openedek);
				}
			}
		} else {
			/*打开指定的登记记录*/
			$record = $modelRec->byId($openedek);
		}
		/**互动数据*/
		if (!empty($openedek)) {
			/*登记人信息*/
			$record->enroller = $user;
			if (!empty($record->data['member'])) {
				$record->data['member'] = json_decode($record->data['member']);
			} else if (isset($record->data['member'])) {
				$record->data['member'] = new \stdClass;
			}
			/*评论数据*/
			if ($app->can_like_record === 'Y') {
				$record->likers = $modelRec->likers($openedek);
			}
			/*评论数据*/
			if ($app->can_remark_record === 'Y') {
				$record->remarks = $modelRec->remarks($openedek);
			}
			/*获得关联抽奖活动记录*/
			$ql = array(
				'award_title',
				'xxt_lottery_log',
				"enroll_key='$openedek'",
			);
			$lotteryResult = $this->model()->query_objs_ss($ql);
			if (!empty($lotteryResult)) {
				$lrs = array();
				foreach ($lotteryResult as $lr) {
					$lrs[] = $lr->award_title;
				}
				$record->data['lotteryResult'] = implode(',', $lrs);
			}
		}

		return new \ResponseData($record);
	}
	/**
	 * 列出所有的登记记录
	 *
	 * $site
	 * $app
	 * $orderby time|remark|score|follower
	 * $openid
	 * $page
	 * $size
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 *
	 */
	public function list_action($site, $app, $owner = 'U', $rid = '', $orderby = 'time', $openid = null, $page = 1, $size = 30) {
		$user = $this->who;
		switch ($owner) {
		case 'I':
			$options = array(
				'inviter' => $user->uid,
			);
			break;
		case 'A':
			$options = array();
			break;
		default:
			$options = array(
				'creater' => $user->uid,
			);
			break;
		}
		$options['rid'] = $rid;
		$options['page'] = $page;
		$options['size'] = $size;
		$options['orderby'] = $orderby;

		$app = $this->model('matter\enroll')->byId($app);
		$modelRec = $this->model('matter\enroll\record');

		$rst = $modelRec->find($site, $app, $options);

		return new \ResponseData($rst);
	}
	/**
	 * 登记记录点赞
	 *
	 * $site
	 * $ek
	 */
	public function score_action($site, $ek) {
		/** 当前用户 */
		$user = $this->getUser($site);
		$openid = $user->openid;

		$modelRec = $this->model('app\enroll\record');
		if ($modelRec->hasScored($openid, $ek)) {
			/**
			 * 点过赞，再次点击，取消赞
			 */
			$this->model()->delete(
				'xxt_enroll_record_score',
				"enroll_key='$ek' and openid='$openid'"
			);
			$myScore = 0;
		} else {
			/**
			 * 点赞
			 */
			$i = array(
				'openid' => $openid,
				'nickname' => $user->nickname,
				'enroll_key' => $ek,
				'create_at' => time(),
				'score' => 1,
			);
			$this->model()->insert('xxt_enroll_record_score', $i, false);
			$myScore = 1;
		}
		/**
		 * 获得点赞的总数
		 */
		$score = $modelRec->score($ek);
		$this->model()->update('xxt_enroll_record', array('score' => $score), "enroll_key='$ek'");

		return new \ResponseData(array('myScore' => $myScore, 'score' => $score));
	}
	/**
	 * 返回对指定记录点赞的人
	 * @param string $site
	 * @param string $ek
	 */
	public function likerList_action($site, $ek, $page = 1, $size = 10) {
		$likers = $this->model('app\enroll\record')->likers($ek);

		return new \ResponseData(array('likers' => $likers));
	}
	/**
	 * 针对登记记录发表评论
	 *
	 * $site
	 * $ek
	 */
	public function remark_action($site, $ek) {
		$data = $this->getPostJson();
		if (empty($data->remark)) {
			return new \ResponseError('评论不允许为空！');
		}

		$modelEnroll = $this->model('app\enroll');
		/**
		 * 当前活动
		 */
		$q = array('aid,openid', 'xxt_enroll_record', "enroll_key='$ek'");
		$record = $this->model()->query_obj_ss($q);
		$app = $record->aid;
		$app = $modelEnroll->byId($app);
		/**
		 * 发表评论的用户
		 */
		$user = $this->getUser($site);
		if (empty($user->openid)) {
			return new \ResponseError('无法获得用户身份标识');
		}

		$remark = array(
			'openid' => $user->openid,
			'nickname' => $user->nickname,
			'enroll_key' => $ek,
			'create_at' => time(),
			'remark' => $this->model()->escape($data->remark),
		);
		$remark['id'] = $this->model()->insert('xxt_enroll_record_remark', $remark, true);
		$remark['nickname'] = $user->nickname;
		$this->model()->update("update xxt_enroll_record set remark_num=remark_num+1 where enroll_key='$ek'");
		/**
		 * 通知登记人有评论
		 */
		if ($app->remark_notice === 'Y' && !empty($app->remark_notice_page)) {
			$apis = $this->model('mp\mpaccount')->getApis($site);
			if ($apis && $apis->{$apis->mpsrc . '_custom_push'} === 'Y') {
				/**
				 * 发送评论提醒
				 */
				$url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/enroll?mpid=$site&aid=$app&ek=$ek&page=$app->remark_notice_page";
				$text = urlencode($remark['nickname'] . '对【');
				$text .= '<a href="' . $url . '">';
				$text .= urlencode($app->title);
				$text .= '</a>';
				$text .= urlencode('】发表了评论：' . $remark['remark']);
				$message = array(
					"msgtype" => "text",
					"text" => array(
						"content" => $text,
					),
				);
				/**
				 * 通知登记人
				 */
				if ($this->model('log')->canReceivePush($site, $record->openid)) {
					if ($record->openid !== $user->openid) {
						$this->sendByOpenid($site, $record->openid, $message);
					}
				}
				/**
				 * 通知其他发表了评论的用户
				 */
				$modelRec = $this->model('app\enroll\record');
				$others = $modelRec->remarkers($ek);
				foreach ($others as $other) {
					if ($other->openid === $record->openid || $other->openid === $remarker->openid) {
						continue;
					}
					$this->sendByOpenid($site, $other->openid, $message);
				}
			}
		}

		return new \ResponseData($remark);
	}
	/**
	 * 删除当前记录
	 *
	 * @param string $site
	 * @param string $app
	 */
	public function remove_action($site, $app, $ek) {
		$modelRec = $this->model('matter\enroll\record');

		$rst = $modelRec->removeByUser($site, $app, $ek);

		return new \ResponseData($rst);
	}
}