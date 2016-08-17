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
 * 签到活动记录
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
	 * 提交登记数据并签到
	 *
	 * 执行签到，在每个轮次上只能进行一次签到，第一次签到后再提交也不会更改签到时间等信息
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $ek enrollKey 如果要更新之前已经提交的数据，需要指定
	 * @param string $submitkey 支持文件分段上传
	 *
	 */
	public function submit_action($site, $app, $submitkey = '') {
		/* support CORS */
		//header('Access-Control-Allow-Origin:*');
		//header('Access-Control-Allow-Methods:POST');
		//header('Access-Control-Allow-Headers:Content-Type');
		//$_SERVER['REQUEST_METHOD'] === 'OPTIONS' && exit;

		if (empty($site)) {
			header('HTTP/1.0 500 parameter error:site is empty.');
			die('参数错误！');
		}
		if (empty($app)) {
			header('HTTP/1.0 500 parameter error:app is empty.');
			die('参数错误！');
		}

		$modelApp = $this->model('matter\signin');
		if (false === ($signinApp = $modelApp->byId($app))) {
			header('HTTP/1.0 500 parameter error:app dosen\'t exist.');
			die('签到活动不存在');
		}
		/**
		 * 提交的数据
		 */
		$user = $this->who;
		$signinData = $this->getPostJson();
		/**
		 * 包含用户身份信息
		 */
		if (isset($signinData->member) && isset($signinData->member->schema_id)) {
			$member = clone $signinData->member;
			$rst = $this->_submitMember($site, $member, $user);
			if ($rst[0] === false) {
				return new \ParameterError($rst[1]);
			}
		}
		/**
		 * 签到并保存登记的数据
		 */
		$modelRec = $this->model('matter\signin\record');
		$signState = $modelRec->signin($user, $site, $signinApp);
		// 保存签到登记数据
		$rst = $modelRec->setData($user, $site, $signinApp, $signState->ek, $signinData, $submitkey);
		if (false === $rst[0]) {
			return new \ResponseError($rst[1]);
		}
		/**
		 * 检查签到数据是否在报名表中
		 */
		if (isset($signinApp->enroll_app_id)) {
			$enrollApp = $this->model('matter\enroll')->byId($signinApp->enroll_app_id);
			if ($enrollApp) {
				/*获得要检查的数据*/
				$dataSchemas = json_decode($signinApp->data_schemas);
				$requireCheckedData = new \stdClass;
				foreach ($dataSchemas as $dataSchema) {
					if (isset($dataSchema->requireCheck) && $dataSchema->requireCheck === 'Y') {
						$requireCheckedData->{$dataSchema->id} = isset($signinData->{$dataSchema->id}) ? $signinData->{$dataSchema->id} : '';
					}
				}
				if ($signinApp->mission_phase_id) {
					/* 需要匹配项目阶段 */
					$requireCheckedData->phase = $signinApp->mission_phase_id;
				}
				/* 在指定的登记活动中检查数据 */
				$modelEnrollRec = $this->model('matter\enroll\record');
				$enrollRecords = $modelEnrollRec->byData($site, $enrollApp, $requireCheckedData);
				if (!empty($enrollRecords)) {
					/**
					 * 找报名表中找到对应的记录
					 */
					$enrollRecord = $enrollRecords[0];
					if ($enrollRecord->verified === 'Y') {
						$enrollData = $enrollRecords[0]->data;
						foreach ($enrollData as $n => $v) {
							!isset($signinData->{$n}) && $signinData->{$n} = $v;
						}
						// 记录报名数据
						$modelRec->setData($user, $site, $signinApp, $signState->ek, $signinData, $submitkey);
						// 记录验证状态
						$modelRec->update(
							'xxt_signin_record',
							['verified' => 'Y', 'verified_enroll_key' => $enrollRecord->enroll_key],
							"enroll_key='{$signState->ek}'"
						);
						$signState->verified = 'Y';
						// 返回指定的验证成功页
						if (isset($signinApp->entry_rule->success->entry)) {
							$signState->forword = $signinApp->entry_rule->success->entry;
						}
					}
				}
				if (!isset($signState->verified)) {
					/**
					 * 没有在报名表中找到对应的记录
					 */
					$modelRec->update(
						'xxt_signin_record',
						['verified' => 'N', 'verified_enroll_key' => ''],
						"enroll_key='{$signState->ek}'"
					);
					$signState->verified = 'N';
					if (isset($signinApp->entry_rule->fail->entry)) {
						$signState->forword = $signinApp->entry_rule->fail->entry;
					}
				}
			}
		}

		return new \ResponseData($signState);
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
	 * 返回指定记录或最后一条记录
	 *
	 * @param string $site
	 * @param string $app
	 */
	public function get_action($site, $app) {
		$modelApp = $this->model('matter\signin');
		$modelRec = $this->model('matter\signin\record');
		$options = ['cascade' => 'N'];

		$app = $modelApp->byId($app, $options);

		// 当前访问用户的基本信息
		$user = $this->who;

		// 登记数据
		$options = array(
			'fields' => '*',
		);

		$record = $modelRec->byUser($user, $site, $app, $options);

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