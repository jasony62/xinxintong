<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动签到
 */
class enrollsignin_model extends Reply {
	/**
	 *
	 */
	public function __construct($call, $aid, $directReply = true) {
		parent::__construct($call);
		$this->aid = $aid;
		$this->directReply = $directReply;
	}
	/**
	 *
	 */
	public function exec() {
		/**
		 * 当前用户活动签到
		 */
		$siteId = $this->call['siteid'];
		$openid = $this->call['from_user'];

		$model = \TMS_APP::model('matter\enroll');
		$app = $model->byId($this->aid);
		$rst = $model->signin($siteId, $this->aid, $openid);
		/**
		 * 回复
		 */
		if ($rst) {
			if ($app->success_matter_type && $app->success_matter_id) {
				$cls = $app->success_matter_type;
				if ($this->directReply === true) {
					$r = \TMS_APP::model('sns\reply\\' . $cls, $this->call, $app->success_matter_id);
				} else {
					return array('matter_type' => $app->success_matter_type, 'matter_id' => $app->success_matter_id);
				}

			} else {
				$r = \TMS_APP::model('sns\reply\text', $this->call, "活动【" . $app->title . "】已签到，已登记", false);
			}
		} else {
			if ($app->failure_matter_type && $app->failure_matter_id) {
				$cls = $app->failure_matter_type;
				if ($this->directReply === true) {
					$r = \TMS_APP::model('sns\reply\\' . $cls, $this->call, $app->failure_matter_id);
				} else {
					return array('matter_type' => $app->failure_matter_type, 'matter_id' => $app->failure_matter_id);
				}

			} else {
				$r = \TMS_APP::model('sns\reply\text', $this->call, "活动【" . $app->title . "】已签到，未登记", false);
			}
		}
		$r->exec();
	}
}