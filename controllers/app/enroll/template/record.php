<?php
namespace app\enroll\template;

require_once dirname(__FILE__) . '/base.php';
/**
 * 登记记录
 */
class record extends base {
	/**
	 * 返回登记记录列表
	 *
	 * @param string $scenario
	 * @param string $templte
	 * @param string $rid
	 * @param string $owner A(all)|U(current user)|I(invite)
	 * @param string $orderby
	 *
	 * @return
	 * 数据列表
	 * 数据总条数
	 * 数据项的定义
	 *
	 */
	public function list_action($scenario, $template, $rid = null, $owner = null, $orderby = 'time') {
		$templateDir = $this->getTemplateDir($scenario, $template);
		$data = $this->getData($templateDir);

		$records = array();
		foreach ($data->records as $record) {
			if ($this->_match($record, $rid, $owner)) {
				$records[] = $record;
			}
		}
		$result = array(
			"total" => count($records),
			"schema" => $data->schema,
			"records" => $records,
		);

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	private function _match(&$record, $rid, $owner) {
		if (!empty($rid) && $record->rid !== $rid) {
			return false;
		}
		if (!empty($owner)) {
			if ($owner === 'U' && $record->openid !== 'mine') {
				return false;
			}
		}
		return true;
	}
}