<?php
namespace site\fe\matter\enroll\template;

require_once dirname(__FILE__) . '/base.php';
/**
 * 登记记录轮次
 */
class round extends base {
	/**
	 * 返回所有的轮次
	 *
	 * @param string $scenario
	 * @param string $template
	 * @param string $orderby
	 *
	 * @return
	 *
	 */
	public function list_action($scenario, $template) {
		$templateDir = $this->getTemplateDir($scenario, $template);
		$data = $this->getData($templateDir);
		$rounds = $data->rounds;

		return new \ResponseData($rounds);
	}
}