<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 填写记录的留言
 */
class remark extends main_base {
	/**
	 * 返回一条填写记录的所有留言
	 *
	 * @param string $ek
	 * @param string $schema schema's id，如果不指定，返回的是对整条记录的留言
	 * @param string $id xxt_enroll_record_data's id
	 *
	 */
	public function list_action($ek, $schema = '', $page = 1, $size = 99, $id = '') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		// 会按照指定的用户id进行过滤，所以去掉用户id，获得所有数据
		$oUser = new \stdClass;

		$options = [];
		if (!empty($id)) {
			$data_id = [];
			$data_id[] = $id;
			$options['data_id'] = $data_id;
		}
		$result = $this->model('matter\enroll\remark')->listByRecord($oUser, $ek, $schema, $page, $size, $options);

		return new \ResponseData($result);
	}
	/**
	 * 返回指定活动下所有留言
	 */
	public function byApp_action($site, $app, $page = 1, $size = 30) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		$oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$oCriteria = $this->getPostJson();
		$options = [
			'fields' => 'id,userid,create_at,nickname,content,agreed,like_num,schema_id,enroll_key',
			'criteria' => $oCriteria,
		];
		$result = $this->model('matter\enroll\remark')->listByApp($oApp, $page, $size, $options);

		return new \ResponseData($result);
	}
}