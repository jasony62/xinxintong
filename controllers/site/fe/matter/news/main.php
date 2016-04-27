<?php
namespace site\fe\matter\news;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 多图文
 */
class main extends \site\fe\matter\base {
	/**
	 *
	 */
	public function get_action($id, $cascade = 'Y') {
		$modelNews = $this->model('matter\news');
		$news = $modelNews->byId($id);
		if ($news->empty_reply_type && $news->empty_reply_id) {
			$news->emptyReply = $this->model('matter\base')->getMatterInfoById($news->empty_reply_type, $news->empty_reply_id);
		}

		if ($cascade === 'Y') {
			$news->matters = $modelNews->getMatters($news->id);
			foreach ($news->matters as &$m) {
				$matterModel = \TMS_APP::M('matter\\' . $m->type);
				$m->url = $matterModel->getEntryUrl($this->siteId, $m->id);
			}
			$news->acl = $this->model('acl')->byMatter($this->siteId, 'news', $news->id);
		}

		$data['news'] = $news;

		return new \ResponseData($data);
	}
}