<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动事件通知接收人
 */
class enrollreceiver_model extends Reply {
	/**
	 *
	 */
	public function __construct($call, $appId) {
		parent::__construct($call);
		$this->appId = $appId;
	}
	/**
	 * 当前用户加入通知接受人
	 */
	public function exec($doResponse = true) {
		$siteId = $this->call['siteid'];
		$openId = $this->call['from_user'];
		$snsSrc = $this->call['src'];

		$modelAcnt = \TMS_APP::M('site\user\account');
		$oSiteUser = $modelAcnt->byPrimaryOpenid($siteId, $snsSrc, $openId);
		if ($oSiteUser) {
			if (!empty($oSiteUser->unionid) && isset($oSiteUser->is_reg_primary) && $oSiteUser->is_reg_primary !== 'Y') {
				$oRegUser = $modelAcnt->byPrimaryUnionid($siteId, $oSiteUser->unionid);
				if ($oRegUser) {
					$oSiteUser = $oRegUser;
				}
			}
			$oSnsUser = new \stdClass;
			$oSnsUser->src = $snsSrc;
			$oSnsUser->openid = $openId;
			$modelAcnt->insert(
				'xxt_enroll_receiver',
				[
					'siteid' => $siteId,
					'aid' => $this->appId,
					'join_at' => time(),
					'userid' => $oSiteUser->uid,
					'nickname' => $modelAcnt->escape($oSiteUser->nickname),
					'sns_user' => json_encode($oSnsUser),
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