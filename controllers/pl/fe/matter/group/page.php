<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 分组活动控制器
 */
class page extends \pl\fe\matter\base {
	/**
	 * 创建分组页面
	 *
	 * @param string $aid
	 * @param string $template
	 */
	public function create_action($site, $app, $scenario = 'lottery', $template = 'basic') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelCode = $this->model('code\page');
		$code = $modelCode->create($site, $user->id);

		$this->model()->update(
			'xxt_group',
			array(
				'page_code_name' => $code->name,
				'page_code_id' => $code->id,
			),
			"id='$app'"
		);
		$module = TMS_APP_TEMPLATE . '/pl/fe/matter/group/' . $scenario . '/' . $template;
		/*page*/
		$data = array(
			'html' => file_get_contents($module . '.html'),
			'css' => file_get_contents($module . '.css'),
			'js' => file_get_contents($module . '.js'),
		);
		$modelCode->modify($code->id, $data);
		/*config*/
		$config = file_get_contents($module . '.json');
		$config = preg_replace('/\t|\r|\n/', '', $config);
		$config = json_decode($config);
		if (!empty($config->extjs)) {
			foreach ($config->extjs as $js) {
				$modelCode->insert('xxt_code_external', array('code_id' => $code->id, 'type' => 'J', 'url' => $js), false);
			}
		}
		if (!empty($config->extcss)) {
			foreach ($config->extcss as $css) {
				$modelCode->insert('xxt_code_external', array('code_id' => $code->id, 'type' => 'C', 'url' => $css), false);
			}
		}

		return new \ResponseData($code);
	}
	/**
	 * 重置抽奖页面
	 *
	 * @param string $aid
	 * @param string $template
	 */
	public function reset_action($site, $app, $scenario = 'lottery', $template = 'basic') {
		$modelCode = $this->model('code\page');

		$options = array('fields' => 'page_code_name', 'cascaded' => 'N');
		$app = $this->model('matter\group')->byId($app, $options);

		$module = TMS_APP_TEMPLATE . '/pl/fe/matter/group/' . $scenario . '/' . $template;
		/*page*/
		$data = array(
			'html' => file_get_contents($module . '.html'),
			'css' => file_get_contents($module . '.css'),
			'js' => file_get_contents($module . '.js'),
		);
		$code = $modelCode->lastByName($site, $app->page_code_name);
		$modelCode->modify($code->id, $data);
		/*config*/
		$modelCode->delete('xxt_code_external', "code_id={$code->id}");
		$config = file_get_contents($module . '.json');
		$config = preg_replace('/\t|\r|\n/', '', $config);
		$config = json_decode($config);
		if (!empty($config->extjs)) {
			foreach ($config->extjs as $js) {
				$modelCode->insert('xxt_code_external', array('code_id' => $code->id, 'type' => 'J', 'url' => $js), false);
			}
		}
		if (!empty($config->extcss)) {
			foreach ($config->extcss as $css) {
				$modelCode->insert('xxt_code_external', array('code_id' => $code->id, 'type' => 'C', 'url' => $css), false);
			}
		}

		return new \ResponseData($code->id);
	}
}