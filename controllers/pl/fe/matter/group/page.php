<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 分组活动控制器
 */
class page extends \pl\fe\matter\base {
	/**
	 * 创建抽奖页面
	 *
	 * @param string $aid
	 * @param string $type
	 */
	public function create_action($site, $app, $type = 'carousel') {
		$uid = \TMS_CLIENT::get_client_uid();
		$modelCode = $this->model('code/page');
		$code = $modelCode->create($uid);

		$this->model()->update('xxt_group', array('page_code_id' => $code->id), "id='$app'");

		$module = TMS_APP_TEMPLATE . '/pl/fe/matter/group/' . $type;
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

		return new \ResponseData($code->id);
	}
	/**
	 * 重置抽奖页面
	 *
	 * @param string $aid
	 * @param string $type
	 */
	public function reset_action($site, $app, $type = 'carousel') {
		$modelCode = $this->model('code/page');

		$options = array('fields' => 'page_code_id', 'cascaded' => 'N');
		$app = $this->model('matter\group')->byId($app, $options);

		$module = TMS_APP_TEMPLATE . '/pl/fe/matter/group/' . $type;
		/*page*/
		$data = array(
			'html' => file_get_contents($module . '.html'),
			'css' => file_get_contents($module . '.css'),
			'js' => file_get_contents($module . '.js'),
		);
		$modelCode->modify($app->page_code_id, $data);
		/*config*/
		$modelCode->delete('xxt_code_external', "code_id=$app->page_code_id");
		$config = file_get_contents($module . '.json');
		$config = preg_replace('/\t|\r|\n/', '', $config);
		$config = json_decode($config);
		if (!empty($config->extjs)) {
			foreach ($config->extjs as $js) {
				$modelCode->insert('xxt_code_external', array('code_id' => $app->page_code_id, 'type' => 'J', 'url' => $js), false);
			}
		}
		if (!empty($config->extcss)) {
			foreach ($config->extcss as $css) {
				$modelCode->insert('xxt_code_external', array('code_id' => $app->page_code_id, 'type' => 'C', 'url' => $css), false);
			}
		}

		return new \ResponseData($app->page_code_id);
	}
}