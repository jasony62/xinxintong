<?php
class tag_model extends TMS_MODEL {
	/**
	 *
	 */
	public function byId($id, $fields = '*') {
		$q = array(
			$fields,
			'xxt_member_tag',
			"id=$id",
		);

		$tag = $this->query_obj_ss($q);

		return $tag;
	}
	/**
	 *
	 * $mpid
	 * $authid
	 */
	public function byMpid($mpid, $authid, $fields = '*') {
		$q = array(
			'*',
			'xxt_member_tag',
			"authapi_id=$authid",
		);

		$tags = $this->query_objs_ss($q);

		return $tags;
	}
	/**
	 *
	 */
	public function byName($mpid, $name, $fields = '*') {
		$q = array(
			$fields,
			'xxt_member_tag',
			"mpid='$mpid' and name='$name'",
		);

		$tag = $this->query_obj_ss($q);

		return $tag;
	}
	/**
	 * 获得指定认证接口下的标签
	 */
	public function byAuthid($authid, $fields = '*') {
		$q = array(
			$fields,
			'xxt_member_tag',
			"authapi_id=$authid",
		);

		$tags = $this->query_objs_ss($q);

		return $tags;
	}
	/**
	 * 添加标签
	 */
	public function create($mpid, $tag) {
		$t = array(
			'mpid' => $mpid,
			'authapi_id' => $tag->authapi_id,
			'name' => $tag->name,
			'extattr' => isset($tag->extattr) ? $tag->extattr : '',
		);
		$id = $this->insert('xxt_member_tag', $t, true);

		$t['id'] = $id;

		return $t;
	}
	/**
	 * 删除标签
	 *
	 * 如果存在标签成员不允许删除
	 */
	public function remove($mpid, $id) {
		/**
		 * 是否存在成员？
		 */
		$q = array(
			'count(*)',
			'xxt_member',
			"mpid='$mpid' and concat(',',tags,',') like '%\",$id,\"%'",
		);
		if (0 < (int) $this->query_val_ss($q)) {
			return array(false, '存在用户，不允许删除');
		}

		/**
		 * 删除标签
		 */
		$this->delete(
			'xxt_member_tag',
			"mpid='$mpid' and id=$id"
		);

		return array(true);
	}
	/**
	 * 获得一个标签下的关注用户
	 * 为了避免返回太多数据，限制返回的数量
	 *
	 * $tagids
	 */
	public function getFans($tagids, $fields = '*', $page = 1, $size = 50) {
		is_string($tagids) && $tagids = explode(',', $tagids);

		$w = "exists(";
		$w .= "select 1 from xxt_member m where f.fid=m.fid";
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