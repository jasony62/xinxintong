<?php
namespace pl\fe\matter\article;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 文章控制器
 */
class attachment extends \pl\fe\matter\base {
	/**
	 * 分段上传附件
	 */
	public function upload_action($site, $articleid) {
		$dest = '/article/' . $articleid . '/' . $_POST['resumableFilename'];
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
			$url = 'alioss://article/' . $id . '/' . $file->name;
		} else {
			/*文件存储在本地*/
			$modelRes = $this->model('fs/local', $site, '_resumable');
			$modelAtt = $this->model('fs/local', $site, '附件');
			$fileUploaded = $modelRes->rootDir . '/article/' . $id . '/' . $file->name;
			$fileUploaded2 = $modelAtt->rootDir . '/article_' . $id . '_' . \TMS_MODEL::toLocalEncoding($file->name);
			if (false === rename($fileUploaded, $fileUploaded2)) {
				return new ResponseError('移动上传文件失败');
			}
			$url = 'local://article_' . $id . '_' . $file->name;
		}
		$oAtt = new \stdClass;
		$oAtt->matter_id = $id;
		$oAtt->matter_type = 'article';
		$oAtt->name = $file->name;
		$oAtt->type = $file->type;
		$oAtt->size = $file->size;
		$oAtt->last_modified = $file->lastModified;
		$oAtt->url = $url;

		$oAtt->id = $model->insert('xxt_matter_attachment', $oAtt, true);
		/* 更新文章状态 */
		$model->update(
			'xxt_article',
			['has_attachment' => 'Y'],
			['id' => $id]
		);

		return new \ResponseData($oAtt);
	}
	/**
	 * 删除附件
	 */
	public function del_action($site, $id) {
		$model = $this->model();
		// 附件对象
		$att = $model->query_obj_ss(array('matter_id,name,url', 'xxt_matter_attachment', "id='$id'"));
		/**
		 * remove from fs
		 */
		if (strpos($att->url, 'alioss') === 0) {
			$fs = $this->model('fs/alioss', $site, 'attachment');
			$object = $site . '/article/' . $att->matter_id . '/' . $att->name;
			$rsp = $fs->delete_object($object);
		} else if (strpos($att->url, 'local') === 0) {
			$fs = $this->model('fs/local', $site, '附件');
			$path = 'article_' . $att->matter_id . '_' . $att->name;
			$rsp = $fs->delete($path);
		} else {
			$fs = $this->model('fs/saestore', $site);
			$fs->delete($att->url);
		}
		/**
		 * remove from local
		 */
		$rst = $model->delete('xxt_matter_attachment', "id='$id'");
		if ($rst == 1) {
			$q = array(
				'1',
				'xxt_matter_attachment',
				"id='$id'",
			);
			$cnt = $model->query_val_ss($q);
			if ($cnt == 0) {
				$model->update(
					'xxt_article',
					array('has_attachment' => 'N'),
					"id='$id'"
				);
			}
		}

		return new \ResponseData($rst);
	}
}