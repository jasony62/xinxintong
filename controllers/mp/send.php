<?php
namespace mp;

require_once dirname(__FILE__) . '/mp_controller.php';

class send extends mp_controller {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 * 发送客服消息
	 *
	 * 需要开通高级接口
	 */
	public function custom_action($openid) {
		$mpa = $this->model('mp\mpaccount')->byId($this->mpid);
		/**
		 * 检查是否开通了群发接口
		 */
		if ($mpa->mpsrc === 'wx' || $mpa->mpsrc === 'yx') {
			$setting = $this->model('mp\mpaccount')->getFeature($this->mpid, $mpa->mpsrc . '_custom_push');
			if ($setting->{$mpa->mpsrc . '_custom_push'} === 'N') {
				return new \ResponseError('未开通群发高级接口，请检查！');
			}
		}
		/**
		 * get matter.
		 */
		$matter = $this->getPostJson();
		if (isset($matter->id)) {
			$message = $this->assemble_custom_message($matter);
		} else {
			$message = array(
				"msgtype" => "text",
				"text" => array(
					"content" => $matter->text,
				),
			);
		}
		/**
		 * 发送消息
		 */
		$rst = $this->sendByOpenid($this->mpid, $openid, $message);
		if (false === $rst[0]) {
			return new \ResponseError($rst[1]);
		}
		/**
		 * 记录日志
		 */
		if (isset($matter->id)) {
			$this->model('log')->send($this->mpid, $openid, null, $matter->title, $matter);
		} else {
			$this->model('log')->send($this->mpid, $openid, null, $matter->text, null);
		}

		return new \ResponseData('success');
	}
	/**
	 *
	 */
	private function send2group($mpsrc, $mpid, $message, $matter, &$warning) {
		$uid = \TMS_CLIENT::get_client_uid();

		$mpproxy = $this->model('mpproxy/' . $mpsrc, $mpid);

		$rst = $mpproxy->send2group($message);
		if ($rst[0] === true) {
			$msgid = isset($rst[1]->msg_id) ? $rst[1]->msg_id : 0;
			$this->model('log')->mass($uid, $mpid, $matter->type, $matter->id, $message, $msgid, 'ok');
		} else {
			$warning[] = $rst[1];
			$this->model('log')->mass($uid, $mpid, $matter->type, $matter->id, $message, 0, $rst[1]);
		}

		return true;
	}
	/**
	 * 群发消息
	 * 需要开通高级接口
	 *
	 * 开通了群发接口的微信和易信公众号
	 * 微信企业号
	 * 开通了点对点认证接口的易信公众号
	 */
	public function mass_action() {
		$mpaccount = $this->getMpaccount();
		// 要发送的素材
		$matter = $this->getPostJson();
		if (empty($matter->targetUser) || empty($matter->userSet)) {
			return new \ResponseError('请指定接收消息的用户');
		}
		// 要接收的用户
		$userSet = $matter->userSet;
		/**
		 * send message.
		 */
		if ($matter->targetUser === 'F') {
			/**
			 * set message
			 */
			if ($mpaccount->mpsrc === 'wx') {
				/**
				 * 微信的图文群发消息需要上传到公众号平台，所以链接素材无法处理
				 */
				$model = $this->model('matter\\' . $matter->type);
				if ($matter->type === 'text') {
					$message = $model->forCustomPush($this->mpid, $matter->id);
				} else if (in_array($matter->type, array('article', 'news', 'channel'))) {
					$message = $model->forWxGroupPush($this->mpid, $matter->id);
				}

			} else if ($mpaccount->mpsrc === 'yx') {
				$message = $this->assemble_custom_message($matter);
			}
			if (empty($message)) {
				return new \ResponseError('指定的素材无法向微信用户群发！');
			}
			/**
			 * send
			 */
			if ($userSet[0]->identity === -1) {
				/**
				 * 发给所有用户
				 */
				$mpaccount->mpsrc === 'wx' && $message['filter'] = array('is_to_all' => true);
				$this->send2group($mpaccount->mpsrc, $this->mpid, $message, $matter, $warning);
			} else {
				/**
				 * 发送给指定的关注用户组
				 */
				if ($mpaccount->mpsrc === 'wx') {
					foreach ($userSet as $us) {
						$message['filter'] = array(
							'is_to_all' => false,
							'group_id' => $us->identity,
						);
						$this->send2group($mpaccount->mpsrc, $this->mpid, $message, $matter, $warning);
					}
				} else if ($mpaccount->mpsrc === 'yx') {
					$message = $this->assemble_custom_message($matter);
					foreach ($userSet as $us) {
						$message['group'] = $us->label;
						$this->send2group($mpaccount->mpsrc, $this->mpid, $message, $matter, $warning);
					}
				}
			}
		} else {
			/**
			 * 发送给认证用户
			 */
			$rst = $this->send2Member($mpaccount, $matter->userSet, $matter);
			if ($rst[0] === false) {
				is_array($rst[1]) ? $warning = $rst[1] : $warning[] = $rst[1];
			}
		}
		if (!empty($warning)) {
			return new \ResponseError(implode(';', $warning));
		} else {
			return new \ResponseData('success');
		}
	}
	/**
	 * 群发消息
	 * 开通了点对点认证接口的易信公众号
	 */
	public function yxmember_action($phase = 0, $sizeOfBatch = 20) {
		if ($phase == 0) {
			$matter = $this->getPostJson();
			if (empty($matter->targetUser) || empty($matter->userSet)) {
				return new \ResponseError('请指定接收消息的用户');
			}
			/*消息*/
			$model = $this->model('matter\\' . $matter->type);
			$message = $model->forCustomPush($this->mpid, $matter->id);
			/*用户*/
			$userSet = $matter->userSet;
			$rst = $this->getOpenid($userSet);
			if ($rst[0] === false) {
				return new \ResponseError($rst[1]);
			}
			$openids = $rst[1];
			$_SESSION['message'] = &$message;
			$_SESSION['openids'] = &$openids;
			$countOfOpenids = count($openids);

			return new \ResponseData(array('nextPhase' => 1, 'countOfOpenids' => $countOfOpenids));
		}
		if ($phase == 1) {
			$warning = isset($_SESSION['warning']) ? $_SESSION['warning'] : array();
			$message = $_SESSION['message'];
			$openids = $_SESSION['openids'];
			$batch = array_slice($openids, 0, $sizeOfBatch);
			/*发送*/
			$rst = $this->send2YxUserByP2p($this->mpid, $message, $batch);
			if (false === $rst[0]) {
				$warning = array_merge($warning, $rst[1]);
			}
			if (count($openids) > $sizeOfBatch) {
				$openids = array_splice($openids, $sizeOfBatch);
				$_SESSION['openids'] = &$openids;
				$countOfOpenids = count($openids);
				$_SESSION['warning'] = $warning;
				return new \ResponseData(array('nextPhase' => 1, 'countOfOpenids' => $countOfOpenids));
			}
		}
		/*结束*/
		unset($_SESSION['warning']);
		unset($_SESSION['openids']);
		unset($_SESSION['message']);

		return new \ResponseData($warning);
	}
	/**
	 * 预览消息
	 *
	 * 开通预览接口的微信公众号
	 * 开通点对点消息的易信公众奥
	 * 微信企业号
	 */
	public function preview_action($matterId, $matterType, $openids) {
		$mpaccount = $this->getMpaccount();

		if ($mpaccount->mpsrc === 'wx') {
			$model = $this->model('matter\\' . $matterType);
			if ($matterType === 'text') {
				$message = $model->forCustomPush($this->mpid, $matterId);
			} else if (in_array($matterType, array('article', 'news', 'channel'))) {
				/**
				 * 微信的图文群发消息需要上传到公众号平台，所以链接素材无法处理
				 */
				$message = $model->forWxGroupPush($this->mpid, $matterId);
			}
			$rst = $this->send2WxuserByPreview($this->mpid, $message, $openids);
		} else if ($mpaccount->mpsrc === 'yx') {
			$message = $this->assemble_custom_message($matter);
			$rst = $this->sent2YxUserByp2p($this->mpid, $message, $openids);
		} else if ($mpaccount->mpsrc === 'qy') {
		}
		if (empty($message)) {
			return new \ResponseError('指定的素材无法向用户群发！');
		}
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		} else {
			return new \ResponseData('ok');
		}
	}
	/**
	 * 群发消息到子公众号
	 * 需要开通高级接口
	 *
	 * 开通了群发接口的微信和易信公众号
	 * 微信企业号
	 * 开通了点对点认证接口的易信公众号
	 */
	public function mass2mps_action() {
		$matter = $this->getPostJson();
		if (empty($matter->mps)) {
			return new \ResponseError('请指定接收消息的公众号');
		}

		$uid = \TMS_CLIENT::get_client_uid();
		$rst = $this->model('mp\mpaccount')->mass2mps($uid, $matter->id, $matter->type, $matter->mps);

		return new \ResponseData($rst[1]);
	}
	/**
	 * 根据指定的素材，组装客服消息
	 */
	private function assemble_custom_message($matter) {
		$model = $this->model('matter\\' . $matter->type);
		$message = $model->forCustomPush($this->mpid, $matter->id);

		return $message;
	}
	/**
	 * 发送模板消息
	 *
	 * $tid 模板消息id
	 */
	public function tmplmsg_action($tid) {
		$posted = $this->getPostJson();

		if (isset($posted->matter)) {
			$url = $this->model('matter\\' . $posted->matter->type)->getEntryUrl($this->mpid, $posted->matter->id);
		} else if (isset($posted->url)) {
			$url = $posted->url;
		} else {
			$url = '';
		}

		$data = $posted->data;
		$userSet = $posted->userSet;

		$rst = $this->getOpenid($userSet);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		if (empty($rst[1])) {
			return new \ResponseError('没有指定消息接收人');
		}

		$openids = $rst[1];

		foreach ($openids as $openid) {
			$rst = $this->tmplmsgSendByOpenid($this->mpid, $tid, $openid, $data, $url);
			if ($rst[0] === false) {
				return new \ResponseError($rst[1]);
			}

		}

		return new \ResponseData('success');
	}
	/**
	 *
	 */
	public function tmplmsglog_action($tid, $page, $size) {
		$model = $this->model();
		$q = array(
			'id,template_id,msgid,openid,data,create_at,status',
			'xxt_log_tmplmsg',
			"mpid='$this->mpid' and tmplmsg_id=$tid",
		);
		$q2 = array(
			'r' => array(
				'o' => ($page - 1) * $size,
				'l' => $size,
			),
		);
		if ($logs = $model->query_objs_ss($q, $q2)) {
			$q[0] = 'count(*)';
			$total = $model->query_val_ss($q);
		} else {
			$total = 0;
		}

		return new \ResponseData(array('logs' => $logs, 'total' => $total));
	}
	/**
	 * 测试上传媒体文件接口
	 */
	public function uploadPic_action($url) {
		$mpa = $this->getMpaccount();
		$mpproxy = $this->model('mpproxy/' . $mpa->mpsrc, $mpa->mpid);

		$media = $mpproxy->mediaUpload($url);
		if ($media[0] === false) {
			return new \ResponseError('上传图片失败：' . $media[1]);
		} else {
			return new \ResponseData($media[1]);
		}
	}
}