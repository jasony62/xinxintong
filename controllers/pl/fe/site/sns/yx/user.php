<?php
namespace pl\fe\site\sns\yx;

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/base.php';
/**
 * 易信公众号
 */
class user extends \pl\fe\base {
	/**
	 * get all text call.
	 */
	public function index_action($site) {
		\TPL::output('/pl/fe/site/sns/yx/main');
		exit;
	}
	/**
	 * all fans.
	 *
	 * $keyword
	 * $amount
	 * $gid 关注用户分组
	 */
	public function list_action($site, $keyword = '', $page = 1, $size = 30, $order = 'time', $gid = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$result = $this->model('sns\yx\fan')->bySite($site, $page, $size, ['gid' => $gid, 'keyword' => $keyword]);

		return new \ResponseData($result);
	}
	/**
	 * get one
	 */
	public function get_action($fid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$fan = $this->model('user/fans')->byId($fid);
		$mm = $this->model('user/member');
		if ($members = $mm->byFanid($fid)) {
			foreach ($members as &$member) {
				!empty($member->depts) && $member->depts = $mm->getDepts($member->mid, $member->depts);
				!empty($member->tags) && $member->tags = $mm->getTags($member->mid, $member->tags);
			}
			$fan->members = $members;
		}

		return new \ResponseData($fan);
	}
	/**
	 * get groups
	 */
	public function group_action() {
		$groups = $this->model('user/fans')->getGroups($this->mpid);

		return new \ResponseData($groups);
	}
	/**
	 * 用户的交互足迹
	 */
	public function track_action($openid, $page = 1, $size = 30) {
		$track = $this->model('log')->track($this->mpid, $openid, $page, $size);

		return new \ResponseData($track);
	}
	/**
	 * 更新粉丝信息
	 */
	public function update_action($openid) {
		$mpa = $this->model('mp\mpaccount')->byId($this->mpid);

		$nv = $this->getPostJson();
		/**
		 * 如果要更新粉丝的分组，需要先在公众平台上更新
		 */
		if (isset($nv->groupid)) {
			/**
			 * 更新公众平台上的数据
			 */
			$mpproxy = $this->model('mpproxy/' . $mpa->mpsrc, $this->mpid);
			$rst = $mpproxy->groupsMembersUpdate($openid, $nv->groupid);
			if ($rst[0] === false) {
				return new \ResponseError($rst[1]);
			}

		}
		/**
		 * 更新本地数据
		 */
		$rst = $this->model()->update(
			'xxt_fans',
			(array) $nv,
			"mpid='$this->mpid' and openid='$openid'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 从公众平台同步所有粉丝的基本信息和分组信息
	 *
	 * 需要开通高级接口
	 *
	 * $step
	 * $nextOpenid
	 *
	 */
	public function refreshAll_action($step = 0, $nextOpenid = '') {
		return new \ResponseData('not support');
	}
	/**
	 * 从公众平台同步指定粉丝的基本信息和分组信息
	 *
	 * todo 从公众号获得粉丝的代码是否应该挪走？
	 */
	public function refreshOne_action($openid) {
		return new \ResponseData('not support');
	}
	/**
	 * 从公众平台更新粉丝分组信息
	 *
	 * 1、清除现有的分组
	 * 2、同步公众的号的分组
	 * 不更新粉丝所属的分组
	 */
	public function refreshGroup_action() {
		return new \ResponseData('not support');
	}
	/**
	 * 添加粉丝分组
	 *
	 * 同时在公众平台和本地添加
	 */
	public function addGroup_action() {
		return new \ResponseData('not support');
	}
	/**
	 * 更新粉丝分组的名称
	 *
	 * 同时修改公众平台的数据和本地数据
	 */
	public function updateGroup_action() {
		return new \ResponseData('not support');
	}
	/**
	 * 删除粉丝分组
	 *
	 * 同时删除公众平台上的数据和本地数据
	 */
	public function removeGroup_action() {
		return new \ResponseData('not support');
	}
	/**
	 * 删除一个关注用户
	 */
	public function removeOne_action($fid) {
		return new \ResponseData('not support');
	}
	/**
	 *
	 */
	public function wxfansgroup_action() {
		return new \ResponseData('not support');
	}
}