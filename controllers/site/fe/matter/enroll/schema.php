<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动
 */
class schema extends base {
	/**
	 * 返回登记活动题目定义
	 *
	 * @param string $app
	 * @param string $rid
	 *
	 */
	public function get_action($app, $rid = '') {
		/* 要打开的应用 */
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N', 'fields' => self::AppFields, 'appRid' => $rid]);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		/* 应用的动态题目 */
		$modelSch = $this->model('matter\enroll\schema');
		$modelSch->setDynaSchemas($oApp);

		$dataSchemas = $oApp->dynaDataSchemas;

		return new \ResponseData($dataSchemas);
	}
}