<?php
namespace pl\fe\site\sns\wx;

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/base.php';
/**
 * 微信公众号
 */
class group extends \pl\fe\base {
	/**
	 * get groups
	 */
	public function list_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$groups = $this->model('sns\wx\group')->bySite($site);

		return new \ResponseData($groups);
	}
	/**
	 * 从公众平台更新粉丝分组信息
	 *
	 * 1、清除现有的分组
	 * 2、同步公众的号的分组
	 * 不更新粉丝所属的分组
	 */
	public function refresh_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$wxConfig = $this->model('sns\wx')->bySite($site);
		$proxy = $this->model("sns\wx\proxy", $wxConfig);
		$rst = $proxy->groupsGet();
		if (false === $rst[0]) {
			return new \ResponseError($rst[1]);
		}

		$groups = $rst[1]->groups;

		$model = $this->model();
		$model->delete('xxt_site_wxfangroup', "siteid='$site'");
		foreach ($groups as $g) {
			$i = ['id' => $g->id, 'siteid' => $site, 'name' => $g->name];
			$model->insert('xxt_site_wxfangroup', $i, false);
		}

		return new \ResponseData(count($groups));
	}
	/**
	 * 添加粉丝分组
	 *
	 * 同时在公众平台和本地添加
	 */
	public function add_action() {
		return new \ResponseData('not support');
	}
	/**
	 * 更新粉丝分组的名称
	 *
	 * 同时修改公众平台的数据和本地数据
	 */
	public function update_action() {
		return new \ResponseData('not support');
	}
	/**
	 * 删除粉丝分组
	 *
	 * 同时删除公众平台上的数据和本地数据
	 */
	public function remove_action() {
		return new \ResponseData('not support');
	}
}