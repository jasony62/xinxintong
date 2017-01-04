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
		if($this->call['from_user'] !== 'mocker'){
			switch ($this->call['src']) {
				case 'wx':
					//获取nickname
					$from_nickname = \TMS_APP::model('sns\wx\fan')->byOpenid($siteId, $this->call['from_user'], 'nickname,headimgurl');
					$user->wx_openid = $this->call['from_user'];
					break;
				case 'yx':
					$from_nickname = \TMS_APP::model('sns\yx\fan')->byOpenid($siteId, $this->call['from_user'], 'nickname,headimgurl');
					$user->yx_openid = $this->call['from_user'];
					break;
				case 'qy':
					$from_nickname = \TMS_APP::model('sns\qy\fan')->byOpenid($siteId, $this->call['from_user'], 'nickname,headimgurl');
					$user->qy_openid = $this->call['from_user'];
					break;
			}
			$user->nickname = $from_nickname->nickname;
			$user->headimgurl = $from_nickname->headimgurl;
			//获取userid
			$options['fields'] = 'uid';
			$user2 = \TMS_APP::model('site\user\account')->byOpenid($siteId, $this->call['src'], $this->call['from_user'], $options);
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