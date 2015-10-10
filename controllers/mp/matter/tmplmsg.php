<?php
namespace mp\matter;

require_once dirname(__FILE__) . '/matter_ctrl.php';

class tmplmsg extends matter_ctrl {
	/**
	 *
	 */
	public function index_action($id = null) {
		$uid = \TMS_CLIENT::get_client_uid();

		if (!empty($id)) {
			$q = array(
				"t.*,a.nickname creater_name,'$uid' uid",
				'xxt_tmplmsg t,account a',
				"t.mpid='$this->mpid' and t.id='$id' and t.state=1 and t.creater=a.uid",
			);
			$tmplmsg = $this->model()->query_obj_ss($q);
			$q = array(
				"*",
				'xxt_tmplmsg_param',
				"tmplmsg_id=$id",
			);
			$tmplmsg->params = $this->model()->query_objs_ss($q);

			if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
				return new \ResponseData($tmplmsg);
			} else {
				$param = array();
				$param['tmplmsg'] = $tmplmsg;
				\TPL::assign('param', $param);
				$this->view_action('/mp/matter/tmplmsg/main');
			}
		} else {
			$q = array(
				"t.*,a.nickname creater_name,'$uid' uid",
				'xxt_tmplmsg t,account a',
				"t.mpid='$this->mpid' and t.state=1 and t.creater=a.uid",
			);
			$q2['o'] = 't.create_at desc';
			$tmplmsgs = $this->model()->query_objs_ss($q, $q2);

			return new \ResponseData($tmplmsgs);
		}
	}
	/**
	 *
	 */
	public function list_action() {
		$model = $this->model();
		/**/
		$q = array(
			"t.*",
			'xxt_tmplmsg t',
			"t.mpid='$this->mpid'",
		);
		$tmplmsgs = $model->query_objs_ss($q);
		/**/
		$q = array(
			"id,pname,plabel",
			'xxt_tmplmsg_param',
		);
		foreach ($tmplmsgs as &$tmplmsg) {
			$q[2] = "tmplmsg_id=$tmplmsg->id";
			$tmplmsg->params = $model->query_objs_ss($q);
		}

		return new \ResponseData($tmplmsgs);
	}
	/**
	 *
	 */
	public function mappingGet_action($id) {
		$mapping = $this->model('matter\tmplmsg')->mappingById($id);

		return new \ResponseData($mapping);
	}
	/**
	 * 创建模板消息
	 */
	public function create_action($title = '新模板消息') {
		$uid = \TMS_CLIENT::get_client_uid();
		$d['mpid'] = $this->mpid;
		$d['creater'] = $uid;
		$d['create_at'] = time();
		$d['title'] = $title;

		$id = $this->model()->insert('xxt_tmplmsg', $d, true);

		$q = array(
			"t.*,a.nickname creater_name,'$uid' uid",
			'xxt_tmplmsg t,account a',
			"t.id=$id and t.creater=a.uid",
		);

		$tmplmsg = $this->model()->query_obj_ss($q);

		return new \ResponseData($tmplmsg);
	}
	/**
	 * 删除模板消息
	 *
	 * $id
	 */
	public function remove_action($id) {
		$rst = $this->model()->update(
			'xxt_tmplmsg',
			array('state' => 0),
			"mpid='$this->mpid' and id=$id"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 更新模板消息属性
	 */
	public function update_action($id) {
		$nv = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_tmplmsg',
			$nv,
			"mpid='$this->mpid' and id=$id"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * $tid tmplmsg's id
	 */
	public function addParam_action($tid) {
		$p['tmplmsg_id'] = $tid;

		$id = $this->model()->insert('xxt_tmplmsg_param', $p, true);

		return new \ResponseData($id);
	}
	/**
	 *
	 * 更新参数定义
	 *
	 * $id parameter's id
	 */
	public function updateParam_action($id) {
		$nv = $this->getPostJson();

		$rst = $this->model()->update(
			'xxt_tmplmsg_param',
			(array) $nv,
			"id=$id"
		);

		return new \ResponseData($rst);
	}
	/**
	 *
	 * $pid parameter's id
	 */
	public function removeParam_action($pid) {
		$rst = $this->model()->delete('xxt_tmplmsg_param', "id=$pid");

		return new \ResponseData($rst);
	}
}
