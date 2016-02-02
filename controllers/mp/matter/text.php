<?php
namespace mp\matter;

require_once dirname(dirname(__FILE__)) . '/mp_controller.php';

class text extends \mp\mp_controller {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';
		return $rule_action;
	}
	/**
	 * get all static texts.
	 */
	public function index_action() {
		$this->view_action('/mp/matter/texts');
	}
	/**
	 *
	 */
	public function list_action() {
		$options = $this->getPostJson();
		/**
		 * 当前用户
		 */
		$uid = \TMS_CLIENT::get_client_uid();
		/**
		 * 素材的来源
		 */
		$mpid = (!empty($options->src) && $options->src === 'p') ? $this->getParentMpid() : $this->mpid;

		$q = array(
			"t.*,a.nickname creater_name,'$uid' uid",
			'xxt_text t,account a',
			"mpid='$mpid' and state=1 and t.creater=a.uid",
		);
		/**
		 * 限制作者？
		 */
		if (!$this->model('mp\permission')->isAdmin($mpid, $uid, true)) {
			$limit = $this->model()->query_value('matter_visible_to_creater', 'xxt_mpsetting', "mpid='$mpid'");
			if ($limit === 'Y') {
				$q[2] .= " and (creater='$uid' or public_visible='Y')";
			}

		}

		$q2['o'] = 'create_at desc';
		$texts = $this->model()->query_objs_ss($q, $q2);
		return new \ResponseData($texts);
	}
	/**
	 * 创建文本素材
	 */
	public function create_action() {
		$uid = \TMS_CLIENT::get_client_uid();
		$text = $this->getPostJson();

		$d = array();
		$d['mpid'] = $this->mpid;
		$d['creater'] = $uid;
		$d['create_at'] = time();
		$d['content'] = $this->model()->escape($text->content);

		$id = $this->model()->insert('xxt_text', $d, true);

		$q = array(
			"t.*,a.nickname creater_name,'$uid' uid",
			'xxt_text t,account a',
			"id='$id' and t.creater=a.uid",
		);
		$text = $this->model()->query_obj_ss($q);

		return new \ResponseData($text);
	}
	/**
	 *
	 */
	public function delete_action($id) {
		$rst = $this->model()->update('xxt_text', array('state' => 0), "mpid='$this->mpid' and id=$id");

		return new \ResponseError($rst);
	}
	/**
	 * 更新文本素材的属性
	 */
	public function update_action($id) {
		$nv = $this->getPostJson();

		if (isset($nv->content)) {
			$nv->content = $this->model()->escape($nv->content);
		}

		$rst = $this->model()->update('xxt_text',
			(array) $nv,
			"mpid='$this->mpid' and id=$id"
		);

		return new \ResponseData($rst);
	}
}
