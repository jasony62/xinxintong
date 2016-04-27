<?php
namespace site\fe\matter\channel;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 频道
 */
class main extends \site\fe\matter\base {
	/**
	 *
	 */
	protected function canAccessObj($site, $matterId, $member, $authapis, &$matter) {
		return $this->model('matter\acl')->canAccessMatter($site, 'channel', $matterId, $member, $authapis);
	}
	/**
	 *
	 */
	public function get_action($site, $id) {
		$data = array();

		$user = $this->who;
		$data['user'] = $user;

		$channel = $this->model('matter\channel')->byId($id);
		if (isset($channel->access_control) && $channel->access_control === 'Y') {
			$this->accessControl($site, $id, $channel->authapis, $user->uid, $channel, false);
		}

		//$channel->matters = $this->model('matter\channel')->getMatters($id, $channel, $site);
		//$channel->acl = $this->model('acl')->byMatter($site, 'channel', $id);
		$data['channel'] = $channel;

		return new \ResponseData($data);
	}
	/**
	 *
	 * $site
	 * $id channel's id
	 */
	public function mattersGet_action($site, $id, $orderby = 'time', $page = null, $size = null) {
		$user = $this->who;

		$params = new \stdClass;
		$params->orderby = $orderby;
		if ($page !== null && $size !== null) {
			$params->page = $page;
			$params->size = $size;
		}

		$matters = \TMS_APP::M('matter\channel')->getMattersNoLimit($id, $user->uid, $params);
		$tagModel = $this->model('tag');
		foreach ($matters as &$m) {
			$matterModel = \TMS_APP::M('matter\\' . $m->type);
			$m->url = $matterModel->getEntryUrl($site, $m->id);
			if ($m->type === 'article') {
				$m->tags = $tagModel->tagsByRes($m->id, 'article');
			}
		}

		return new \ResponseData($matters);
	}
}