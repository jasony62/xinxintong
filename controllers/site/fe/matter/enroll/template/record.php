<?php
namespace app\enroll\template;

require_once dirname(__FILE__) . '/base.php';
/**
 * 登记记录
 */
class record extends base {
	/**
	 * 返回登记记录
	 *
	 * @param string $scenario
	 * @param string $templte
	 *
	 * @return
	 *
	 */
	public function get_action($scenario, $template) {
		$customConfig = $this->getPostJson();
		if (!empty($customConfig->simpleSchema)) {
			$schema = $this->model('app\enroll\page')->schemaByText($customConfig->simpleSchema);
			$record = new \stdClass;
			$record->rid = "";
			$record->enroll_key = "ek2";
			$record->enroll_at = time();
			$record->signin_at = "0";
			$record->tags = null;
			$record->follower_num = "0";
			$record->score = null;
			$record->remark_num = "0";
			$record->fid = "fid1";
			$record->nickname = "用户1";
			$record->openid = "user1";
			$record->headimgurl = "";
			$data = new \stdClass;
			$data->member = new \stdClass;
			foreach ($schema as $def) {
				if (!empty($def->ops)) {
					$i = mt_rand(0, count($def->ops) - 1);
					$data->{$def->id} = $def->ops[$i]->v;
				} else {
					$data->{$def->id} = '';
				}
			}
			$record->data = $data;
			$record->signinLogs = array();
		} else {
			$templateDir = $this->getTemplateDir($scenario, $template);
			$data = $this->getData($templateDir);
			$record = $data->records[0];
		}

		return new \ResponseData($record);
	}
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
		$config = $this->getConfig($templateDir);

		$records = array();
		foreach ($data->records as $record) {
			if ($this->_match($record, $rid, $owner)) {
				$records[] = $record;
			}
		}
		$result = array(
			"total" => count($records),
			"schema" => $config->schema,
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