<?php
namespace pl\fe\site\sns\yx;

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/base.php';
/**
 * 易信公众号
 */
class send extends \pl\fe\base {
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
	 * 群发消息
	 */
	private function _send2group($siteId, $message, $matter, &$warning) {
		$user = $this->accountUser();

		$wxConfig = $this->model('sns\yx')->bySite($siteId);
		$proxy = $this->model("sns\yx\proxy", $wxConfig);

		$rst = $proxy->send2group($message);
		if ($rst[0] === true) {
			$msgid = isset($rst[1]->msg_id) ? $rst[1]->msg_id : 0;
			$this->model('log')->mass($user->id, $siteId, $matter->type, $matter->id, $message, $msgid, 'ok');
		} else {
			$warning[] = $rst[1];
			$this->model('log')->mass($user->id, $siteId, $matter->type, $matter->id, $message, 0, $rst[1]);
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
	public function mass_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		// 要发送的素材
		$matter = $this->getPostJson();
		if (empty($matter->userSet)) {
			return new \ResponseError('请指定接收消息的用户');
		}
		// 要接收的用户
		$userSet = $matter->userSet;
		/**
		 * send message.
		 */
		$message = $this->assemble_custom_message($site, $matter);
		if (empty($message)) {
			return new \ResponseError('指定的素材无法向微信用户群发！');
		}
		/**
		 * send
		 */
		if ($userSet[0]->identity === -1) {
			/**
			 * 发送给所有用户
			 */
			$this->_send2group($site, $message, $matter, $warning);
		} else {
			/**
			 * 发送给指定的关注用户组
			 */
			foreach ($userSet as $us) {
				$message['group'] = $us->label;
				$this->_send2group($site, $message, $matter, $warning);
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

		$message = $this->assemble_custom_message($matter);
		$rst = $this->sent2YxUserByp2p($this->mpid, $message, $openids);

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
	private function assemble_custom_message($siteId, $matter) {
		$model = $this->model('matter\\' . $matter->type);
		$message = $model->forCustomPush($siteId, $matter->id);

		return $message;
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