<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记记录数据汇总
 */
class repos extends base {
	/**
	 * 返回指定登记项的活动登记名单
	 */
	public function list4Schema_action($app, $schema = '', $rid = '', $owner = '', $page = 1, $size = 12) {
		// 登记活动
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['fields' => 'id,data_schemas', 'cascaded' => 'N']);
		// 登记数据过滤条件
		$criteria = $this->getPostJson();

		// 登记记录过滤条件
		$options = new \stdClass;
		$options->page = $page;
		$options->size = $size;
		!empty($rid) && $options->rid = $rid;
		!empty($owner) && $options->owner = $owner;
		if (empty($schema)) {
			$options->schemas = [];
			foreach ($oApp->dataSchemas as $dataSchema) {
				if (isset($dataSchema->shareable) && $dataSchema->shareable === 'Y') {
					$options->schemas[] = $dataSchema->id;
				}
			}
		} else {
			$options->schemas = [$schema];
		}

		// 查询结果
		$mdoelData = $this->model('matter\enroll\data');
		$result = $mdoelData->byApp($oApp, $options);

		return new \ResponseData($result);
	}
	/**
	 * 返回指定登记活动，指定登记项的填写内容
	 *
	 * @param string $app
	 * @param string $schema schema'id
	 * @param string $rid 轮次id，如果不指定为当前轮次，如果为ALL，所有轮次
	 * @param string $onlyMine 只返回当前用户自己的
	 *
	 */
	public function dataBySchema_action($app, $schema, $rid = '', $onlyMine = 'N', $page = 1, $size = 10) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		if (empty($oApp->dataSchemas)) {
			return new \ResponseError('活动【' . $oApp->title . '】没有定义登记项');
		}
		foreach ($oApp->dataSchemas as $dataSchema) {
			if ($dataSchema->id === $schema) {
				$oSchema = $dataSchema;
				break;
			}
		}
		if (!isset($oSchema)) {
			return new \ObjectNotFoundError();
		}

		$modelData = $this->model('matter\enroll\data');
		$options = new \stdClass;
		$options->rid = $rid;
		$options->page = $page;
		$options->size = $size;
		if ($onlyMine === 'Y') {
			//$options->userid = $this->who->uid;
		}
		$result = $modelData->bySchema($oApp, $oSchema, $options);

		return new \ResponseData($result);
	}
}