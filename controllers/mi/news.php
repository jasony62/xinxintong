<?php
namespace mi;

require_once dirname(dirname(__FILE__)) . '/member_base.php';

class news extends \member_base {
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
		return $this->model('acl')->canAccessMatter($mpid, 'news', $matterId, $member, $authapis);
	}
	/**
	 *
	 */
	public function get_action($mpid, $id) {
		$data = array();

		$user = $this->getUser($mpid, array('verbose' => array('member' => true)));
		$data['user'] = $user;
		//
		$news = $this->model('matter\news')->byId($id);
		if (isset($news->access_control) && $news->access_control === 'Y') {
			$this->accessControl($mpid, $id, $news->authapis, $user->openid, $news, false);
		}

		$matters = \TMS_APP::M('matter\news')->getMatters($id);
		$modelAcl = \TMS_APP::M('acl');

		$matters2 = array();
		foreach ($matters as $m) {
			if ($m->access_control === 'Y' && $news->filter_by_matter_acl === 'Y') {
				$inacl = false;
				foreach ($user->members as $member) {
					if ($modelAcl->canAccessMatter($mpid, $m->type, $m->id, $member, $m->authapis)) {
						$inacl = true;
						break;
					}
				}
				if (!$inacl) {
					continue;
				}

			}
			$m->url = \TMS_APP::M('matter\\' . $m->type)->getEntryUrl($mpid, $m->id, $user->openid);
			$matters2[] = $m;
		}

		if (count($matters2) === 0 && !empty($news->empty_reply_type) && !empty($news->empty_reply_id)) {
			$modelMatter = \TMS_APP::M('matter\\' . $news->empty_reply_type);
			$matter = $modelMatter->byId($news->empty_reply_id);
			$matter->url = $modelMatter->getEntryUrl($mpid, $news->empty_reply_id, $user->openid);
			$news->matters = array($matter);
		} else {
			$news->matters = $matters2;
		}
		$data['news'] = $news;

		return new \ResponseData($data);
	}
}