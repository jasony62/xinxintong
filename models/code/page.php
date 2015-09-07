<?php
/**
 *
 */
class page_model extends TMS_MODEL {
	/**
	 *
	 */
	public function &byId($id, $fields = '*') {
		$q = array(
			$fields,
			'xxt_code_page',
			"id=$id",
		);
		if ($p = $this->query_obj_ss($q)) {
			$p->ext_js = array();
			$p->ext_css = array();

			$q = array(
				'*',
				'xxt_code_external',
				"code_id=$id",
			);
			$exts = $this->query_objs_ss($q);
			foreach ($exts as $ext) {
				if ($ext->type === 'J') {
					$p->ext_js[] = $ext;
				} else if ($ext->type === 'C') {
					$p->ext_css[] = $ext;
				}

			}
		}

		return $p;
	}
	/**
	 *
	 */
	public function &byUser($uid) {
		$q = array(
			'*',
			'xxt_code_page',
			"creater='$uid'",
		);
		$p = $this->query_objs_ss($q);

		return $p;
	}
	/**
	 * 创建一个空页面
	 */
	public function create($creater) {
		$current = time();
		$page = array(
			'creater' => $creater,
			'create_at' => $current,
			'modify_at' => $current,
			'title' => '新页面',
			'html' => '',
			'css' => '',
			'js' => '',
		);

		$page['id'] = $this->insert('xxt_code_page', $page, true);

		return (object) $page;
	}
	/**
	 *
	 */
	public function remove($id) {
		$rst = $this->delete('xxt_code_page', "id=$id");

		return $rst;
	}
	/**
	 *
	 */
	public function modify($id, $data) {
		$data = (array) $data;
		foreach ($data as $n => $v) {
			if (in_array($n, array('css', 'html', 'js'))) {
				$data[$n] = $this->escape($v);
			}

		}

		$rst = $this->update('xxt_code_page', $data, "id=$id");

		return $rst;
	}
}
