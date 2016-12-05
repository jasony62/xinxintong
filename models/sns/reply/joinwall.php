<?php
namespace sns\reply;

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
		$wall = \TMS_APP::model('matter\wall');
		$siteId = $this->call['siteid'];
		$user = new \stdClass;
		$user->openid = $this->call['from_user'];
		if($user->openid !== 'mocker'){
			switch ($this->call['src']) {
				case 'wx':
					//获取nickname
					$from_nickname = \TMS_APP::model('sns\wx\fan')->byOpenid($siteId, $user->openid, 'nickname,headimgurl');
					break;
				case 'yx':
					$from_nickname = \TMS_APP::model('sns\yx\fan')->byOpenid($siteId, $user->openid, 'nickname,headimgurl');
					break;
				case 'qy':
					$from_nickname = \TMS_APP::model('sns\qy\fan')->byOpenid($siteId, $user->openid, 'nickname,headimgurl');
					break;
			}
			$user->nickname = $from_nickname->nickname;
			$user->headimgurl = $from_nickname->headimgurl;
			$user->ufrom = $this->call['src'];
			//获取userid
			$options['fields'] = 'uid';
			$user2 = \TMS_APP::model('site\user\account')->byOpenid($siteId, $user->ufrom, $user->openid, $options);
			if($user2 === false){
				$user->userid = '';
			}else{
				$user->userid = $user2->uid;
			}
		}
		
		$desc = $wall->join($siteId, $this->wid, $user, $this->remark);
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