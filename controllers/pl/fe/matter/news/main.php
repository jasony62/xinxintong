<?php
namespace pl\fe\matter\news;

require_once dirname(dirname(__FILE__)) . '/base.php';

class main extends \pl\fe\matter\base {
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
			if(!empty($n->matter_mg_tag)){
				$n->matter_mg_tag = json_decode($n->matter_mg_tag);
			}
			$n->uid = $user->id;
			if ($n->empty_reply_type && $n->empty_reply_id) {
				$n->emptyReply = $this->model('matter\base')->getMatterInfoById($n->empty_reply_type, $n->empty_reply_id);
			}

			if ($cascade === 'Y') {
				$n->matters = $modelNews->getMatters($n->id);
				$n->acl = $this->model('acl')->byMatter($site, 'news', $n->id);
			}
		}

		return new \ResponseData($n);
	}
	/**
	 *
	 */
	public function list_action($site, $cascade = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$options = $this->getPostJson();
		$modelNews = $this->model('matter\news');

		$q = [
			'*',
			'xxt_news',
			"siteid = '". $modelNews->escape($site) ."' and state = 1"
		];
		if (!empty($options->byTitle)) {
			$q[2] .= " and title like '%". $modelNews->escape($options->byTitle) ."%'";
		}
		$q2['o'] = 'create_at desc';
		$news = $modelNews->query_objs_ss($q, $q2);
		/**
		 * 获得子资源
		 */
		if ($news) {
			$modelBase = $this->model('matter\base');
			$modelAcl = $this->model('acl');
			foreach ($news as &$n) {
				$n->url = $modelNews->getEntryUrl($site, $n->id);
				$n->type = 'news';
				if ($n->empty_reply_type && $n->empty_reply_id) {
					$n->emptyReply = $modelBase->getMatterInfoById($n->empty_reply_type, $n->empty_reply_id);
				}
				if ($cascade === 'Y') {
					$n->matters = $modelNews->getMatters($n->id);
					$n->acl = $modelAcl->byMatter($site, 'news', $n->id);
				}
			}
		}

		return new \ResponseData($news);
	}
	/**
	 * 更新数据
	 */
	public function update_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelNews = $this->model('matter\news');
		$modelNews->setOnlyWriteDbConn(true);
		$nv = $this->getPostJson();
		$current = time();

		$nv->modifier = $user->id;
		$nv->modifier_src = 'A';
		$nv->modifier_name = $user->name;
		$nv->modify_at = $current;
		/* 更新数据 */
		$rst = $modelNews->update(
			'xxt_news',
			$nv,
			['siteid' => $site, 'id' => $id]
		);
		/* 记录操作日志 */
		if ($rst) {
			$news = $modelNews->byId($id, 'id,title');
			$this->model('matter\log')->matterOp($site, $user, $news, 'U');
		}

		return new \ResponseData($rst);
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
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$current = time();

		$news = [];
		$news['siteid'] = $site;
		$news['mpid'] = $site;
		$news['creater'] = $user->id;
		$news['create_at'] = $current;
		$news['creater_src'] = 'A';
		$news['creater_name'] = $user->name;
		$news['modifier'] = $user->id;
		$news['modifier_src'] = 'A';
		$news['modifier_name'] = $user->name;
		$news['modify_at'] = $current;
		$news['title'] = isset($posted->title) ? $posted->title : '新多图文';

		$modelNews = $this->model('matter\news');
		$modelNews->setOnlyWriteDbConn(true);
		$id = $modelNews->insert('xxt_news', $news, true);

		/* 指定包含的素材 */
		!empty($posted->matters) && $this->_assignMatters($id, $posted->matters);

		/* 记录操作日志 */
		$matter = (object) $news;
		$matter->id = $id;
		$matter->type = 'news';
		$this->model('matter\log')->matterOp($site, $user, $matter, 'C');

		$news = $modelNews->byId($id);

		return new \ResponseData($news);
	}
	/**
	 * 删除一个多图文素材
	 */
	public function delete_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelNews = $this->model('matter\news');
		$matter = $modelNews->byId($id);
		$rst = $modelNews->update('xxt_news', ['state' => 0], ['siteid' => $site, 'id' => $id]);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $user, $matter, 'Recycle');

		return new \ResponseData($rst);
	}
	/**
	 * 恢复被删除的素材
	 */
	public function restore_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('matter\news');
		$matter = $model->byId($id);
		if (false === $matter) {
			return new \ResponseError('数据已经被彻底删除，无法恢复');
		}

		/* 恢复数据 */
		$rst = $model->update('xxt_news', ['state' => 1], ['siteid' => $site, 'id' => $id]);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $user, $matter, 'Restore');

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	protected function getMatterType() {
		return 'news';
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