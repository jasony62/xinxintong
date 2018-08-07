<?php
namespace site\op\matter\enroll;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/*
 * 登记活动主控制器
 */
class remark extends \site\op\base {
	/**
	 * 返回一条登记记录的所有留言
	 */
	public function list_action($ek, $schema = '', $page = 1, $size = 99) {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
		}

		$oUser = new \stdClass;

		$result = $this->model('matter\enroll\remark')->listByRecord($oUser, $ek, $schema, $page, $size);

		return new \ResponseData($result);
	}
	/**
	 * 返回指定活动下所有留言
	 */
	public function byApp_action($app, $page = 1, $size = 30) {
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
	/**
	 *
	 */
	public function agree_action($value = '') {
		$posted = $this->getPostJson();
		if (empty($posted->remark)) {
			return new \ParameterError('没有指定留言数据');
		}
		if (is_array($posted->remark)) {
			$remarkIds = $posted->remark;
		} else {
			$remarkIds = [$posted->remark];
		}

		$modelRem = $this->model('matter\enroll\remark');
		if (!in_array($value, ['Y', 'N', 'A'])) {
			$value = '';
		}
		foreach ($remarkIds as $id) {
			$rst = $modelRem->update(
				'xxt_enroll_record_remark',
				['agreed' => $value],
				['id' => $id]
			);
		}

		return new \ResponseData($rst);
	}
}