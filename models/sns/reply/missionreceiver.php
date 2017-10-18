<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动事件通知接收人
 */
class missionreceiver_model extends Reply {
	/**
	 *
	 */
	public function __construct($call, $appId) {
		parent::__construct($call);
		$this->appId = $appId;
	}
	/**
	 *
	 */
	public function exec($doResponse = true) {
		/**
		 * 当前用户加入通知接受人
		 */
		$modelEnl = \TMS_APP::model('matter\mission\receiver');
		$siteId = $this->call['siteid'];
		$openId = $this->call['from_user'];
		$snsSrc = $this->call['src'];

		$fan = \TMS_APP::M('sns\\' . $snsSrc . '\\fan')->byOpenid($siteId, $openId);
		if ($fan) {
			$snsUser = new \stdClass;
			$snsUser->src = $snsSrc;
			$snsUser->openid = $openId;
			$modelEnl->insert(
				'xxt_mission_receiver',
				[
					'siteid' => $siteId,
					'aid' => $this->appId,
					'join_at' => time(),
					'userid' => $fan->userid,
					'nickname' => $fan->nickname,
					'sns_user' => json_encode($snsUser),
				],
				false
			);
			$desc = '加入成功';
		} else {
			$desc = '加入失败';
		}
		/**
		 * 返回成功提示
		 */
		if ($doResponse) {
			$r = $this->textResponse($desc);
			die($r);
		} else {
			return $desc;
		}
	}
}