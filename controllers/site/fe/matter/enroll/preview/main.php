<?php
namespace site\fe\matter\enroll\preview;
/**
 * 登记活动预览
 */
class main extends \TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$ruleAction = [
			'rule_type' => 'black',
		];

		return $ruleAction;
	}
	/**
	 *
	 */
	public function index_action($openAt = null) {
		if (!empty($openAt)) {
			if ($openAt === 'before') {
				$this->outputError('登记尚未开始');
			}
			if ($openAt === 'after') {
				$this->outputError('登记已经结束');
			}
		}

		\TPL::output('/site/fe/matter/enroll/preview');
		exit;
	}
	/**
	 * 返回登记记录
	 *
	 * @param string $site
	 * @param string $app
	 * @param string $page page's name
	 */
	public function get_action($site, $app, $page) {
		$params = array();

		$modelApp = $this->model('matter\enroll');

		/* 登记活动定义 */
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ResponseError('指定的登记活动不存在，请检查参数是否正确');
		}
		$params['app'] = &$oApp;

		/* 当前访问用户的基本信息 */
		$user = new \stdClass;
		$params['user'] = $user;

		/* 计算打开哪个页面 */
		$modelPage = $this->model('matter\enroll\page');
		$oOpenPage = $modelPage->byName($oApp, $page);
		if (empty($oOpenPage)) {
			return new \ResponseError('页面不存在');
		}
		$params['page'] = $oOpenPage;

		/* 站点页面设置 */
		if ($oApp->use_site_header === 'Y' || $oApp->use_site_footer === 'Y') {
			$params['site'] = $this->model('site')->byId(
				$site,
				['cascaded' => 'header_page_name,footer_page_name']
			);
		}

		/* 项目页面设置 */
		if ($oApp->use_mission_header === 'Y' || $oApp->use_mission_footer === 'Y') {
			if ($oApp->mission_id) {
				$params['mission'] = $this->model('matter\mission')->byId(
					$oApp->mission_id,
					['cascaded' => 'header_page_name,footer_page_name']
				);
			}
		}
		$params['activeRound'] = $this->model('matter\enroll\round')->getActive($oApp);

		return new \ResponseData($params);
	}
	/**
	 *
	 */
	protected function outputError($err, $title = '程序错误') {
		\TPL::assign('title', $title);
		\TPL::assign('body', $err);
		\TPL::output('error');
		exit;
	}
}