<?php
namespace pl\fe\matter\text;

require_once dirname(dirname(__FILE__)) . '/base.php';

class main extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/text/frame');
		exit;
	}
	/**
	 *
	 */
	public function setting_action() {
		\TPL::output('/pl/fe/matter/text/frame');
		exit;
	}
	/**
	 *
	 */
	public function list_action($site, $fields = '*') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$post = $this->getPostJson();

		$q = [
			$fields,
			'xxt_text',
			"siteid = '". $model->escape($site) ."' and state = 1"
		];
		if (!empty($post->byTitle)) {
			$q[2] .= " and title like '%". $model->escape($post->byTitle) ."%'";
		}
		if (!empty($post->byTags)) {
			foreach ($post->byTags as $tag) {
				$q[2] .= " and matter_mg_tag like '%". $model->escape($tag->id) ."%'";
			}
		}

		$q2['o'] = 'create_at desc';
		$texts = $model->query_objs_ss($q, $q2);
		if($texts){
			foreach ($texts as $text) {
				!empty($text->matter_mg_tag) && $text->matter_mg_tag = json_decode($text->matter_mg_tag);
				$text->type = 'text';
			}
		}

		return new \ResponseData($texts);
	}
	/**
	 * 创建文本素材
	 */
	public function create_action($site) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$model->setOnlyWriteDbConn(true);
		$text = $this->getPostJson();

		$d = array();
		$d['siteid'] = $site;
		$d['creater'] = $user->id;
		$d['creater_name'] = $user->name;
		$d['create_at'] = time();
		$d['modifier'] = $user->id;
		$d['modifier_name'] = $user->name;
		$d['modify_at'] = time();
		$d['title'] = $model->escape($text->title);
		// @todo should remove
		$d['content'] = $model->escape($text->title);

		$id = $model->insert('xxt_text', $d, true);

		$q = [
			"*",
			'xxt_text',
			["id" => $id],
		];
		$text = $model->query_obj_ss($q);

		/* 记录操作日志 */
		$text->type = 'text';
		$this->model('matter\log')->matterOp($text->siteid, $user, $text, 'C');

		return new \ResponseData($text);
	}
	/**
	 *
	 */
	public function delete_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$text = $this->model('matter\text')->byId($id);
		$model = $this->model();
		$nv = new \stdClass;
		$nv->state = 0;
		$nv->modifier = $user->id;
		$nv->modifier_name = $user->name;
		$nv->modify_at = time();

		$rst = $model->update(
			'xxt_text',
			$nv,
			["siteid" => $site, "id" => $id]
		);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $user, $text, 'Recycle');

		return new \ResponseData($rst);
	}
	/**
	 * 恢复被删除的素材
	 */
	public function restore_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('matter\text');
		$text = $model->byId($id);
		if (false === $text) {
			return new \ResponseError('数据已经被彻底删除，无法恢复');
		}

		/* 恢复数据 */
		$nv = new \stdClass;
		$nv->state = 1;
		$nv->modifier = $user->id;
		$nv->modifier_name = $user->name;
		$nv->modify_at = time();
		$rst = $model->update('xxt_text', $nv, ['siteid' => $site, 'id' => $id]);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $user, $text, 'Restore');

		return new \ResponseData($rst);
	}
	/**
	 * 更新文本素材的属性
	 */
	public function update_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();
		$model->setOnlyWriteDbConn(true);
		$nv = $this->getPostJson();

		if (isset($nv->title)) {
			$nv->title = $model->escape($nv->title);
			$nv->content = $nv->title;
		}
		$nv->modifier = $user->id;
		$nv->modifier_name = $user->name;
		$nv->modify_at = time();

		$rst = $model->update(
			'xxt_text',
			$nv,
			["siteid" => $site, "id" => $id]
		);

		/* 记录操作日志 */
		$q = [
			"*",
			'xxt_text',
			["id" => $id],
		];
		$text = $model->query_obj_ss($q);
		$text->type = 'text';
		$this->model('matter\log')->matterOp($text->siteid, $user, $text, 'U');

		return new \ResponseData($rst);
	}
}