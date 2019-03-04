<?php
namespace pl\fe\matter;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 素材控制器基类
 */
class base extends \pl\fe\base {
	/**
	 * 获得素材的类型
	 */
	protected function getMatterType() {
		$cls = get_class($this);
		$cls = str_replace('pl\fe\matter\\', '', $cls);
		$cls = explode('\\', $cls);
		if (count($cls) === 2) {
			return $cls[0];
		}
		throw new \Exception();
	}
	/**
	 * 设置访问白名单
	 *
	 * @param int $id 规则ID
	 */
	public function setAcl_action($site, $id = null) {
		if (empty($id)) {
			die('parameters invalid.');
		}

		$acl = $this->getPostJson();
		if (isset($acl->id)) {
			$u['identity'] = $acl->identity;
			empty($acl->idsrc) && $u['label'] = $acl->identity;
			$rst = $this->model()->update('xxt_matter_acl', $u, "id=$acl->id");
			return new \ResponseData($rst);
		} else {
			$i['siteid'] = $site;
			$i['matter_type'] = $this->getMatterType();
			$i['matter_id'] = $id;
			$i['identity'] = $acl->identity;
			$i['idsrc'] = $acl->idsrc;
			$i['label'] = isset($acl->label) ? $acl->label : '';
			$i['id'] = $this->model()->insert('xxt_matter_acl', $i, true);

			return new \ResponseData($i);
		}
	}
	/**
	 * 删除访问控制列表
	 *
	 * @param int $acl 规则ID
	 */
	public function removeAcl_action($site, $acl) {
		$rst = $this->model()->delete(
			'xxt_matter_acl',
			"siteid='$site' and id=$acl"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 素材的阅读日志
	 */
	public function readGet_action($id, $page = 1, $size = 30) {
		$model = $this->model('log');

		$type = $this->getMatterType();

		$reads = $model->getMatterRead($type, $id, $page, $size);

		return new \ResponseData($reads);
	}
	/**
	 * 素材访问控制
	 */
	public function accessControlUser($matterType, $matterId) {
		if (false === ($oUser = $this->accountUser())) {
			return [false, '未登陆'];
		}

		$aOptions = ['cascaded' => 'N', 'fields' => 'siteid,id,title,mission_id'];
		$oMatter = $this->model('matter\\' . $matterType)->byId($matterId, $aOptions);
		if (!$oMatter) {
			return [false, '指定的素材不存在'];
		}

		$siteid = $oMatter->siteid;
		$modelSiteAdmin = $this->model('site\admin');
		$oSiteAdmin = $modelSiteAdmin->byUid($siteid, $oUser->id);
		if ($oSiteAdmin !== false) {
			return [true, $oUser];
		}

		/*检查此素材是否在项目中*/
		if ($matterType !== 'mission' && !empty($oMatter->mission_id)) {
			$mission_id = $oMatter->mission_id;
		} else if ($matterType === 'mission') {
			$mission_id = $matterId;
		}
		if (isset($mission_id)) {
			$oMissionUser = $this->model('matter\mission\acl')->byCoworker($mission_id, $oUser->id, ['fields' => 'id']);
			if ($oMissionUser) {
				return [true, $oUser];
			}
		}

		return [false, '访问控制未通过'];
	}
	/**
	 * 上传附件
	 */
	protected function attachmentUpload($oApp, $data) {
		$dest = '/' . $oApp->type . '/' . $oApp->id . '/' . $data['resumableFilename'];
		$oResumable = $this->model('fs/resumable', $oApp->siteid, $dest, '_attachment');
		$oResumable->handleRequest($data);

		return 'ok';
	}
	/**
	 * 上传成功后将附件信息保存到数据库中
	 */
	protected function attachmentAdd($oApp, $oFile) {
		if (defined('APP_FS_USER') && APP_FS_USER === 'ali-oss') {
			/* 文件存储在阿里 */
			$url = 'alioss://' . $oApp->type . '/' . $oApp->id . '/' . $oFile->name;
		} else {
			/* 文件存储在本地 */
			$modelRes = $this->model('fs/local', $oApp->siteid, '_resumable');
			$modelAtt = $this->model('fs/local', $oApp->siteid, '附件');
			$fileUploaded = $modelRes->rootDir . '/' . $oApp->type . '/' . $oApp->id . '/' . $oFile->name;

			$targetDir = $modelAtt->rootDir . '/' . $oApp->type . '/' . date('Ym');
			if (!file_exists($targetDir)) {
				mkdir($targetDir, 0777, true);
			}
			$fileUploaded2 = $targetDir . '/' . $oApp->id . '_' . $modelApp->toLocalEncoding($oFile->name);
			if (false === rename($fileUploaded, $fileUploaded2)) {
				return [false, '移动上传文件失败'];
			}
			$url = 'local://' . $oApp->type . '/' . date('Ym') . '/' . $oApp->id . '_' . $oFile->name;
		}

		$oAtt = new \stdClass;
		$oAtt->matter_id = $oApp->id;
		$oAtt->matter_type = $oApp->type;
		$oAtt->name = $oFile->name;
		$oAtt->type = $oFile->type;
		$oAtt->size = $oFile->size;
		$oAtt->last_modified = $oFile->lastModified;
		$oAtt->url = $url;

		$oAtt->id = $modelApp->insert('xxt_matter_attachment', $oAtt, true);

		return [true, $oAtt];
	}
	/**
	 * 删除附件
	 */
	protected function attachmentDel($siteId, $attId) {
		$model = $this->model();
		// 附件对象
		$att = $model->query_obj_ss(['matter_id,matter_type,name,url', 'xxt_matter_attachment', "id='$attId'"]);
		if ($att === false) {
			return [false, '未找到附件'];
		}
		/**
		 * remove from fs
		 */
		if (strpos($att->url, 'alioss') === 0) {
			$fs = $this->model('fs/alioss', $site, 'attachment');
			$object = $siteId . '/' . $att->matter_type . '/' . $att->matter_id . '/' . $att->name;
			$rsp = $fs->delete_object($object);
		} else if (strpos($att->url, 'local') === 0) {
			$fs = $this->model('fs/local', $siteId, '附件');
			$path = '' . $att->matter_type . '_' . $att->matter_id . '_' . $att->name;
			$rsp = $fs->delete($path);
		} else {
			$fs = $this->model('fs/saestore', $siteId);
			$fs->delete($att->url);
		}
		/**
		 * remove from local
		 */
		$rst = $model->delete('xxt_matter_attachment', "id='$attId'");
		
		return [true, $rst];
	}
}