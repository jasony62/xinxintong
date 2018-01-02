<?php
namespace pl\fe\site\sns\wx;

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/base.php';
/**
 * 微信公众号
 */
class user extends \pl\fe\base {
	/**
	 * get all text call.
	 */
	public function index_action($site) {
		\TPL::output('/pl/fe/site/sns/wx/main');
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

		$result = $this->model('sns\wx\fan')->bySite($site, $page, $size, ['gid' => $gid, 'keyword' => $keyword]);

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
		return new \ResponseData('not support');
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
	public function refreshAll_action($site, $step = 0, $nextOpenid = '') {
		if ($step === 0) {
			$fansCount = 0;
			/**
			 * 获得所有粉丝的openid
			 */
			$wxConfig = $this->model('sns\wx')->bySite($site);
			$proxy = $this->model('sns\wx\proxy', $wxConfig);
			$rst = $proxy->userGet($nextOpenid);
			if (false === $rst[0]) {
				if (false !== strpos($rst[1], '(40001)')) {
					$proxy->accessToken(true);
					$rst = $proxy->userGet($nextOpenid);
					if (false === $rst[0]) {
						return new \ResponseError($rst[1]);
					}
				} else {
					return new \ResponseError($rst[1]);
				}
			}
			if (!is_object($rst[1])) {
				return new \ResponseError($rst[1]);
			}
			$userSet = $rst[1];
			$total = $userSet->total; // 所有粉丝的数量
			$openids = $userSet->data->openid; // 本次获得的粉丝id数组
			$nextOpenid = $userSet->count == 10000 ? $userSet->next_openid : '';
		} else {
			$stack = $_SESSION['fans_refreshAll_stack'];
			$wxConfig = $stack['wxConfig'];
			$total = $stack['total'];
			$fansCount = $stack['fansCount'];
			$openids = $stack['openids'];
			$proxy = $this->model('sns\wx\proxy', $wxConfig);
		}
		/**
		 * 更新粉丝
		 */
		if (!empty($openids)) {
			$current = time();
			$ins = array(
				'siteid' => $site,
				'subscribe_at' => $current,
				'sync_at' => $current,
			);
			$finish = 0;
			$modelWxFan = $this->model('sns\wx\fan');
			foreach ($openids as $index => $openid) {
				if ($index == 50 * ($step + 1)) {
					$step++;
					$stack = array(
						'wxConfig' => $wxConfig,
						'total' => $total,
						'fansCount' => $fansCount,
						'openids' => $openids,
					);
					$_SESSION['fans_refreshAll_stack'] = $stack;
					return new \ResponseData(array('total' => $total, 'step' => $step, 'left' => count($openids), 'finish' => $finish, 'refreshCount' => $fansCount, 'nextOpenid' => $nextOpenid));
				}

				$finish++;
				unset($openids[$index]);

				$lfan = $modelWxFan->byOpenid($site, $openid);
				if ($lfan && $lfan->sync_at + 43200 > $current) {
					/* 一小时之内不同步 */
					continue;
				}
				/**
				 * 从公众号获得粉丝信息
				 */
				$info = $proxy->userInfo($openid, true);
				if ($info[0] == false) {
					$fansCount++;
					continue;
					//return new \ResponseError($info[1]);
				}
				$rfan = $info[1];
				if ($rfan->subscribe != 0) {
					$nickname = json_encode($rfan->nickname);
					$nickname = preg_replace('/\\\ud[0-9a-f]{3}/i', '', $nickname);
					$nickname = json_decode($nickname);
					if ($lfan) {
						/**
						 * 更新关注状态粉丝信息
						 */
						$upd = array(
							'nickname' => $modelWxFan->escape($nickname),
							'sex' => $rfan->sex,
							'city' => $rfan->city,
							'groupid' => $rfan->groupid,
							'sync_at' => $current,
						);
						isset($rfan->icon) && $upd['headimgurl'] = $rfan->icon;
						isset($rfan->headimgurl) && $upd['headimgurl'] = $rfan->headimgurl;
						isset($rfan->province) && $upd['province'] = $rfan->province;
						isset($rfan->country) && $upd['country'] = $rfan->country;
						$this->model()->update(
							'xxt_site_wxfan',
							$upd,
							"siteid='$site' and openid='$openid'"
						);
						$fansCount++;
					} else {
						/**
						 * 新粉丝
						 */
						$ins['openid'] = $openid;
						if ($info[0]) {
							$ins['groupid'] = $rfan->groupid;
							$ins['nickname'] = $modelWxFan->escape($nickname);
							$ins['sex'] = $rfan->sex;
							$ins['city'] = $rfan->city;
							isset($rfan->subscribe_time) && $ins['subscribe_at'] = $rfan->subscribe_time;
							isset($rfan->icon) && $ins['headimgurl'] = $rfan->icon;
							isset($rfan->headimgurl) && $ins['headimgurl'] = $rfan->headimgurl;
							isset($rfan->province) && $ins['province'] = $rfan->province;
							isset($rfan->country) && $ins['country'] = $rfan->country;
							$modelWxFan->insert('xxt_site_wxfan', $ins, false);
							$fansCount++;
						}
					}
				}
			}
		}

		return new \ResponseData(array('total' => $total, 'step' => $step, 'left' => count($openids), 'finish' => $finish, 'refreshCount' => $fansCount, 'nextOpenid' => $nextOpenid));
	}
	/**
	 * 从公众平台同步指定粉丝的基本信息和分组信息
	 *
	 * todo 从公众号获得粉丝的代码是否应该挪走？
	 */
	public function refreshOne_action($site, $openid) {
		$wxConfig = $this->model('sns\wx')->bySite($site);
		$wxProxy = $this->model('sns\wx\proxy', $wxConfig);
		$info = $wxProxy->userInfo($openid, true);
		if ($info[0] === false) {
			if (false !== strpos($info[1], '(40001)')) {
				$wxProxy->accessToken(true);
				$info = $wxProxy->userGet($nextOpenid);
				if (false === $info[0]) {
					return new \ResponseError($info[1]);
				}
			} else {
				return new \ResponseError($info[1]);
			}
		}
		if ($info[1]->subscribe != 1) {
			return new \ResponseError('指定用户未关注公众号，无法获取用户信息');
		}
		/**
		 * 更新数据
		 */
		$model = $this->model();
		// 替换掉emoji字符？？？
		$nickname = json_encode($info[1]->nickname);
		$nickname = preg_replace('/\\\ud[0-9a-f]{3}/i', '', $nickname);
		$nickname = json_decode($nickname);
		$nickname = trim($model->escape($nickname));
		$u = [
			'nickname' => empty($nickname) ? '未知' : $nickname,
			'sex' => $info[1]->sex,
			'city' => $info[1]->city,
			'groupid' => $info[1]->groupid,
		];
		isset($info[1]->headimgurl) && $u['headimgurl'] = $info[1]->headimgurl;
		isset($info[1]->icon) && $u['headimgurl'] = $info[1]->icon;
		isset($info[1]->province) && $u['province'] = $info[1]->province;
		isset($info[1]->country) && $u['country'] = $info[1]->country;
		$model->update(
			'xxt_site_wxfan',
			$u,
			["siteid" => $site, "openid" => $openid]
		);

		return new \ResponseData($u);
	}
	/**
	 * 删除一个关注用户
	 */
	public function removeOne_action() {
		return new \ResponseData('not support');
	}
}