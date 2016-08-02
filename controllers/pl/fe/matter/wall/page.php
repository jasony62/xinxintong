<?php
namespace pl\fe\matter\wall;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 信息墙定制页
 */
class page extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/wall/frame');
		exit;
	}
	/**
	 * 获得指定信息墙的定制页
	 *
	 * @param string $wall
	 */
	public function list_action($id, $site) {
		$modelPage = $this->model('matter\wall\page');
		$modelCode = $this->model('code\page');

		$wallPages = array(
			array(
				'name' => '信息墙大屏幕',
				'title' => '信息墙大屏幕',
				'type' => 'op',
				'seq' => 1,
			),
		);
		$pages = array();
		foreach ($wallPages as $wp) {
			$page = $modelPage->byType($wp['type'], $id);
			if (empty($page)) {
				$page = $modelPage->add($site, $wp, $id);
				$tmplateDir = dirname(__FILE__) . '/template/' . str_replace('.', '/', $wp['type']) . '/';
				$data = array(
					'html' => file_get_contents($tmplateDir . 'basic.html'),
					'css' => file_get_contents($tmplateDir . 'basic.css'),
					'js' => file_get_contents($tmplateDir . 'basic.js'),
				);
				$modelCode->modify($page->code_id, $data);
			}
			if (is_array($page)) {
				$pages = array_merge($pages, $page);
			} else {
				$pages[] = $page;
			}
		}

		return new \ResponseData($pages);
	}
	/**
	 * 用模板重置页面
	 */
	public function reset_action($page) {
		$page = $this->model('matter\wall\page')->byId($page);
		$modelCode = $this->model('code\page');

		$pattern = 'basic';
		$dir = str_replace('.', '/', $page->type);
		$templateDir = dirname(__FILE__) . '/template/' . $dir . '/';
		$data = array(
			'html' => file_get_contents($templateDir . $pattern . '.html'),
			'css' => file_get_contents($templateDir . $pattern . '.css'),
			'js' => file_get_contents($templateDir . $pattern . '.js'),
		);
		$modelCode->modify($page->code_id, $data);

		return new \ResponseData('ok');
	}
}