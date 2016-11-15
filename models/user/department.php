<?php
/**
 * 认证用户的部门
 */
class department_model extends TMS_MODEL {
	/**
	 *
	 */
	public function &byId($id, $fields = '*') {
		$q = array(
			$fields,
			'xxt_member_department',
			"id=$id",
		);

		$dept = $this->query_obj_ss($q);

		return $dept;
	}
	/**
	 *
	 */
	public function byMpid($mpid, $authid, $pid = 0, $fields = '*') {
		$q = array(
			'*',
			'xxt_member_department',
			"mpid='$mpid' and authapi_id=$authid and pid=$pid",
		);
		$q2 = array('o' => 'seq');

		$depts = $this->query_objs_ss($q, $q2);

		return $depts;
	}
	/**
	 * 将用户所属部门的id，转换为名称的字符串
	 *
	 * $depts string
	 */
	public function strUserDepts($depts) {
		if (empty($depts)) {
			return '';
		}

		$str = array();
		$aDepts = json_decode($depts);
		foreach ($aDepts as $aDeptIds) {
			$n = array();
			foreach ($aDeptIds as $id) {
				$d = $this->byId($id, 'name');
				$n[] = $d->name;
			}
			$str[] = implode(',', $n);
		}

		return implode(';', $str);
	}
	/**
	 * 添加部门
	 *
	 * $mpid
	 * $authid 部门必须属于某个认证接口
	 * $pid 父节点的id
	 * $seq 父节点中的序号
	 */
	public function create($mpid, $authid, $pid, $seq = null) {
		$isAppend = ($seq === null);
		if ($isAppend) {
			/**
			 * 加到父节点的尾
			 */
			$q = array(
				'count(*)',
				'xxt_member_department',
				"mpid='$mpid' and pid=$pid",
			);
			$lastSeq = (int) $this->query_val_ss($q);
			$seq = $lastSeq + 1;
		}
		$i = array(
			'mpid' => $mpid,
			'authapi_id' => $authid,
			'pid' => $pid,
			'seq' => $seq,
			'name' => '新部门',
		);
		$id = $this->insert('xxt_member_department', $i, true);
		/**
		 * 更新fullpath
		 * fullpath包含节点自身的id
		 */
		if ($pid == 0) {
			$fullpath = "$id";
		} else {
			/**
			 * 父节点的fullpath
			 */
			$q = array(
				'fullpath',
				'xxt_member_department',
				"mpid='$mpid' and id=$pid",
			);
			$fullpath = $this->query_val_ss($q);
			$fullpath .= ",$id";
		}
		$this->update(
			'xxt_member_department',
			array('fullpath' => $fullpath),
			"mpid='$mpid' and id=$id"
		);

		$dept = $this->query_obj_ss(array('*', 'xxt_member_department', "id=$id"));

		return $dept;
	}
	/**
	 * 删除部门
	 *
	 * 如果存在子部门不允许删除
	 * 如果存在部门成员不允许删除
	 */
	public function remove($mpid, $id) {
		$q = array(
			'pid,seq',
			'xxt_member_department',
			"mpid='$mpid' and id=$id",
		);
		if (false === ($dept = $this->query_obj_ss($q))) {
			return array(false, '部门不存在');
		}

		/**
		 * 是否存在子部门？
		 */
		$q = array(
			'count(*)',
			'xxt_member_department',
			"mpid='$mpid' and pid=$id",
		);
		if (0 < (int) $this->query_val_ss($q)) {
			return array(false, '存在子部门，不允许删除');
		}

		/**
		 * 是否存在成员？
		 */
		$q = array(
			'count(*)',
			'xxt_member',
			"mpid='$mpid' and concat(',',depts,',') like '%\",$id,\"%'",
		);
		if (0 < (int) $this->query_val_ss($q)) {
			return array(false, '存在用户，不允许删除');
		}

		/**
		 * 删除部门
		 */
		$rst = (int) $this->delete(
			'xxt_member_department',
			"mpid='$mpid' and id=$id"
		);
		if ($rst === 1) {
			/**
			 * 更新兄弟部门的序号
			 */
			$sql = 'update xxt_member_department';
			$sql .= ' set seq=seq-1';
			$sql .= " where mpid='$mpid' and pid=$dept->pid and seq>$dept->seq";
			$this->update($sql);
		}

		return array(true);
	}
	/**
	 * 获得一个部门下的关注用户
	 * 为了避免返回太多数据，限制返回的数量
	 *
	 * $id
	 * $fields
	 */
	public function getFans($deptid, $fields = '*', $page = 1, $size = 50) {
		$q = array(
			$fields,
			'xxt_fans f',
			"exists(select 1 from xxt_member m where f.fid=m.fid and (m.depts like '%\"$deptid\"%'))",
		);
		$q2 = array('r' => array('o' => ($page - 1) * $size, 'l' => $size));

		$fans = $this->query_objs_ss($q, $q2);

		return $fans;
	}
	/**
	 * 获得一个部门下，并具有指定标签的关注用户
	 *
	 * $deptid
	 * $tagids
	 * $fields
	 */
	public function getFansByTag($deptid, $tagids, $fields = '*', $page = 1, $size = 50) {
		is_string($tagids) && $tagids = explode(',', $tagids);

		$w = "exists(";
		$w .= "select 1 from xxt_member m where f.fid=m.fid and (m.depts like '%\"$deptid\"%')";
		foreach ($tagids as $tagid) {
			$w .= " and concat(',',m.tags,',') like '%,$tagid,%'";
		}

		$w .= ")";

		$q = array(
			$fields,
			'xxt_fans f',
			$w,
		);
		$q2 = array('r' => array('o' => ($page - 1) * $size, 'l' => $size));

		$fans = $this->query_objs_ss($q, $q2);

		return $fans;
	}
}