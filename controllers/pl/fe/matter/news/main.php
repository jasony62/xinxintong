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
		$user = $this->accountUser();
		if (false === $user) {
			return new \ResponseTimeout();
		}

		$n = $this->model('matter\news')->byId($id);
		$n->uid = $user->id;
		if ($n->empty_reply_type && $n->empty_reply_id) {
			$n->emptyReply = $this->model('matter\base')->getMatterInfoById($n->empty_reply_type, $n->empty_reply_id);
		}

		if ($cascade === 'Y') {
			$n->matters = $this->model('matter\news')->getMatters($n->id);
			$n->acl = $this->model('acl')->byMatter($site, 'news', $n->id);
		}

		return new \ResponseData($n);
	}
	/**
	 *
	 */
	public function list_action($site, $cascade = 'Y') {
		$user = $this->accountUser();

		$options = $this->getPostJson();
		$q = array(
			"n.*",
			'xxt_news n',
			"n.siteid='$site' and n.state=1",
		);
		$q2['o'] = 'create_at desc';
		$news = $this->model()->query_objs_ss($q, $q2);
		/**
		 * 获得子资源
		 */
		if ($news) {
			foreach ($news as &$n) {
				if ($n->empty_reply_type && $n->empty_reply_id) {
					$n->emptyReply = $this->model('matter\base')->getMatterInfoById($n->empty_reply_type, $n->empty_reply_id);
				}
				if ($cascade === 'Y') {
					$n->matters = $this->model('matter\news')->getMatters($n->id);
					$n->acl = $this->model('acl')->byMatter($site, 'news', $n->id);
				}
			}
		}

		return new \ResponseData($news);
	}
	/**
	 * 更新数据
	 */
	public function update_action($site, $id) {
		$user = $this->accountUser();
		$nv = $this->getPostJson();
		$current = time();

		$nv->modifier = $user->id;
		$nv->modifier_src = 'A';
		$nv->modifier_name = $user->name;
		$nv->modify_at = $current;
		/* 更新数据 */
		$rst = $this->model()->update(
			'xxt_news',
			$nv,
			"siteid='$site' and id='$id'"
		);
		/* 记录操作日志 */
		if ($rst) {
			$news = $this->model('matter\\' . 'news')->byId($id, 'id,title');
			$news->type = 'news';
			$this->model('log')->matterOp($site, $user, $news, 'U');
		}

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function updateMatter_action($id) {
		$matters = $this->getPostJson();
		/**
		 * delete relation.
		 */
		$this->model()->delete('xxt_news_matter', "news_id=$id");
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
		foreach ($matters as $i => $m) {
			$matter_id = $m->id;
			$matter_type = $m->type;
			$ns['news_id'] = $news_id;
			$ns['matter_id'] = $matter_id;
			$ns['matter_type'] = $matter_type;
			$ns['seq'] = $i;
			$this->model()->insert('xxt_news_matter', $ns);
		}

		return true;
	}
	/**
	 * 创建一个多图文素材
	 */
	public function create_action($site) {
		$user = $this->accountUser();
		$posted = $this->getPostJson();
		$current = time();

		$news = array();
		$news['siteid'] = $site;
		$news['creater'] = $user->id;
		$news['create_at'] = $current;
		$news['creater_src'] = 'A';
		$news['creater_name'] = $user->name;
		$news['modifier'] = $user->id;
		$news['modifier_src'] = 'A';
		$news['modifier_name'] = $user->name;
		$news['modify_at'] = $current;
		$news['title'] = isset($posted->title) ? $posted->title : '新多图文';
		$id = $this->model()->insert('xxt_news', $news, true);

		/* 指定包含的素材 */
		!empty($posted->matters) && $this->_assignMatters($id, $posted->matters);

		/* 记录操作日志 */
		$matter = (object) $news;
		$matter->id = $id;
		$matter->type = 'news';
		$this->model('log')->matterOp($site, $user, $matter, 'C');

		$news = $this->model('matter\news')->byId($id);

		return new \ResponseData($news);
	}
	/**
	 * 删除一个多图文素材
	 */
	public function delete_action($id) {
		$rst = $this->model()->update('xxt_news', array('state' => 0), "mpid='$this->mpid' and id=$id");

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
			array(
				'empty_reply_type' => $matter->mt,
				'empty_reply_id' => $matter->mid,
			),
			"id='$id'"
		);

		return new \ResponseData($ret);
	}
}