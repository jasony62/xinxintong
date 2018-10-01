<?php
namespace matter\task;
/**
 * 提醒事件
 */
class absent_model extends \TMS_MODEL {
	/**
	 * 执行活动任务提醒任务
	 *
	 * @param object $oMatter
	 * @param mix $arguments
	 *
	 * @return array
	 */
	public function exec($oMatter, $arguments = null) {
		switch ($oMatter->type) {
		case 'enroll':
			$arguments = empty($arguments) ? new \stdClass : (is_object($arguments) ? $arguments : json_decode($arguments));
			$aResult = $this->_enroll($oMatter, $arguments);
			break;
		default:
			return [false, '不支持的活动类型【' . $oMatter->type . '】'];
		}

		return [true];
	}
	/**
	 * 记录活动提醒通知
	 */
	private function _enroll($oMatter, $oArguments) {
		$modelEnl = $this->model('matter\enroll');
		$oMatter = $modelEnl->byId($oMatter->id, ['cascaded' => 'N']);
		if (false === $oMatter || $oMatter->state !== '1') {
			return [false, '指定的活动不存在，或已不可用'];
		}

		/* 获得活动的进入链接 */
		$noticeURL = $oMatter->entryUrl;
		$noticeURL .= '&origin=timer';

		if (count($receivers) === 0) {
			return [false, '指定活动中没有接收人'];
		}

		return [true, $oMatter, $noticeURL, $receivers];
	}
}