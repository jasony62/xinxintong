<?php
namespace site\sns;
/**
 * convert xml message into an array object.
 */
class UserCall {
	/**
	 * --text
	 * Content
	 * --image
	 * PicUrl
	 * --locaton
	 * Location_X,Location_Y,Scale,Label
	 * --event
	 * Event,EventKey
	 * AgentID
	 */
	private $mapping = array(
		'msgid' => 'MsgId', //消息id，64位整型
		'to_user' => 'ToUserName', //开发者易信号
		'from_user' => 'FromUserName', //发送方帐号（一个OpenID）
		'create_at' => 'CreateTime', //消息创建时间 （整型,Unix时间戳）
		'type' => 'MsgType', //text,image,location
	);

	private $xml; // xml object
	/**
	 * $xlmstr call message xml string
	 */
	public function __construct($xmlstr, $mpid = '', $src = '') {
		$this->xml = new \DomDocument();
		$this->xml->loadXML($xmlstr);
		$this->mpid = $mpid;
		$this->src = $src;
	}

	public function __get($name) {
		$tag = !empty($this->mapping[$name]) ? $this->mapping[$name] : $name;
		$node = $this->xml->getElementsByTagName($tag);
		if ($node->item(0)) {
			return $node->item(0)->nodeValue;
		} else {
			return null;
		}
	}
	/**
	 * convert to an array.
	 */
	public function &to_array() {
		$a['src'] = $this->src;
		$a['mpid'] = $this->mpid;
		$a['msgid'] = $this->msgid;
		$a['to_user'] = $this->to_user;
		$a['from_user'] = $this->from_user;
		$a['create_at'] = $this->create_at;
		$a['type'] = $this->type;
		$this->AgentID !== null && $a['agentid'] = $this->AgentID;
		switch ($a['type']) {
		case 'text':
			$a['data'] = $this->Content;
			break;
		case 'image':
			// todo 易信的图片消息中没有MediaId
			$a['data'] = array($this->MediaId, $this->PicUrl);
			break;
		case 'voice':
			$a['data'] = array($this->MediaId, $this->Format, $this->Recognition);
			break;
		case 'audio':
			$a['data'] = array($this->Url, $this->name, $this->mimeType);
			break;
		case 'video':
			$a['data'] = array($this->MediaId, $this->ThumbMediaId);
			break;
		case 'location':
			$a['data'] = $this->location_data();
			break;
		case 'event':
			$a['data'] = $this->event_data();
			break;
		}

		return $a;
	}
	/**
	 *
	 */
	private function location_data() {
		$ld[] = $this->Location_X;
		$ld[] = $this->Location_Y;
		$ld[] = $this->Scale;
		$ld[] = $this->Label;
		return json_encode($ld);
	}
	/**
	 * subscribe/unsubscribe/CLICK/scan/TEMPLATESENDJOBFINISH
	 *
	 * return [eventType, eventData]
	 */
	private function event_data() {
		$e = $this->Event;
		$ek = '' . $this->EventKey;
		if (in_array($e, array('CLICK', 'click', 'SCAN', 'scan'))) {
			/**
			 * 菜单事件
			 * 已关注时的扫码事件
			 */
			$ed = array($e, $ek);
		} else if ($e === 'subscribe' && !empty($ek)) {
			/**
			 * 为关注时，扫描场景二维码事件
			 */
			$ed = array($e, $ek);
		} else if ($e === 'MASSSENDJOBFINISH') {
			/**
			 * 模板消息处理结果
			 */
			$ed = array(
				'Event' => $e,
				'MsgID' => $this->MsgID,
				'Status' => $this->Status,
				'TotalCount' => $this->TotalCount,
				'FilterCount' => $this->FilterCount,
				'SentCount' => $this->SentCount,
				'ErrorCount' => $this->ErrorCount,
			);
		} else if ($e === 'TEMPLATESENDJOBFINISH') {
			/**
			 * 模板消息处理结果
			 */
			$ed = array($e, $this->MsgID, $this->Status);
		} else if ($e === 'card_pass_check') {
			/**
			 * 卡卷审核通过事件
			 */
			$ed = array($e, $this->CardId);
		} else if ($e === 'card_not_pass_check') {
			/**
			 * 卡卷审核未通过事件
			 */
			$ed = array($e, $this->CardId);
		} else if ($e === 'user_get_card') {
			/**
			 * 领取卡卷事件
			 */
			$ed = array($e, $this->FriendUserName, $this->CardId, $this->IsGiveByFriend, $this->UserCardCode);
		} else if ($e === 'user_del_card') {
			/**
			 * 删除卡卷事件
			 */
			$ed = array($e, $this->CardId, $this->IsGiveByFriend);
		} else if ($e === 'LOCATION') {
			/**
			 * 易信自动上报地理位置
			 */
			$ed = array($e, $this->Latitude, $this->Longitude, $this->Precision);
		} else {
			$ed = array($e);
		}

		return json_encode($ed);
	}
}