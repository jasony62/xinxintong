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
		$model=\TMS_APP::model('matter\article');
		$page = 1;
		$limit = 10;
		$matters = $model->fullsearch_its($siteId, $this->keyword, $page, $limit);
		foreach ($matters as &$matter) {
			$matter->entryURL = $model->getEntryUrl($this->call['siteid'], $matter->id);
		}
		return $matters;
	}

	/**
	 * 生成回复消息
	 */
	public function exec() {
		$matters = $this->loadMatters();
		if(empty($matters)){
			$r=$this->textResponse("找不到包含【".$this->keyword."】的文章，请尝试更换关键词继续搜索。");
		}else{
			$r = $this->cardResponse($matters);
		}
		die($r);
	}
}