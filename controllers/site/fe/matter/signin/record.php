<?php
namespace site\fe\matter\signin;

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
	public function submit_action($site, $app, $submitkey = '') {
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

		$modelApp = $this->model('matter\signin');
		if (false === ($app = $modelApp->byId($app))) {
			header('HTTP/1.0 500 parameter error:app dosen\'t exist.');
			die('活动不存在');
		}
		/**
		 * 提交的数据
		 */
		$user = $this->who;
		$enrollData = $this->getPostJson();
		/**
		 * 包含用户身份信息
		 */
		if (isset($enrollData->member) && isset($enrollData->member->schema_id)) {
			$member = clone $enrollData->member;
			$rst = $this->_submitMember($site, $member, $user);
			if ($rst[0] === false) {
				return new \ParameterError($rst[1]);
			}
		}
		/**
		 * 提交数据
		 */
		$modelRec = $this->model('matter\signin\record');
		$signState = $modelRec->signin($user, $site, $app);
		if ($signState->enrolled) {
			/* 已经登记，更新原先提交的数据 */
			$modelRec->update('xxt_signin_record',
				array('enroll_at' => time()),
				"enroll_key='{$signState->ek}'"
			);
		}
		/**
		 * 检查签到数据是否在报名表中
		 */
		if (isset($app->enroll_app_id)) {
			/*获得要检查的数据*/
			$dataSchemas = json_decode($app->data_schemas);
			$requireCheckedData = new \stdClass;
			foreach ($dataSchemas as $dataSchema) {
				if (isset($dataSchema->requireCheck) && $dataSchema->requireCheck === 'Y') {
					$requireCheckedData->{$dataSchema->id} = $enrollData->{$dataSchema->id};
				}
			}
			/*在指定的登记活动中检查数据*/
			$enrollApp = $this->model('matter\enroll')->byId($app->enroll_app_id);
			if ($enrollApp) {
				$enrollRecord = $this->model('matter\enroll\record')->byData($site, $enrollApp, $requireCheckedData);
				if (empty($enrollRecord)) {
					//return new \ResponseError('提交的数据不在指定的清单中');
					/* 已经登记，更新原先提交的数据 */
					$modelRec->update('xxt_signin_record',
						array('verified' => 'N'),
						"enroll_key='{$signState->ek}'"
					);
				}
			}
		}
		/* 插入提交的数据 */
		$rst = $modelRec->setData($user, $site, $app, $signState->ek, $enrollData, $submitkey);
		if (false === $rst[0]) {
			return new \ResponseError($rst[1]);
		}

		return new \ResponseData($signState->ek);
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
	 * 分段上传文件
	 *
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

		$model = $this->model('matter\signin');
		if (false === ($app = $model->byId($app))) {
			return new \ParameterError("指定的活动（$app）不存在");
		}
		/**
		 * 当前访问用户的基本信息
		 */
		$user = $this->who;
		/* 如果已经有登记记录则不登记 */
		$modelRec = $this->model('matter\signin\record');
		if ($once === 'Y') {
			$ek = $modelRec->userLastKey($user, $site, $app);
		}
		/* 创建登记记录*/
		if (empty($ek)) {
			$ek = $modelRec->enroll($user, $site, $app, time(), (empty($posted->referrer) ? '' : $posted->referrer));
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
		$url = '/rest/site/fe/matter/signin';
		$url .= '?site=' . $site;
		$url .= '&app=' . $app->id;
		$url .= '&ek=' . $ek;

		$rsp = new \stdClass;
		$rsp->url = $url;
		$rsp->ek = $ek;

		return new \ResponseData($rsp);
	}
	/**
	 * 返回指定记录或最后一条记录
	 * @param string $site
	 * @param string $app
	 * @param string $ek
	 */
	public function get_action($site, $app) {
		$modelApp = $this->model('matter\signin');
		$modelRec = $this->model('matter\signin\record');
		$record = null;
		$options = array('cascade' => 'N');
		$app = $modelApp->byId($app, $options);
		/*当前访问用户的基本信息*/
		$user = $this->who;
		/**登记数据*/
		$options = array(
			'fields' => '*',
		);
		if ($record = $modelRec->byUser($user, $site, $app, $options)) {
			$openedek = $record->enroll_key;
			if ($record->enroll_at) {
				$record->data = $modelRec->dataById($openedek);
			}
			/*登记人信息*/
			$record->enroller = $user;
			if (!empty($record->data['member'])) {
				$record->data['member'] = json_decode($record->data['member']);
			} else if (isset($record->data['member'])) {
				$record->data['member'] = new \stdClass;
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

		$app = $this->model('matter\signin')->byId($app);
		$modelRec = $this->model('matter\signin\record');

		$rst = $modelRec->find($site, $app, $options);

		return new \ResponseData($rst);
	}
}