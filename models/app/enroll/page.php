<?php
namespace app\enroll;

class page_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byId($aid, $apid) {
		$select = 'ap.*,cp.html,cp.css,cp.js';
		$from = 'xxt_enroll_page ap,xxt_code_page cp';
		$where = "ap.aid='$aid' and ap.id=$apid and ap.code_id=cp.id";

		$q = array($select, $from, $where);

		$ep = $this->query_obj_ss($q);

		$code = \TMS_APP::model('code/page')->byId($ep->code_id);
		$ep->html = $code->html;
		$ep->css = $code->css;
		$ep->js = $code->js;
		$ep->ext_js = $code->ext_js;
		$ep->ext_css = $code->ext_css;

		return $ep;
	}
	/**
	 * 根据活动
	 */
	public function &byEnroll($id, $fields = null) {
		$fields === null && $fields = 'id,name,type,title,code_id,autoenroll_onenter,autoenroll_onshare,check_entry_rule,share_page,share_summary';
		$q = array(
			$fields,
			'xxt_enroll_page',
			"aid='$id'",
		);
		$q2 = array('o' => 'create_at');
		$eps = $this->query_objs_ss($q, $q2);
		foreach ($eps as &$ep) {
			$code = \TMS_APP::model('code/page')->byId($ep->code_id);
			$ep->html = $code->html;
			$ep->css = $code->css;
			$ep->js = $code->js;
			$ep->ext_js = $code->ext_js;
			$ep->ext_css = $code->ext_css;
			$pages[] = $ep;
		}

		return $pages;
	}
	/**
	 * 从页面的html中提取登记项定义
	 *
	 * 数据项的定义需要从表单中获取
	 * 表单中定义了数据项的id和name
	 * 定义数据项都是input，所以首先应该将页面中所有input元素提取出来
	 * 每一个元素中都有ng-model和title属相，ng-model包含了id，title是名称
	 */
	public function &schemaByHtml($html, $size = null) {
		$defs = array();

		if (empty($html)) {
			return $defs;
		}

		if (preg_match_all('/<(div|li|option).+?wrap=.+?>.+?<\/(div|li|option)/i', $html, $wraps)) {
			$wraps = $wraps[0];
			foreach ($wraps as $wrap) {
				$def = array();
				$inp = array();
				$title = array();
				$ngmodel = array();
				$opval = array();
				$optit = array();
				if (!preg_match('/<input.+?>/', $wrap, $inp) && !preg_match('/<option.+?>/', $wrap, $inp) && !preg_match('/<textarea.+?>/', $wrap, $inp) && !preg_match('/wrap="img".+?>/', $wrap, $inp) && !preg_match('/wrap="file".+?>/', $wrap, $inp)) {
					continue;
				}

				$inp = $inp[0];
				if (preg_match('/title="(.*?)"/', $inp, $title)) {
					$title = $title[1];
				}

				if (preg_match('/type="radio"/', $inp)) {
					/**
					 * for radio group.
					 */
					if (preg_match('/ng-model="data\.(.+?)"/', $inp, $ngmodel)) {
						$id = $ngmodel[1];
					}

					if (empty($id)) {
						continue;
					}

					$existing = false;
					foreach ($defs as &$d) {
						if ($existing = ($d['id'] === $id)) {
							break;
						}
					}

					if (!$existing) {
						$defs[] = array('title' => $title, 'id' => $id, 'ops' => array());
						$d = &$defs[count($defs) - 1];
					}
					$op = array();
					if (preg_match('/value="(.+?)"/', $inp, $opval)) {
						$op['v'] = $opval[1];
					}

					if (preg_match_all('/data-(.+?)="(.+?)"/', $wrap, $opAttrs)) {
						for ($i = 0, $l = count($opAttrs[0]); $i < $l; $i++) {
							$op[$opAttrs[1][$i]] = $opAttrs[2][$i];
						}
					}
					$d['ops'][] = $op;
				} else if (preg_match('/<option/', $inp)) {
					/**
					 * for radio group.
					 */
					if (preg_match('/name="data\.(.+?)"/', $inp, $ngmodel)) {
						$id = $ngmodel[1];
					}

					if (empty($id)) {
						continue;
					}

					$existing = false;
					foreach ($defs as &$d) {
						if ($existing = ($d['id'] === $id)) {
							break;
						}
					}

					if (!$existing) {
						$defs[] = array('title' => $title, 'id' => $id, 'ops' => array());
						$d = &$defs[count($defs) - 1];
					}
					$op = array();
					if (preg_match('/value="(.+?)"/', $inp, $opval)) {
						$op['v'] = $opval[1];
					}

					if (preg_match_all('/data-(.+?)="(.+?)"/', $wrap, $opAttrs)) {
						for ($i = 0, $l = count($opAttrs[0]); $i < $l; $i++) {
							$op[$opAttrs[1][$i]] = $opAttrs[2][$i];
						}
					}
					$d['ops'][] = $op;
				} else if (preg_match('/type="checkbox"/', $inp)) {
					/**
					 * for checkbox group.
					 */
					if (preg_match('/ng-model="data\.(.+?)\.(.+?)"/', $inp, $ngmodel)) {
						$id = $ngmodel[1];
						$opval = $ngmodel[2];
					}

					if (empty($id) || !isset($opval)) {
						continue;
					}

					$existing = false;
					foreach ($defs as &$d) {
						if ($existing = ($d['id'] === $id)) {
							break;
						}
					}

					if (!$existing) {
						$defs[] = array('title' => $title, 'id' => $id, 'ops' => array());
						$d = &$defs[count($defs) - 1];
					}
					$op = array();
					$op['v'] = $opval;
					if (preg_match_all('/data-(.+?)="(.+?)"/', $wrap, $opAttrs)) {
						for ($i = 0, $l = count($opAttrs[0]); $i < $l; $i++) {
							$op[$opAttrs[1][$i]] = $opAttrs[2][$i];
						}
					}
					$d['ops'][] = $op;
				} else if (preg_match('/ng-repeat="img in data\.(.+?)"/', $inp, $ngrepeat)) {
					$id = $ngrepeat[1];
					$defs[] = array('title' => $title, 'id' => $id, 'type' => 'img');
				} else if (preg_match('/ng-repeat="file in data\.(.+?)"/', $inp, $ngrepeat)) {
					$id = $ngrepeat[1];
					$defs[] = array('title' => $title, 'id' => $id, 'type' => 'file');
				} else {
					/**
					 * for text input/textarea.
					 */
					if (preg_match('/ng-model="data\.(.+?)"/', $inp, $ngmodel)) {
						$id = $ngmodel[1];
					}

					if (empty($id)) {
						continue;
					}

					$defs[] = array('title' => $title, 'id' => $id);
				}
			}
		}

		if ($size !== null && $size > 0 && $size < count($defs)) {
			/**
			 * 随机获得指定数量的登记项
			 */
			$randomDefs = array();
			$upper = count($defs) - 1;
			for ($i = 0; $i < $size; $i++) {
				$random = mt_rand(0, $upper);
				$randomDefs[] = $defs[$random];
				array_splice($defs, $random, 1);
				$upper--;
			}
			return $randomDefs;
		} else {
			return $defs;
		}

	}
	/**
	 *
	 */
	public function &schemaByEnroll($id) {
		$schema = array();

		$pages = $this->byEnroll($id);
		foreach ($pages as $p) {
			if ($p->type === 'I') {
				$defs = $this->schemaByHtml($p->html);
				$schema = array_merge($schema, $defs);
			}
		}

		return $schema;
	}
	/**
	 * 创建活动页面
	 */
	public function add($mpid, $aid, $data = null) {
		$uid = \TMS_CLIENT::get_client_uid();

		$code = \TMS_APP::model('code/page')->create($uid);

		$newPage = array(
			'mpid' => $mpid,
			'aid' => $aid,
			'creater' => $uid,
			'create_at' => time(),
			'type' => isset($data['type']) ? $data['type'] : 'V',
			'title' => isset($data['title']) ? $data['title'] : '新页面',
			'name' => isset($data['name']) ? $data['name'] : 'z' . time(),
			'code_id' => $code->id,
		);

		$apid = $this->insert('xxt_enroll_page', $newPage, true);

		$newPage['id'] = $apid;
		$newPage['html'] = '';
		$newPage['css'] = '';
		$newPage['js'] = '';

		return (object) $newPage;
	}
}
