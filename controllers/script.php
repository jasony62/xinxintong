<?php
/**
 * 脚本文件
 */
class script extends TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'index';

		return $rule_action;
	}
	/**
	 * 获得指定文件的更新日期
	 */
	public function time_action() {
		$targets = $this->getPostJson();

		if (empty($targets) || !is_object($targets)) {
			return new \ParameterError('invalid parameters.');
		}

		$times = new \stdClass; // 记录目标文件的修改时间

		$fnGetTime = function ($path, $name, $category) use ($times) {
			$path .= '.' . $category;
			if (strpos($path, DIRECTORY_SEPARATOR) !== 0 || !file_exists(TMS_APP_DIR . $path)) {
				$times->{$category}->{$name} = '';
			} else {
				$mtime = filemtime(TMS_APP_DIR . $path);
				$times->{$category}->{$name} = (object) ['time' => $mtime];
			}
		};

		if (!empty($targets->js) && is_object($targets->js)) {
			$times->js = new \stdClass;
			array_walk($targets->js, $fnGetTime, 'js');
		}
		if (!empty($targets->html) && is_object($targets->html)) {
			$times->html = new \stdClass;
			array_walk($targets->html, $fnGetTime, 'html');
		}

		return new \ResponseData($times);
	}
}