<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 根据关键字检索单图文
 * todo 如果搜索不到是否应该给出提示呢？
 */
class fullsearch_model extends MultiArticleReply {

	private $keyword;

	public function __construct($call, $keyword) {
		parent::__construct($call, null);
		$this->keyword = $keyword;
	}

	protected function loadMatters() {
		$siteId = $this->call['siteid'];
		$page = 1;
		$limit = 10;
		$matters = \TMS_APP::model('matter\article2')->fullsearch_its($siteId, $this->keyword, $page, $limit);
		return $matters;
	}
}