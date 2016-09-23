<?php
namespace site\op\matter\enroll;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/**
 *
 */
class record extends \site\op\base {
	/**
	 *
	 */
	public function list_action($site, $app, $page = 1, $size = 30, $tags = null, $rid = null, $kw = null, $by = null, $orderby = null, $contain = null) {
		$options = array(
			'page' => $page,
			'size' => $size,
			'tags' => $tags,
			'rid' => $rid,
			'kw' => $kw,
			'by' => $by,
			'orderby' => $orderby,
			'contain' => $contain,
		);
		$app = $this->model('matter\enroll')->byId($app);
		$mdoelRec = $this->model('matter\enroll\record');
		$result = $mdoelRec->find($site, $app, $options);

		return new \ResponseData($result);
	}
	/**
	 * 更新登记记录
	 *
	 * @param string $app
	 * @param $ek record's key
	 */
	public function update_action($site, $app, $ek) {
		$record = $this->getPostJson();
		$model = $this->model();
		$current = time();

		$app = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		$schemas = json_decode($app->data_schemas);
		$schemasById = [];
		foreach ($schemas as $schema) {
			$schemasById[$schema->id] = $schema;
		}

		//
		$model->update(
			'xxt_enroll_record',
			['enroll_at' => $current],
			"enroll_key='$ek'"
		);
		foreach ($record as $k => $v) {
			if (in_array($k, ['verified', 'tags', 'comment'])) {
				$model->update(
					'xxt_enroll_record',
					[$k => $v],
					"enroll_key='$ek'"
				);
				// 更新记录的标签时，要同步更新活动的标签，实现标签在整个活动中有效
				if ($k === 'tags') {
					$this->model('matter\enroll')->updateTags($app->id, $v);
				}
				if ($k === 'verified' && $v === 'Y') {
					$this->_whenVerifyRecord($app, $ek);
				}
			} else if ($k === 'data' and is_object($v)) {
				$dbData = new \stdClass;
				foreach ($v as $cn => $cv) {
					$schema = $schemasById[$cn];
					if (is_array($cv) && isset($cv[0]->imgSrc)) {
						/* 上传图片 */
						$vv = array();
						$fsuser = $this->model('fs/user', $site);
						foreach ($cv as $img) {
							if (preg_match("/^data:.+base64/", $img->imgSrc)) {
								$rst = $fsuser->storeImg($img);
								if (false === $rst[0]) {
									return new \ResponseError($rst[1]);
								}
								$vv[] = $rst[1];
							} else {
								$vv[] = $img->imgSrc;
							}
						}
						$cv = implode(',', $vv);
						$dbData->{$cn} = $cv;
					} else if ($schema->type === 'score') {
						$dbData->{$cn} = $cv;
						$cv = json_encode($cv);
					} else if (is_string($cv)) {
						$cv = $model->escape($cv);
						$dbData->{$cn} = $cv;
					} else if (is_object($cv) || is_array($cv)) {
						/*多选题*/
						$cv = implode(',', array_keys(array_filter((array) $cv, function ($i) {return $i;})));
						$dbData->{$cn} = $cv;
					}
					/*检查数据项是否存在，如果不存在就先创建一条*/
					$q = array(
						'count(*)',
						'xxt_enroll_record_data',
						"enroll_key='$ek' and name='$cn'",
					);
					if (1 === (int) $model->query_val_ss($q)) {
						$model->update(
							'xxt_enroll_record_data',
							array('value' => $cv),
							"enroll_key='$ek' and name='$cn'"
						);
					} else {
						$cd = [
							'aid' => $app->id,
							'enroll_key' => $ek,
							'name' => $cn,
							'value' => $cv,
						];
						$model->insert('xxt_enroll_record_data', $cd, false);
					}
				}
				//
				$record->data = $dbData;
				$dbData = $model->toJson($dbData);
				$model->update(
					'xxt_enroll_record',
					['data' => $dbData],
					"enroll_key='$ek'"
				);
			}
		}
		// 记录操作日志
		//$app->type = 'enroll';
		//$this->model('matter\log')->matterOp($site, $user, $app, 'update', $record);

		return new \ResponseData($record);
	}
}