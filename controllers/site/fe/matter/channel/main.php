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
	public function get_action($site, $id) {
		$data = array();

		$user = $this->who;
		$data['user'] = $user;

		$channel = $this->model('matter\channel')->byId($id);

		$channel->acl = $this->model('matter\acl')->byMatter($site, 'channel', $id);
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
		if ($page !== null && $size !== null) {
			$params->page = $page;
			$params->size = $size;
		}

		$modelChannel = $this->model('matter\channel');
		$site = $modelChannel->escape($site);
		$data = $modelChannel->getMattersNoLimit($id, $user->uid, $params);
		foreach ($data->matters as &$m) {
			$matterModel = \TMS_APP::M('matter\\' . $m->type);
			$m->url = $matterModel->getEntryUrl($site, $m->id);
		}

		return new \ResponseData($data);
	}
}