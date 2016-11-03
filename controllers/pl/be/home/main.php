<?php
namespace pl\be\home;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台首页
 */
class main extends \pl\be\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/be/home/frame');
		exit;
	}
	/**
	 * 创建站点首页页面
	 */
	public function pageCreate_action($name, $template = 'basic') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$mdoelPl = $this->model('platform');

		$platform = $mdoelPl->get();

		$data = $this->_makePage($name, $template);

		$code = $this->model('code\page')->create('platform', $user->id, $data);

		$rst = $mdoelPl->update(
			'xxt_platform',
			[
				$name . '_page_id' => $code->id,
				$name . '_page_name' => $code->name,
			],
			"1=1"
		);

		return new \ResponseData($code);
	}
	/**
	 * 根据模版重置引导关注页面
	 *
	 * @param int $codeId
	 */
	public function pageReset_action($name, $template = 'basic') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$platform = $this->model('platform')->get();

		$data = $this->_makePage($name, $template);

		$rst = $this->model('code\page')->modify($platform->{$name . '_page_id'}, $data);

		return new \ResponseData($rst);
	}
	/**
	 * 通过本地模版生成页面
	 */
	private function &_makePage($name, $template) {
		$templateDir = TMS_APP_TEMPLATE . '/pl/be/' . $name;
		$data = array(
			'html' => file_get_contents($templateDir . '/' . $template . '.html'),
			'css' => file_get_contents($templateDir . '/' . $template . '.css'),
			'js' => file_get_contents($templateDir . '/' . $template . '.js'),
		);

		return $data;
	}
}