<?php
namespace site\fe\matter\template\enroll\preview;
/**
 * 记录活动预览
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
	public function index_action($site, $tid) {
		if (false === ($template = $this->model('matter\template')->byId($tid, null, ['cascaded' => 'N']))) {
			die('指定的模板不存在，请检查参数是否正确');
		}

		\TPL::output('/site/fe/matter/template/enroll/preview');
		exit;
	}
	/**
	 * 返回登记模板
	 *
	 * @param string $site
	 * @param string $template
	 * @param string $page page's name
	 */
	public function get_action($site, $tid, $vid = null, $page) {
		$params = array();

		$modelTmp = $this->model('matter\template');

		/* 记录活动定义 */
		if (false === ($template = $modelTmp->byId($tid, $vid))) {
			return new \ResponseError('指定的模板不存在，请检查参数是否正确');
		}
		$params['app'] = &$template;

		/* 当前访问用户的基本信息 */
		$user = new \stdClass;
		$params['user'] = $user;

		/* 计算打开哪个页面 */
		$modelPage = $this->model('matter\enroll\page');
		$aid = 'template:' . $template->vid;
		$oOpenPage = $modelPage->byName((object) ['id' => $aid], $page);
		if (empty($oOpenPage)) {
			return new \ResponseError('模板页面不存在');
		}
		$params['page'] = $oOpenPage;

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