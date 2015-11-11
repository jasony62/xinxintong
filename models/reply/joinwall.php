<?php
namespace reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 加入信息墙
 */
class joinwall_model extends Reply {
	/**
	 *
	 */
	public function __construct($call, $wid, $keyword = '') {
		parent::__construct($call);
		$this->wid = $wid;
		$this->remark = trim(str_replace($keyword, '', $call['data']));
	}
	/**
	 * $doResponse 是否执行相应。因为易信二维码需要通过推送客服消息的方式返回相应。
	 */
	public function exec($doResponse = true) {
		/**
		 * 当前用户加入活动
		 */
		$wall = \TMS_APP::model('app\wall');
		$mpid = $this->call['mpid'];
		$openid = $this->call['from_user'];
		$desc = $wall->join($mpid, $this->wid, $openid, $this->remark);
		/**
		 * 返回活动加入成功提示
		 */
		if ($doResponse) {
			$r = $this->textResponse($desc);
			die($r);
		} else {
			return $desc;
		}
	}
}