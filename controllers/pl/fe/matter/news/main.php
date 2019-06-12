<?php
namespace pl\fe\matter\news;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/**
 *
 */
class main extends \pl\fe\matter\main_base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/news/frame');
		exit;
	}
	/**
	 *
	 */
	public function setting_action() {
		\TPL::output('/pl/fe/matter/news/frame');
		exit;
	}
	/**
	 *
	 */
	public function get_action($site, $id, $cascade = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelNews = $this->model('matter\news');
		if ($n = $modelNews->byId($id)) {
			if (!empty($n->matter_mg_tag)) {
				$n->matter_mg_tag = json_decode($n->matter_mg_tag);
			}
			$n->uid = $user->id;
			if ($n->empty_reply_type && $n->empty_reply_id) {
				$n->emptyReply = $this->model('matter\base')->getMatterInfoById($n->empty_reply_type, $n->empty_reply_id);
			}

			if ($cascade === 'Y') {
				$n->matters = $modelNews->getMatters($n->id);
			}
		}

		return new \ResponseData($n);
	}
	/**
	 *
	 */
	public function list_action($site, $cascade = 'Y') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oOptions = $this->getPostJson();
		$modelNews = $this->model('matter\news');

		$q = [
			'*',
			'xxt_news n',
			"siteid = '" . $modelNews->escape($site) . "' and state = 1",
		];
		if (!empty($oOptions->byTitle)) {
			$q[2] .= " and title like '%" . $oOptions->byTitle . "%'";
		}
		if (!empty($oOptions->byCreator)) {
			$q[2] .= " and creater_name like '%" . $oOptions->byCreator . "%'";
		}
		if (!empty($oOptions->byTags)) {
			foreach ($oOptions->byTags as $tag) {
				$q[2] .= " and matter_mg_tag like '%" . $tag->id . "%'";
			}
		}
		if (isset($oOptions->byStar) && $oOptions->byStar === 'Y') {
			$q[2] .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='news' and t.matter_id=n.id and userid='{$oUser->id}')";
		}
		$q2['o'] = 'create_at desc';
		$news = $modelNews->query_objs_ss($q, $q2);
		/**
		 * 获得子资源
		 */
		if ($news) {
			$modelBase = $this->model('matter\base');
			foreach ($news as &$n) {
				$n->url = $modelNews->getEntryUrl($site, $n->id);
				$n->type = 'news';
				if ($n->empty_reply_type && $n->empty_reply_id) {
					$n->emptyReply = $modelBase->getMatterInfoById($n->empty_reply_type, $n->empty_reply_id);
				}
				if ($cascade === 'Y') {
					$n->matters = $modelNews->getMatters($n->id);
				}
			}
		}

		return new \ResponseData(['docs' => $news, 'total' => count($news)]);
	}
	/**
	 * 更新数据
	 */
	public function update_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelNews = $this->model('matter\news')->setOnlyWriteDbConn(true);
		$oNews = $modelNews->byId($id, 'id,title');
		if (false === $oNews) {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();
		$current = time();

		if ($oNews = $modelNews->modify($oUser, $oNews, $oPosted)) {
			$this->model('matter\log')->matterOp($site, $oUser, $oNews, 'U');
		}

		return new \ResponseData($oNews);
	}
	/**
	 * 更新多图文中的素材
	 *
	 * @param int $id 多图文的id
	 */
	public function updateMatter_action($id) {
		$matters = $this->getPostJson();

		$model = $this->model();
		/**
		 * delete relation.
		 */
		$model->delete('xxt_news_matter', ['news_id' => $id]);
		/**
		 * insert new relation.
		 */
		$this->_assignMatters($id, $matters);

		return new \ResponseData(count($matters));
	}
	/**
	 *
	 */
	private function _assignMatters($news_id, &$matters) {
		$model = $this->model();
		$model->setOnlyWriteDbConn(true);

		foreach ($matters as $i => $m) {
			$matter_id = $m->id;
			$matter_type = $m->type;
			$ns['news_id'] = $news_id;
			$ns['matter_id'] = $matter_id;
			$ns['matter_type'] = $matter_type;
			$ns['seq'] = $i;
			$model->insert('xxt_news_matter', $ns);
		}

		return true;
	}
	/**
	 * 创建一个多图文素材
	 */
	public function create_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oPosted = $this->getPostJson();
		$modelNews = $this->model('matter\news');
		$modelNews->setOnlyWriteDbConn(true);

		$oNews = new \stdClass;
		$oNews->siteid = $site;
		$oNews->mpid = $site;
		$oNews->title = isset($oPosted->title) ? $oPosted->title : '新多图文';

		$oNews = $modelNews->create($oUser, $oNews);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $oUser, $oNews, 'C');

		/* 指定包含的素材 */
		!empty($oPosted->matters) && $this->_assignMatters($oNews->id, $oPosted->matters);

		return new \ResponseData($oNews);
	}
	/**
	 * 删除一个多图文素材
	 */
	public function remove_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelNews = $this->model('matter\news');
		$oNews = $modelNews->byId($id, 'id,title');
		if (false === $oNews) {
			return new \ObjectNotFoundError();
		}

		$rst = $modelNews->remove($oUser, $oNews);

		return new \ResponseData($rst);
	}
	/**
	 * 内容为空时的回复
	 */
	public function setEmptyReply_action($id) {
		$matter = $this->getPostJson();

		$ret = $this->model()->update(
			'xxt_news',
			[
				'empty_reply_type' => $matter->mt,
				'empty_reply_id' => $matter->mid,
			],
			['id' => $id]
		);

		return new \ResponseData($ret);
	}
}