<?php
namespace pl\fe\site\sns\wx;

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/base.php';
/**
 * 微信公众号
 */
class page extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/sns/wx/main');
		exit;
	}
	/**
	 *
	 */
	public function create_action($site, $template = 'basic') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$site = $this->model('site')->byId($site);

		$data = $this->_makePage($site, $template);

		$code = \TMS_APP::model('code\page')->create($site, $user->id, $data);

		$rst = $this->model()->update(
			'xxt_site_wx',
			array(
				'follow_page_id' => $code->id,
				'follow_page_name' => $code->name,
			),
			"siteid='{$site->id}'"
		);

		return new \ResponseData($code->id);
	}
	/**
	 * 根据模版重置引导关注页面
	 *
	 * @param int $codeId
	 */
	public function reset_action($site, $codeId, $template = 'basic') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$site = $this->model('site')->byId($site);

		$data = $this->_makePage($site, $template);

		$modelCode = \TMS_APP::model('code\page');
		$code = $modelCode->lastByName($site, $name);
		$rst = $modelCode->modify($code->id, $data);

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	private function &_makePage(&$site, $template) {
		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/site/sns/wx/follow';
		$data = array(
			'html' => file_get_contents($templateDir . '/' . $template . '.html'),
			'css' => file_get_contents($templateDir . '/' . $template . '.css'),
			'js' => file_get_contents($templateDir . '/' . $template . '.js'),
		);
		/*填充页面*/
		$data['html'] = $this->_htmlBySite($site, $data['html']);

		return $data;
	}
	/**
	 *
	 */
	private function &_htmlBySite(&$site, $template) {
		if (defined('SAE_TMP_PATH')) {
			$tmpfname = tempnam(SAE_TMP_PATH, "template");
		} else {
			$tmpfname = tempnam(sys_get_temp_dir(), "template");
		}
		$handle = fopen($tmpfname, "w");
		fwrite($handle, $template);
		fclose($handle);
		$s = new \Savant3(array('template' => $tmpfname, 'exceptions' => true));
		$yx = $this->model('sns\wx')->bySite($site->id);
		$s->assign('site', $site);
		$s->assign('wx', $wx);
		$html = $s->getOutput();
		unlink($tmpfname);

		return $html;
	}
}