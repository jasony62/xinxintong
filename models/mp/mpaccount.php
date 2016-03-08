<?php
namespace mp;
/**
 *
 */
class mpaccount_model extends \TMS_MODEL {
	/**
	 * 创建一个公众账号
	 */
	public function create($data) {
		$uid = \TMS_CLIENT::get_client_uid();
		if (empty($data['mpid'])) {
			$mpid = $this->uuid($uid);
			$data['mpid'] = $mpid;
		} else {
			$mpid = $data['mpid'];
		}
		$data['creater'] = $uid;
		$data['create_at'] = time();
		$this->insert('xxt_mpaccount', $data, false);
		/**
		 * 公众号的配置信息
		 */
		$setting = array(
			'mpid' => $mpid,
		);
		$this->insert('xxt_mpsetting', $setting, false);

		return $mpid;
	}
	/**
	 *
	 */
	public function &byId($mpid, $fields = '*') {
		$q = array(
			$fields,
			'xxt_mpaccount',
			"mpid='$mpid'",
		);

		$mpaccount = $this->query_obj_ss($q);

		return $mpaccount;
	}
	/**
	 * $mpid
	 */
	public function &byMpid($mpid, $fields = '*') {
		if (empty($mpid)) {
			return false;
		}

		$q = array(
			$fields,
			'xxt_mpaccount',
			"parent_mpid='$mpid'",
		);

		$mpas = $this->query_objs_ss($q);

		return $mpas;
	}
	/**
	 * 是否为账号管理员
	 */
	public function isCreater($mpid = null, $uid = null) {
		empty($mpid) && $mpid = \TMS_APP::S('mpid');
		empty($uid) && $uid = \TMS_CLIENT::get_client_uid();
		/**
		 * 账号的创建人
		 */
		$mpcreater = $this->query_value('creater', 'xxt_mpaccount', "mpid='$mpid'");
		if ($uid === $mpcreater) {
			return true;
		}

		return false;
	}
	/**
	 * 获得账号创建人的邮箱
	 */
	public function getCreater($mpid) {
		$q = array(
			'a.authed_id,a.nickname',
			'account a,xxt_mpaccount m',
			"a.uid=m.creater and m.mpid='$mpid'",
		);
		$creater = $this->query_obj_ss($q);

		return $creater;
	}
	/**
	 * 获得账号创建人的联系方式
	 */
	public function getAdminContact($mpid = null) {
		empty($mpid) && $mpid = \TMS_APP::S('mpid');

		empty($mpid) && die('mpid is empty!');

		$q = array(
			'admin_contact contact',
			'xxt_mpsetting m',
			"m.mpid='$mpid'",
		);
		$admin = $this->query_obj_ss($q);

		if (!$admin || empty($admin->contact)) {
			if ($creater = $this->getCreater($mpid)) {
				$contact = empty($creater->nickname) ? $creater->authed_id : $creater->nickname;
			} else {
				$contact = '';
			}

		} else {
			$contact = $admin->contact;
		}

		return $contact;
	}
	/**
	 * 获得公众号设置信息
	 */
	public function &getFeature($mpid, $fields = '*') {
		$q = array(
			$fields,
			'xxt_mpsetting',
			"mpid='$mpid'",
		);

		$mpsetting = $this->query_obj_ss($q);

		return $mpsetting;
	}
	/**
	 * 公众号的接口开放情况
	 */
	public function &getApis($mpid) {
		$names = 'a.mpsrc,a.asparent,a.yx_joined,a.wx_joined,a.qy_joined,a.qy_agentid';
		$names .= ',s.yx_menu,s.wx_menu';
		$names .= ',s.yx_group_push,s.wx_group_push,s.yx_custom_push,s.wx_custom_push';
		$names .= ',s.yx_fans,s.wx_fans,s.yx_fansgroup,s.wx_fansgroup';
		$names .= ',s.yx_qrcode,s.wx_qrcode';
		$names .= ',s.yx_oauth,s.wx_oauth';
		$names .= ',s.yx_p2p,s.yx_checkmobile';
		$names .= ',s.wx_pay';

		$q = array(
			$names,
			'xxt_mpaccount a,xxt_mpsetting s',
			"s.mpid='$mpid' and a.mpid=s.mpid",
		);

		$mpsetting = $this->query_obj_ss($q);

		return $mpsetting;
	}
	/**
	 * 群发消息
	 * 需要开通群发接口
	 */
	public function mass2mps($sender, $matterId, $matterType, $mpids) {
		if (empty($mpids)) {
			return array(false, '没有指定接收消息的公众号');
		}

		$response = array();

		$mpModel = \TMS_APP::M('mp\mpaccount');
		foreach ($mpids as $mpid) {
			$mpaccount = $mpModel->byId($mpid);
			$mpsrc = $mpaccount->mpsrc;
			/**
			 * set message
			 */
			$matterModel = \TMS_APP::M('matter\\' . $matterType);
			if ($mpsrc === 'wx' && in_array($matterType, array('article', 'news', 'channel'))) {
				/**
				 * 微信的图文群发消息需要上传到公众号平台，所以链接素材无法处理
				 */
				$message = $matterModel->forWxGroupPush($mpid, $matterId);
			} else {
				$message = $matterModel->forCustomPush($mpid, $matterId);
			}
			if (empty($message)) {
				$response[$mpid] = '指定的素材无法向公众号用户群发！';
				continue;
			}
			/**
			 * send
			 */
			$mpsrc === 'wx' && $message['filter'] = array('is_to_all' => true);
			$mpproxy = \TMS_APP::M('mpproxy/' . $mpsrc, $mpid);
			$mpproxy->reset($mpid); // 因为重复获得对象会缓存
			$rst = $mpproxy->send2group($message);
			if ($rst[0] === true) {
				$response[$mpid] = 'ok';
				$msgid = isset($rst[1]->msg_id) ? $rst[1]->msg_id : 0;
				\TMS_APP::M('log')->mass($sender, $mpid, $matterType, $matterId, $message, $msgid, 'ok');
			} else {
				$response[$mpid] = $rst[1];
				\TMS_APP::M('log')->mass($sender, $mpid, $matterType, $matterId, $message, 0, $rst[1]);
			}
		}

		return array(true, $response);
	}
	/**
	 * 公众号进行OAuth的地址
	 *
	 * @param string $mpid
	 * @param string $callbackUrl 回调地址
	 */
	public function oauthUrl($mpid, $callbackUrl = null) {
		$mpa = $this->byId($mpid, 'mpsrc');
		switch ($mpa->mpsrc) {
		case 'qy':
			$mpproxy = \TMS_APP::M('mpproxy/qy', $mpid);
			break;
		case 'wx':
			$fea = $this->getApis($mpid);
			$fea->wx_oauth === 'Y' && $mpproxy = \TMS_APP::M('mpproxy/wx', $mpid);
			break;
		case 'yx':
			$fea = $this->getApis($mpid);
			$fea->yx_oauth === 'Y' && $mpproxy = \TMS_APP::M('mpproxy/yx', $mpid);
			break;
		}
		if (isset($mpproxy)) {
			if ($callbackUrl === null) {
				$callbackUrl = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			}
			$url = $mpproxy->oauthUrl($mpid, $callbackUrl);
			return $url;
		}

		return false;
	}
}