<?php
namespace pl\fe\site\sns\yx;

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/base.php';
/**
 * 易信公众号
 */
class text extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/sns/yx/main');
		exit;
	}
	/**
	 * get all text call.
	 */
	public function list_action($site, $cascade = 'Y') {
		$calls = array();
		$q = array(
			'id',
			'xxt_call_text_yx',
			"siteid='$site'",
		);
		$q2['o'] = 'id desc';

		if ($vs = $this->model()->query_objs_ss($q, $q2)) {
			foreach ($vs as $v) {
				$call = $this->_byId($v->id, $cascade === 'Y' ? array('matter') : array());
				$calls[] = $call;
			}
		}
		return new \ResponseData($calls);
	}
	/**
	 * 获得文本命令的子资源
	 */
	public function cascade_action($id) {
		/**
		 * 文本命令的基本信息
		 */
		$q = array(
			'siteid,keyword,match_mode,matter_type,matter_id',
			'xxt_call_text_yx',
			"id=$id",
		);
		$call = $this->model()->query_obj_ss($q);
		/**
		 * 回复素材
		 */
		if ($call->matter_id) {
			$call->matter = $this->model('matter\base')->getMatterInfoById($call->matter_type, $call->matter_id);
		}

		return new \ResponseData($call);
	}
	/**
	 * get one text call.
	 *
	 * $id int text call id.
	 * $contain array
	 */
	private function &_byId($id, $contain = array('matter')) {
		$q = array(
			'id,siteid,keyword,match_mode,matter_type,matter_id',
			'xxt_call_text_yx',
			"id=$id",
		);
		$call = $this->model()->query_obj_ss($q);
		/**
		 * 素材
		 */
		if (!empty($contain) && in_array('matter', $contain)) {
			if ($call->matter_id) {
				$call->matter = $this->model('matter\base')->getMatterInfoById($call->matter_type, $call->matter_id);
			}
		}

		return $call;
	}
	/**
	 * 添加文本命令
	 */
	public function create_action($site) {
		$matter = $this->getPostJson();

		$d['matter_type'] = $matter->type;
		$d['matter_id'] = $matter->id;
		$d['siteid'] = $site;
		$keyword = isset($_POST['keyword']) ? $_POST['keyword'] : '新文本消息';
		$matchMode = isset($_POST['matchMode']) ? $_POST['matchMode'] : 'full';
		$d['keyword'] = $keyword;
		$d['match_mode'] = $matchMode;

		$id = $this->model()->insert('xxt_call_text_yx', $d, true);

		$call = $this->_byId($id);

		return new \ResponseData($call);
	}
	/**
	 * 删除文本命令
	 */
	public function delete_action($site, $id) {
		$rsp = $this->model()->delete('xxt_call_text_yx', "id=$id");

		return new \ResponseData($rsp);
	}
	/**
	 * 更新文本项的基本信息
	 *
	 * $mpid
	 * $id
	 * $nv array 0:name,1:value
	 */
	public function update_action($site, $id) {
		$nv = $this->getPostJson();
		$rst = $this->model()->update(
			'xxt_call_text_yx',
			$nv,
			"siteid='$site' and id=$id"
		);
		return new \ResponseData($rst);
	}
	/**
	 * 指定文本项的回复素材
	 */
	public function setreply_action($site, $id) {
		$reply = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_call_text_yx',
			array(
				'matter_type' => $reply->rt,
				'matter_id' => $reply->rid,
			),
			"siteid='$site' and id=$id"
		);

		return new \ResponseData($rst);
	}
}