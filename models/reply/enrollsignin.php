<?php
namespace reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 活动签到
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
		$mpid = $this->call['mpid'];
		$openid = $this->call['from_user'];

		$model = \TMS_APP::model('app\enroll');
		$act = $model->byId($this->aid);
		$rst = $model->signin($mpid, $this->aid, $openid);
		/**
		 * 回复
		 */
		if ($rst) {
			if ($act->success_matter_type && $act->success_matter_id) {
				$cls = $act->success_matter_type;
				if ($this->directReply === true) {
					$r = \TMS_APP::model('reply\\' . $cls, $this->call, $act->success_matter_id);
				} else {
					return array('matter_type' => $act->success_matter_type, 'matter_id' => $act->success_matter_id);
				}

			} else {
				$r = \TMS_APP::model('reply\text', $this->call, "活动【" . $act->title . "】已签到，已登记", false);
			}
		} else {
			if ($act->failure_matter_type && $act->failure_matter_id) {
				$cls = $act->failure_matter_type;
				if ($this->directReply === true) {
					$r = \TMS_APP::model('reply\\' . $cls, $this->call, $act->failure_matter_id);
				} else {
					return array('matter_type' => $act->failure_matter_type, 'matter_id' => $act->failure_matter_id);
				}

			} else {
				$r = \TMS_APP::model('reply\text', $this->call, "活动【" . $act->title . "】已签到，未登记", false);
			}
		}
		$r->exec();
	}
}