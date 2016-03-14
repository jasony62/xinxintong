<?php
namespace reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 单图文信息卡片
 */
class article_model extends MultiArticleReply {

	protected function loadMatters() {
		$article = \TMS_APP::model('matter\article')->byId($this->set_id, 'id,title,summary,pic');
		$article->type = 'article';

		return array($article);
	}
}