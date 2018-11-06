<?php
namespace matter\task;
/**
 *
 */
class report_model extends \TMS_MODEL {
	/**
	 * 执行活动状态报告任务
	 *
	 * @param
	 */
	public function exec($oMatter, $oArgs = null) {
		return [false, '已经不再支持该类型事件'];
	}
}