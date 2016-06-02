<?php
namespace code;
/**
 *
 */
class page_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byId($id, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
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
	 * 最新版本的代码
	 *
	 * @param string $siteId
	 * @param string $name
	 * @param string $options
	 */
	public function &lastByName($siteId, $name, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array(
			$fields,
			'xxt_code_page',
			"siteid='$siteId' and name='$name' and is_last='Y'",
		);
		if ($p = $this->query_obj_ss($q)) {
			$p->ext_js = array();
			$p->ext_css = array();
			$q = array(
				'*',
				'xxt_code_external',
				"code_id={$p->id}",
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
	 * 最新发布版本的代码
	 *
	 * @param string $siteId
	 * @param string $name
	 * @param string $options
	 */
	public function &lastPublishedByName($siteId, $name, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array(
			$fields,
			'xxt_code_page',
			"siteid='$siteId' and name='$name' and is_last_published='Y'",
		);
		$p = $this->query_obj_ss($q);
		if ($p && isset($p->id)) {
			$p->ext_js = array();
			$p->ext_css = array();

			$q = array(
				'*',
				'xxt_code_external',
				"code_id={$p->id}",
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
	 *
	 * @param string $siteId
	 * @param string $name
	 * @param string $options
	 */
	public function &byName($siteId, $name, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';

		$q = array(
			$fields,
			'xxt_code_page',
			"siteid='$siteId' and name='$name'",
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
	 *
	 * @param string $siteId
	 * @param string $creater
	 * @param array data
	 */
	public function create($siteId, $creater, $data = array()) {
		$current = time();
		$page = array(
			'siteid' => $siteId,
			'name' => uniqid(),
			'creater' => $creater,
			'create_at' => $current,
			'modifier' => $creater,
			'modify_at' => $current,
			'version' => 1,
			'published' => 'Y',
			'is_last' => 'Y',
			'is_last_published' => 'Y',
			'title' => '新页面',
			'html' => isset($data['html']) ? $this->escape($data['html']) : '',
			'css' => isset($data['css']) ? $this->escape($data['css']) : '',
			'js' => isset($data['js']) ? $this->escape($data['js']) : '',
		);

		$page['id'] = $this->insert('xxt_code_page', $page, true);

		return (object) $page;
	}
	/**
	 *
	 */
	public function copy($creater, $src, $target = 0) {
		$page = $this->byId($src);
		if ($target === 0) {
			$newone = $this->create($creater);
			$target = $newone->id;
			$this->delete('xxt_code_external', "code_id=$target");
		}
		$data = array(
			'html' => $this->escape($page->html),
			'css' => $this->escape($page->css),
			'js' => $this->escape($page->js),
		);
		$this->update('xxt_code_page', $data, "id=$target");

		if (!empty($page->ext_js)) {
			foreach ($page->ext_js as $js) {
				$this->insert('xxt_code_external', array('code_id' => $target, 'type' => 'J', 'url' => $js->url), false);
			}
		}
		if (!empty($page->ext_css)) {
			foreach ($page->ext_css as $css) {
				$this->insert('xxt_code_external', array('code_id' => $target, 'type' => 'C', 'url' => $css->url), false);
			}
		}

		return $target;
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
	/**
	 * 删除代码
	 *
	 * @param string $siteId
	 * @param string $name
	 */
	public function removeByName($siteId, $name) {
		$rst = $this->delete(
			'xxt_code_external',
			"code_id in (select id from xxt_code_page where siteid='$siteId' and name='$name')"
		);

		$rst = $this->delete(
			'xxt_code_page',
			"siteid='$siteId' and name='$name'"
		);

		return $rst;
	}
	/**
	 *
	 */
	public function remove($id) {
		$rst = $this->delete(
			'xxt_code_external',
			"code_id='$id'"
		);

		$rst = $this->delete(
			'xxt_code_page',
			"id=$id"
		);

		return $rst;
	}
}