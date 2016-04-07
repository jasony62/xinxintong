<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 单图文信息卡片
 */
class article_model extends MultiArticleReply {

	protected function loadMatters() {
		$model = \TMS_APP::model('matter\article2');
		$article = $model->byId($this->set_id, 'id,title,summary,pic');
		$article->type = 'article';
		$article->entryURL = $model->getEntryUrl($this->call['siteid'], $article->id, $this->call['from_user']);

		return array($article);
	}
}