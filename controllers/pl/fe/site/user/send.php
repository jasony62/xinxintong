<?php
namespace pl\fe\site\user;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';

class send extends \pl\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 * 尽最大可能向用户发送消息
	 *
	 * $site
	 * $openid
	 * $message
	 */
	protected function sendByOpenid($site, $openid, $message, $openid_src = 'wx') {
		$model = $this->model();

		switch ($openid_src) {
		case 'yx':
			$config = $this->model('sns\yx')->bySite($site);
			if ($config->joined === 'Y' && $config->can_p2p === 'Y') {
				$rst = $this->model('sns\yx\proxy', $config)->messageSend($message, array($openid));
			} else {
				$rst = $this->model('sns\yx\proxy', $config)->messageCustomSend($message, $openid);
			}
			break;
		case 'wx':
			$config = $this->model('sns\wx')->bySite($site);
			$rst = $this->model('sns\wx\proxy', $config)->messageCustomSend($message, $openid);
			break;
		case 'qy':
			$config = $this->model('sns\qy')->bySite($site);
			$message['touser'] = $openid;
			$message['agentid'] = $config->agentid;
			$rst = $this->model('sns\qy\proxy', $config)->messageSend($message, $openid);
			break;
		default:
			$rst = array(false);
		}
		return $rst;
	}
	/**
	 * 发送客服消息
	 *
	 * 需要开通高级接口
	 */
	public function custom_action($site, $openid, $src = 'wx') {
		$model = $this->model();
		/**
		 * 检查是否开通了群发接口
		 */
		if ($src == 'wx' || $src == 'yx') {
			$config = $model->query_obj_ss(['*', 'xxt_site_' . $src, "siteid='$site'"]);

			if (empty($config)) {
				return new \ResponseError('没有绑定公众号信息');
			}

			if ($config->can_custom_push === 'N') {
				return new \ResponseError('未开通群发高级接口，请检查！');
			}

			$group_id = $model->query_val_ss(['groupid', 'xxt_site_' . $src . 'fan', "siteid='$site' and openid='$openid'"]);
		}
		/**
		 * get matter.
		 */
		$matter = $this->getPostJson();
		if (isset($matter->id)) {
			$message = $this->assemble_custom_message($site, $matter);
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
		$rst = $this->sendByOpenid($site, $openid, $message, $src);

		if (false === $rst[0]) {
			return new \ResponseError($rst[1]);
		}
		/**
		 * 记录日志
		 */
		$group_id = empty($group_id) ? null : $group_id;

		if (isset($matter->id)) {
			$this->model('matter\log')->send($site, $openid, $group_id, $matter->title, $matter);
		} else {
			$this->model('matter\log')->send($site, $openid, null, $matter->text, null);
		}

		return new \ResponseData('success');
	}
	/**
	 * 管理员向公众号用户群发信息
	 */
	private function send2group($src, $site, $message, $matter, &$warning) {
		//管理员的UID也就是谁发的信息
		$uid = \TMS_CLIENT::get_client_uid();

		$config = $this->model()->query_obj_ss(['*', 'xxt_site_' . $src, "siteid='$site'"]);
		$proxy = $this->model('sns\\' . $src . '\\proxy', $config);

		$rst = $proxy->send2group($message);
		if ($rst[0] === true) {
			$msgid = isset($rst[1]->msg_id) ? $rst[1]->msg_id : 0;
			$this->model('matter\log')->mass($uid, $site, $matter->type, $matter->id, $message, $msgid, 'ok');
		} else {
			$warning[] = $rst[1];
			$this->model('matter\log')->mass($uid, $site, $matter->type, $matter->id, $message, 0, $rst[1]);
		}

		return true;
	}
	/**
	 * 将用户集转换为openid
	 *
	 * $param userSet
	 */
	protected function getOpenid($site, $userSet) {
		$openids = array();
		foreach ($userSet as $us) {
			switch ($us->idsrc) {
			case 'D':
				$deptid = $us->identity;
				$q = array(
					'openid',
					'xxt_site_qyfan',
					"siteid='$site' and depts like '%\"$deptid\"%'",
				);
				$fans = $this->model()->query_objs_ss($q);
				foreach ($fans as $fan) {
					!in_array($fan->openid, $openids) && $openids[] = $fan->openid;
				}
				break;
			case 'T':
				$tagids = explode(',', $us->identity);
				$model = $this->model();
				$q = array(
					'openid',
					'xxt_site_qyfan',
				);
				foreach ($tagids as $tagid) {
					$q[2] = "siteid='$site' and tags like '%$tagid%'";
					$fans = $this->model()->query_objs_ss($q);
					foreach ($fans as $fan) {
						!in_array($fan->openid, $openids) && $openids[] = $fan->openid;
					}
				}
				break;
			case 'DT':
				$deptAndTagIds = explode(',', $us->identity);
				$deptid = $deptAndTagIds[0];
				$tagids = array_slice($deptAndTagIds, 1);
				$model = $this->model();
				$q = array(
					'openid',
					'xxt_site_qyfan',
				);
				foreach ($tagids as $tagid) {
					$q[2] = "siteid='$site' and depts like '%\"$deptid\"%' and tags like '%$tagid%'";
					$fans = $model->query_objs_ss($q);
					foreach ($fans as $fan) {
						!in_array($fan->openid, $openids) && $openids[] = $fan->openid;
					}
				}
				break;
			case 'M':
				$model = $this->model();
				$mid = $us->identity;
				$member = $model->query_obj_ss(['*', 'xxt_site_member', "siteid='$site' and id='$mid'"]);

				$fan = $model->query_obj_ss([
					'ufrom,yx_openid,wx_openid,qy_openid',
					'xxt_site_account',
					"siteid='$site' and uid='$member->userid'",
				]);

				if (empty($fan->ufrom)) {
					return array(false, '无法获得当前用户的openid');
				}

				$openids[] = $fan->{$fan->ufrom . '_openid'};
				break;
			}
		}

		return array(true, $openids);
	}
	/**
	 * 向企业号用户发送消息
	 *
	 * $mpid
	 * $message
	 */
	public function send2Qyuser($site, $message, $encoded = false) {
		$config = $this->model()->query_obj_ss(['*', 'xxt_site_qy', "siteid='$site'"]);

		$proxy = $this->model('sns\\qy\\proxy', $config);

		$rst = $proxy->messageSend($message, $encoded);

		return $rst;
	}
	/**
	 * 群发消息
	 * 需要开通高级接口
	 *
	 * 开通了群发接口的微信和易信公众号
	 * 微信企业号
	 * 开通了点对点认证接口的易信公众号
	 */
	public function mass_action($site, $src = 'wx') {
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
			if ($src === 'wx') {
				/**
				 * 微信的图文群发消息需要上传到公众号平台，所以链接素材无法处理
				 */
				$model = $this->model('matter\\' . $matter->type);
				if ($matter->type === 'text') {
					$message = $model->forCustomPush($site, $matter->id, 'OLD');
				} else if (in_array($matter->type, array('article', 'news', 'channel'))) {
					$message = $model->forWxGroupPush($site, $matter->id, 'OLD');
				}

			} else if ($src === 'yx') {
				$message = $this->assemble_custom_message($site, $matter);
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
				$src === 'wx' && $message['filter'] = array('is_to_all' => true);
				$this->send2group($src, $site, $message, $matter, $warning);
			} else {
				/**
				 * 发送给指定的关注用户组
				 */
				if ($src === 'wx') {
					foreach ($userSet as $us) {
						$message['filter'] = array(
							'is_to_all' => false,
							'group_id' => $us->identity,
						);
						$this->send2group($src, $site, $message, $matter, $warning);
					}
				} else if ($src === 'yx') {
					$message = $this->assemble_custom_message($site, $matter);
					foreach ($userSet as $us) {
						$message['group'] = $us->label;
						$this->send2group($src, $site, $message, $matter, $warning);
					}
				}
			}
		} else {
			/**
			 * 发送给认证用户
			 */
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
	public function yxmember_action($site, $phase = 0, $sizeOfBatch = 20) {
		if ($phase == 0) {
			$matter = $this->getPostJson();
			if (empty($matter->targetUser) || empty($matter->userSet)) {
				return new \ResponseError('请指定接收消息的用户');
			}
			/*消息*/
			$model = $this->model('matter\\' . $matter->type);
			$message = $model->forCustomPush($site, $matter->id);
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
			$rst = $this->send2YxUserByP2p($site, $message, $batch);
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
	 * 通过微信
	 */
	public function send2WxuserByPreview($site, $message, $openid) {
		$config = $this->model()->query_obj_ss(['*', 'xxt_site_wx', "siteid='$site'"]);
		$proxy = $this->model('sns\\wx\\proxy', $config);

		$rst = $proxy->messageMassPreview($message, $openid);

		return $rst;
	}
	/**
	 * 通过易信点对点接口向用户发送消息
	 *
	 * $mpid
	 * $message
	 * $openids
	 */
	public function send2YxUserByP2p($site, $message, $openids) {
		$config = $this->model()->query_obj_ss(['*', 'xxt_site_yx', "siteid='$site'"]);
		$proxy = $this->model('sns\\yx\\proxy', $config);

		$rst = $proxy->messageSend($message, $openids);

		return $rst;
	}
	/**
	 * 预览消息
	 *
	 * 开通预览接口的微信公众号
	 * 开通点对点消息的易信公众奥
	 * 微信企业号
	 */
	public function preview_action($site, $src = 'wx', $matterId, $matterType, $openids) {
		if ($src === 'wx') {
			$model = $this->model('matter\\' . $matterType);
			if ($matterType === 'text') {
				$message = $model->forCustomPush($site, $matterId);
			} else if (in_array($matterType, array('article', 'news', 'channel'))) {
				/**
				 * 微信的图文群发消息需要上传到公众号平台，所以链接素材无法处理
				 */
				$message = $model->forWxGroupPush($site, $matterId);
			}
			$rst = $this->send2WxuserByPreview($site, $message, $openids);
		} else if ($src === 'yx') {
			$message = $this->assemble_custom_message($site, $matter);
			$rst = $this->send2YxUserByP2p($site, $message, $openids);
		} else if ($src === 'qy') {
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
	 * 根据指定的素材，组装客服消息
	 */
	private function assemble_custom_message($site, $matter) {
		$model = $this->model('matter\\' . $matter->type);
		$message = $model->forCustomPush($site, $matter->id);

		return $message;
	}
	/**
	 * 发送模板消息
	 *
	 * $tid 模板消息id
	 */
	public function tmplmsg_action($site, $tid) {
		$posted = $this->getPostJson();

		if (isset($posted->matter)) {
			$url = $this->model('matter\\' . $posted->matter->type)->getEntryUrl($site, $posted->matter->id);
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
			$rst = $this->tmplmsgSendByOpenid($site, $tid, $openid, $data, $url);
			if ($rst[0] === false) {
				return new \ResponseError($rst[1]);
			}

		}

		return new \ResponseData('success');
	}
	/**
	 *
	 */
	public function tmplmsglog_action($site, $tid, $page, $size) {
		$model = $this->model();
		$q = array(
			'id,template_id,msgid,openid,data,create_at,status',
			'xxt_log_tmplmsg',
			"mpid='$site' and tmplmsg_id=$tid",
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
	public function uploadPic_action($site, $src = 'wx', $url) {
		$config = $this->model()->query_obj_ss(['*', 'xxt_site_' . $src, "siteid='$site'"]);
		$proxy = $this->model('sns\\' . $src . '\\proxy', $config);

		$media = $proxy->mediaUpload($url);
		if ($media[0] === false) {
			return new \ResponseError('上传图片失败：' . $media[1]);
		} else {
			return new \ResponseData($media[1]);
		}
	}
}