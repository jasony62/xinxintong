<?php
namespace mi;

require_once dirname(dirname(__FILE__)) . '/member_base.php';

class channel extends \member_base {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	protected function canAccessObj($mpid, $matterId, $member, $authapis, &$matter) {
		return $this->model('acl')->canAccessMatter($mpid, 'channel', $matterId, $member, $authapis);
	}
	/**
	 *
	 */
	public function get_action($mpid, $id) {
		$data = array();

		$user = $this->getUser($mpid);
		$data['user'] = $user;

		$channel = $this->model('matter\channel')->byId($id);
		if (isset($channel->access_control) && $channel->access_control === 'Y') {
			$this->accessControl($mpid, $id, $channel->authapis, $user->openid, $channel, false);
		}

		//$channel->matters = $this->model('matter\channel')->getMatters($id, $channel, $mpid);
		//$channel->acl = $this->model('acl')->byMatter($mpid, 'channel', $id);
		$data['channel'] = $channel;

		return new \ResponseData($data);
	}
	/**
	 *
	 * $mpid
	 * $id channel's id
	 */
	public function mattersGet_action($mpid, $id, $orderby = 'time', $page = null, $size = null) {
		$vid = $this->getVisitorId($mpid);

		$params = new \stdClass;
		$params->orderby = $orderby;
		if ($page !== null && $size !== null) {
			$params->page = $page;
			$params->size = $size;
		}

		$matters = \TMS_APP::M('matter\channel')->getMattersNoLimit($id, $vid, $params);
		$tagModel = $this->model('tag');
		foreach ($matters as $m) {
			$matterModel = \TMS_APP::M('matter\\' . $m->type);
			$m->url = $matterModel->getEntryUrl($mpid, $m->id);
			$m->tags = $tagModel->tagsByRes($m->id, 'article');
		}

		return new \ResponseData($matters);
	}
}
