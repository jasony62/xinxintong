<?php
namespace site\fe\matter\signin\preview;
/**
 * 签到活动预览
 */
class main extends \TMS_CONTROLLER {
	/**
	 * 返回活动页
	 */
	public function index_action($openAt = 'ontime') {
		if ($openAt === 'before') {
			$this->outputError('签到还没有开始');
		}
		/* 返回签到活动页面 */
		\TPL::output('/site/fe/matter/signin/preview');
		exit;
	}
	/**
	 * 返回签到登记记录
	 *
	 * @param string $siteid
	 * @param string $appid
	 * @param string $page page's name
	 */
	public function get_action($site, $app, $page) {
		$params = [];

		// 签到活动定义
		$signinApp = $this->model('matter\signin')->byId($app, ['cascaded' => 'N']);
		$params['app'] = &$signinApp;

		// 当前访问用户的基本信息
		$user = new \stdClass;
		$params['user'] = $user;

		// 打开哪个页面？
		$oPage = $this->model('matter\signin\page')->byName($signinApp->id, $page);
		$params['page'] = $oPage;

		// 项目页面设置
		if ($signinApp->use_mission_header === 'Y' || $signinApp->use_mission_footer === 'Y') {
			if ($signinApp->mission_id) {
				$params['mission'] = $this->model('matter\mission')->byId(
					$signinApp->mission_id,
					['cascaded' => 'header_page_name,footer_page_name']
				);
			}
		}
		// 站点页面设置
		if ($signinApp->use_site_header === 'Y' || $signinApp->use_site_footer === 'Y') {
			$params['site'] = $this->model('site')->byId(
				$site,
				['cascaded' => 'header_page_name,footer_page_name']
			);
		}

		// 签到记录
		if ($oPage->type === 'I') {
			$params['record'] = false;
		}

		return new \ResponseData($params);
	}
	/**
	 *
	 */
	private function outputError($err, $title = '程序错误') {
		\TPL::assign('title', $title);
		\TPL::assign('body', $err);
		\TPL::output('error');
		exit;
	}
}