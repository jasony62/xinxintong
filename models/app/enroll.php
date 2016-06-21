<?php
namespace app;

require_once dirname(dirname(__FILE__)) . '/matter/enroll.php';
/**
 * 登记活动
 */
class enroll_model extends \matter\enroll_model {
	/**
	 * 活动登记（不包括登记数据）
	 *
	 * $mpid 运行的公众号，和openid和src相对应
	 * $act
	 * $openid
	 * $vid
	 * $mid
	 */
	public function enroll($mpid, $act, $openid, $vid = '', $mid = '', $enroll_at = null) {
		$fan = \TMS_APP::M('user/fans')->byOpenid($mpid, $openid);
		$modelRec = \TMS_APP::M('app\enroll\record');
		$ek = $modelRec->genKey($mpid, $act->id);
		$i = array(
			'aid' => $act->id,
			'mpid' => $mpid,
			'enroll_at' => $enroll_at === null ? time() : $enroll_at,
			'enroll_key' => $ek,
			'openid' => $openid,
			'nickname' => !empty($fan) ? $fan->nickname : '',
			'vid' => $vid,
			'mid' => $mid,
		);
		$modelRun = \TMS_APP::M('app\enroll\round');
		if ($activeRound = $modelRun->getActive($mpid, $act->id)) {
			$i['rid'] = $activeRound->rid;
		}

		$this->insert('xxt_enroll_record', $i, false);

		return $ek;
	}
}