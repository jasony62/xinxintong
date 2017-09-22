<?php
namespace pl\fe\matter\link;

require_once dirname(dirname(__FILE__)) . '/base.php';

class main extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/link/frame');
		exit;
	}
	/**
	 *
	 */
	public function setting_action() {
		\TPL::output('/pl/fe/matter/link/frame');
		exit;
	}
	/**
	 *
	 */
	public function get_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();
		$options = $this->getPostJson();

		$q = array(
			"*",
			'xxt_link',
			"siteid='$site' and id='$id' and state=1",
		);
		if ($link = $model->query_obj_ss($q)) {
			$link->type = 'link';
			!empty($link->matter_mg_tag) && $link->matter_mg_tag = json_decode($link->matter_mg_tag);
			/**
			 * params
			 */
			$q = array(
				'id,pname,pvalue',
				'xxt_link_param',
				"link_id='$id'",
			);
			$link->params = $model->query_objs_ss($q);
			/**
			 * channels
			 */
			$link->channels = $this->model('matter\channel')->byMatter($id, 'link');
			/**
			 * acl
			 */
			$link->acl = $this->model('acl')->byMatter($site, 'link', $id);
		}

		return new \ResponseData($link);
	}
	/**
	 *
	 */
	public function list_action($site, $cascade = 'Y') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();
		$site = $model->escape($site);
		$oOptions = $this->getPostJson();
		/**
		 * get links
		 */
		$q = [
			"*",
			'xxt_link l',
			"siteid='$site' and state=1",
		];
		if (!empty($oOptions->byTitle)) {
			$q[2] .= " and title like '%" . $model->escape($oOptions->byTitle) . "%'";
		}
		if (!empty($oOptions->byTags)) {
			foreach ($oOptions->byTags as $tag) {
				$q[2] .= " and matter_mg_tag like '%" . $model->escape($tag->id) . "%'";
			}
		}
		if (isset($oOptions->byStar) && $oOptions->byStar === 'Y') {
			$q[2] .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='article' and t.matter_id=l.id and userid='{$oUser->id}')";
		}
		$q2['o'] = 'create_at desc';
		$links = $model->query_objs_ss($q, $q2);
		/**
		 * get params and channels
		 */
		if ($cascade === 'Y') {
			$modelChn = $this->model('matter\channel');
			$modelAcl = $this->model('acl');
			foreach ($links as $l) {
				/**
				 * params
				 */
				$q = array('id,pname,pvalue',
					'xxt_link_param',
					"link_id=$l->id");
				$l->params = $model->query_objs_ss($q);
				/**
				 * channels
				 */
				$l->channels = $modelChn->byMatter($l->id, 'link');
				/**
				 * acl
				 */
				$l->acl = $modelAcl->byMatter($site, 'link', $l->id);
				$l->type = 'link';
			}
		}

		return new \ResponseData(['docs' => $links, 'total' => count($links)]);
	}
	/**
	 *
	 */
	public function cascade_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/**
		 * params
		 */
		$q = array(
			'id,pname,pvalue',
			'xxt_link_param',
			"link_id='$id'",
		);
		$l['params'] = $this->model()->query_objs_ss($q);
		/**
		 * channels
		 */
		$l['channels'] = $this->model('matter\channel')->byMatter($id, 'link');
		/**
		 * acl
		 */
		$l['acl'] = $this->model('acl')->byMatter($site, 'link', $id);

		return new \ResponseData($l);
	}
	/**
	 * 创建外部链接素材
	 */
	public function create_action($site, $title = '新链接') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelSite = $this->model('site');
		$site = $modelSite->byId($site, ['fields' => 'id,heading_pic']);
		$current = time();
		$link = array();
		$link['siteid'] = $site->id;
		$link['creater'] = $user->id;
		$link['creater_name'] = $user->name;
		$link['create_at'] = $current;
		$link['modifier'] = $user->id;
		$link['modifier_name'] = $user->name;
		$link['modify_at'] = $current;
		$link['title'] = $title;
		$link['pic'] = $site->heading_pic; //使用站点缺省头图

		$id = $modelSite->insert('xxt_link', $link, true);
		$link = $this->model('matter\link')->byId($id);

		/* 记录操作日志 */
		$matter = $link;
		$this->model('matter\log')->matterOp($site->id, $user, $matter, 'C');

		return new \ResponseData($link);
	}
	/**
	 * 删除链接
	 */
	public function remove_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();

		$link = array();
		$link['modifier'] = $user->id;
		$link['modifier_name'] = $user->name;
		$link['modify_at'] = time();
		$link['state'] = 0;
		$rst = $model->update(
			'xxt_link',
			$link,
			['siteid' => $site, 'id' => $id]
		);
		/*记录操作日志*/
		$link = $this->model('matter\link')->byId($id, 'id,title,summary,pic');
		$link->type = 'link';
		$this->model('matter\log')->matterOp($site, $user, $link, 'Recycle');

		return new \ResponseData($rst);
	}
	/**
	 * 恢复被删除的素材
	 */
	public function restore_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('matter\link');
		$link = $model->byId($id, 'id,title,summary,pic');
		if (false === $link) {
			return new \ResponseError('数据已经被彻底删除，无法恢复');
		}

		/* 恢复数据 */
		$update = array();
		$update['modifier'] = $user->id;
		$update['modifier_name'] = $user->name;
		$update['modify_at'] = time();
		$update['state'] = 1;
		$rst = $model->update('xxt_link', $update, ['siteid' => $site, 'id' => $id]);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $user, $link, 'Restore');

		return new \ResponseData($rst);
	}
	/**
	 * 更新链接属性
	 */
	public function update_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$model = $this->model();

		$link = $this->getPostJson();
		$link->modifier = $user->id;
		$link->modifier_name = $user->name;
		$link->modify_at = time();

		$ret = $model->update(
			'xxt_link',
			$link,
			"siteid='$site' and id=$id"
		);
		/*记录操作日志*/
		$link = $this->model('matter\link')->byId($id, 'id,title,summary,pic');
		$link->type = 'link';
		$this->model('matter\log')->matterOp($site, $user, $link, 'U');

		return new \ResponseData($ret);
	}
	/**
	 *
	 * @param $linkid link's id
	 */
	public function paramAdd_action($site, $linkid) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$p['link_id'] = $linkid;

		$id = $this->model()->insert('xxt_link_param', $p);

		return new \ResponseData($id);
	}
	/**
	 *
	 * 更新参数定义
	 *
	 * 因为参数的属性之间存在关联，因此要整体更新
	 *
	 * @param $id parameter's id
	 */
	public function paramUpd_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$p = $this->getPostJson();

		!empty($p->pvalue) && $p->pvalue = urldecode($p->pvalue);

		$rst = $this->model()->update(
			'xxt_link_param',
			$p,
			"id=$id"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * @param $id parameter's id
	 */
	public function removeParam_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$rst = $this->model()->delete('xxt_link_param', "id=$id");

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	protected function getMatterType() {
		return 'link';
	}
}