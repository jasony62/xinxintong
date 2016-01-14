<?php
namespace mp\user;

require_once dirname(dirname(__FILE__)) . '/mp_controller.php';

class department extends \mp\mp_controller {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		$this->view_action('/mp/user/departments');
	}
	/**
	 * 获得指定父节点下的部门
	 *
	 * @param int $authid
	 * @param int $pid
	 */
	public function list_action($authid, $pid = 0) {
		$depts = $this->model('user/department')->byMpid($this->mpid, $authid, $pid);

		return new \ResponseData($depts);
	}
	/**
	 * 添加部门
	 *
	 * $authid 部门必须属于一个认证接口
	 * $pid
	 * $seq 如果没有指定位置，就插入到最后。序号从1开始。
	 */
	public function add_action($authid, $pid = 0, $seq = null) {
		$mpapis = $this->model('mp\mpaccount')->getApis($this->mpid);

		$dept = $this->model('user/department')->create($this->mpid, $authid, $pid, $seq);

		if ($mpapis->mpsrc === 'qy' && $mpapis->qy_joined === 'Y') {
			/**
			 * 与企业号同步
			 */
			$pdept = $this->model('user/department')->byId($dept->pid, 'extattr');
			$pdept = json_decode($pdept->extattr);
			$result = $this->model('mpproxy/qy', $this->mpid)->departmentCreate($dept->name, $pdept->id, $dept->seq);
			if ($result[0] === false) {
				$this->model()->delete('xxt_member_department', "id=$dept->id");
				return new \ResponseError($result[1]);
			}

			$dept->extattr = json_encode(array('id' => (int) $result[1]->id, 'parentid' => (int) $pdept->id));
			$this->model()->update(
				'xxt_member_department',
				array('sync_at' => time(), 'extattr' => $dept->extattr),
				"id=$dept->id"
			);
		}

		return new \ResponseData($dept);
	}
	/**
	 * 更新部门
	 *
	 * todo 目前只支持改名称
	 *
	 * $id
	 */
	public function update_action($id) {
		$mpapis = $this->model('mp\mpaccount')->getApis($this->mpid);

		$nv = $this->getPostJson();

		if ($mpapis->mpsrc === 'qy' && $mpapis->qy_joined === 'Y' && isset($nv->name)) {
			/**
			 * 与企业号同步
			 */
			$dept = $this->model('user/department')->byId($id, 'extattr');
			$rdept = json_decode($dept->extattr);
			if (!empty($rdept->id)) {
				$result = $this->model('mpproxy/qy', $this->mpid)->departmentUpdate($rdept->id, $nv->name);
				if ($result[0] === false) {
					return new \ResponseError($result[1]);
				}

			}
		}

		$rst = $this->model()->update(
			'xxt_member_department',
			(array) $nv,
			"mpid='$this->mpid' and id=$id"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 删除部门
	 *
	 * 如果存在子部门不允许删除
	 * 如果存在部门成员不允许删除
	 */
	public function remove_action($id) {
		$mpapis = $this->model('mp\mpaccount')->getApis($this->mpid);
		if ($mpapis->mpsrc === 'qy' && $mpapis->qy_joined === 'Y') {
			$dept = $this->model('user/department')->byId($id, 'extattr');
		}

		$rst = $this->model('user/department')->remove($this->mpid, $id);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		if ($mpapis->mpsrc === 'qy' && $mpapis->qy_joined === 'Y') {
			/**
			 * 与企业号同步
			 */
			$rdept = json_decode($dept->extattr);
			if (!empty($rdept->id)) {
				$result = $this->model('mpproxy/qy', $this->mpid)->departmentDelete($rdept->id);
				if ($result[0] === false) {
					return new \ResponseError($result[1]);
				}

			}
		}

		return new \ResponseData(true);
	}
}