<?php
namespace pl\fe\matter\link;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 文章控制器
 */
class attachment extends \pl\fe\matter\base {
	/**
	 * 分段上传附件
	 */
	public function upload_action($site, $linkid) {
		$dest = '/link/' . $linkid . '/' . $_POST['resumableFilename'];
		$oResumable = $this->model('fs/resumable', $site, $dest, '_attachment');
		$oResumable->handleRequest($_POST);

		return new \ResponseData('ok');
	}
	/**
	 * 上传成功后将附件信息保存到数据库中
	 */
	public function add_action($site, $id) {
		$model = $this->model();
		$file = $this->getPostJson();

		if (defined('APP_FS_USER') && APP_FS_USER === 'ali-oss') {
			/*文件存储在阿里*/
			$url = 'alioss://link/' . $id . '/' . $file->name;
		} else {
			/*文件存储在本地*/
			$modelRes = $this->model('fs/local', $site, '_resumable');
			$modelAtt = $this->model('fs/local', $site, '附件');
			$fileUploaded = $modelRes->rootDir . '/link/' . $id . '/' . $file->name;
			$fileUploaded2 = $modelAtt->rootDir . '/link_' . $id . '_' . \TMS_MODEL::toLocalEncoding($file->name);
			if (false === rename($fileUploaded, $fileUploaded2)) {
				return new ResponseError('移动上传文件失败');
			}
			$url = 'local://link_' . $id . '_' . $file->name;
		}
		$oAtt = new \stdClass;
		$oAtt->matter_id = $id;
		$oAtt->matter_type = 'link';
		$oAtt->name = $file->name;
		$oAtt->type = $file->type;
		$oAtt->size = $file->size;
		$oAtt->last_modified = $file->lastModified;
		$oAtt->url = $url;

		$oAtt->id = $model->insert('xxt_matter_attachment', $oAtt, true);

		return new \ResponseData($oAtt);
	}
	/**
	 * 删除附件
	 */
	public function del_action($site, $id) {
		$model = $this->model();
		// 附件对象
		$att = $model->query_obj_ss(['matter_id,name,url', 'xxt_matter_attachment', "id='$id'"]);
		/**
		 * remove from fs
		 */
		if (strpos($att->url, 'alioss') === 0) {
			$fs = $this->model('fs/alioss', $site, 'attachment');
			$object = $site . '/link/' . $att->matter_id . '/' . $att->name;
			$rsp = $fs->delete_object($object);
		} else if (strpos($att->url, 'local') === 0) {
			$fs = $this->model('fs/local', $site, '附件');
			$path = 'link_' . $att->matter_id . '_' . $att->name;
			$rsp = $fs->delete($path);
		} else {
			$fs = $this->model('fs/saestore', $site);
			$fs->delete($att->url);
		}
		/**
		 * remove from local
		 */
		$rst = $model->delete('xxt_matter_attachment', "id='$id'");

		return new \ResponseData($rst);
	}
}