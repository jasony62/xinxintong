<?php
namespace app\enroll;

class page_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function &byId($aid, $apid) {
		$select = 'ap.*,cp.html,cp.css,cp.js';
		$from = 'xxt_enroll_page ap,xxt_code_page cp';
		$where = "ap.aid='$aid' and ap.id=$apid and ap.code_id=cp.id";

		$q = array($select, $from, $where);

		$ep = $this->query_obj_ss($q);
		if ($ep) {
			$code = \TMS_APP::model('code\page')->byId($ep->code_id);
			$ep->html = $code->html;
			$ep->css = $code->css;
			$ep->js = $code->js;
			$ep->ext_js = $code->ext_js;
			$ep->ext_css = $code->ext_css;
		}

		return $ep;
	}
	/**
	 *
	 */
	public function byName($aid, $name) {
		$select = 'ep.*,cp.html,cp.css,cp.js';
		$from = 'xxt_enroll_page ep,xxt_code_page cp';
		$where = "ep.aid='$aid' and ep.name='$name' and ep.code_id=cp.id";

		$q = array($select, $from, $where);

		if ($ep = $this->query_obj_ss($q)) {
			$code = \TMS_APP::model('code\page')->byId($ep->code_id);
			$ep->html = $code->html;
			$ep->css = $code->css;
			$ep->js = $code->js;
			$ep->ext_js = $code->ext_js;
			$ep->ext_css = $code->ext_css;
			return $ep;
		} else {
			return false;
		}
	}
	/**
	 * 返回指定登记活动的页面
	 */
	public function &byApp($aid, $options = array()) {
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';
		$fields = isset($options['fields']) ? $options['fields'] : 'id,name,type,title,code_id,autoenroll_onenter,autoenroll_onshare,check_entry_rule,share_page,share_summary,seq,data_schemas,act_schemas';
		$q = array(
			$fields,
			'xxt_enroll_page',
			"aid='$aid'",
		);
		$q2 = array('o' => 'seq,create_at');
		$eps = $this->query_objs_ss($q, $q2);
		if ($cascaded === 'Y' && !empty($eps)) {
			$pages = array();
			foreach ($eps as &$ep) {
				$code = \TMS_APP::model('code\page')->byId($ep->code_id);
				$ep->html = $code->html;
				$ep->css = $code->css;
				$ep->js = $code->js;
				$ep->ext_js = $code->ext_js;
				$ep->ext_css = $code->ext_css;
				$pages[] = $ep;
			}
			return $pages;
		} else {
			return $eps;
		}
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

		if (preg_match_all('/<(div|li|option).+?wrap=.+?>.*?<\/(div|li|option)/i', $html, $wraps)) {
			$wraps = $wraps[0];
			foreach ($wraps as $wrap) {
				$def = array();
				$inp = array();
				$title = array();
				$ngmodel = array();
				$opval = array();
				$optit = array();
				if (!preg_match('/<input.+?>/', $wrap, $inp) && !preg_match('/<option.+?>/', $wrap, $inp) && !preg_match('/<textarea.+?>/', $wrap, $inp) && !preg_match('/wrap="datetime".+?>/', $wrap, $inp) && !preg_match('/wrap="img".+?>/', $wrap, $inp) && !preg_match('/wrap="file".+?>/', $wrap, $inp)) {
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
						$defs[] = array('title' => $title, 'id' => $id, 'type' => 'single', 'ops' => array());
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
						$defs[] = array('title' => $title, 'id' => $id, 'type' => 'single', 'ops' => array());
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
						$defs[] = array('title' => $title, 'id' => $id, 'type' => 'multiple', 'ops' => array());
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
				} else if (preg_match('/ng-bind="data\.(.+?)\|/', $inp, $ngmodel)) {
					$id = $ngmodel[1];
					$defs[] = array('title' => $title, 'id' => $id, 'type' => 'datetime');
				} else {
					/**
					 * for text input/textarea/location.
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
			 * 随机获得指定数量的登记项（为了解决随机获得答题的场景）
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
	public function &schemaByApp($aid) {
		$schema = array();

		$pages = $this->byApp($aid);
		if (!empty($pages)) {
			foreach ($pages as $p) {
				if ($p->type === 'I') {
					$defs = $this->schemaByHtml($p->html);
					$schema = array_merge($schema, $defs);
				}
			}
		}

		return $schema;
	}
	/**
	 * 创建活动页面
	 */
	public function add($mpid, $aid, $data = null) {
		is_object($data) && $data = (array) $data;

		$uid = \TMS_CLIENT::get_client_uid();

		$code = \TMS_APP::model('code\page')->create($uid);

		if (empty($data['seq'])) {
			$q = array(
				'max(seq)',
				'xxt_enroll_page',
				"aid='$aid'",
			);
			$seq = $this->query_val_ss($q);
			$seq = empty($seq) ? 1 : $seq + 1;
		} else {
			$seq = $data['seq'];
		}
		$newPage = array(
			'mpid' => $mpid,
			'aid' => $aid,
			'creater' => $uid,
			'create_at' => time(),
			'type' => isset($data['type']) ? $data['type'] : 'V',
			'title' => isset($data['title']) ? $data['title'] : '新页面',
			'name' => isset($data['name']) ? $data['name'] : 'z' . time(),
			'code_id' => $code->id,
			'seq' => $seq,
		);

		$apid = $this->insert('xxt_enroll_page', $newPage, true);

		$newPage['id'] = $apid;
		$newPage['html'] = '';
		$newPage['css'] = '';
		$newPage['js'] = '';

		return (object) $newPage;
	}
	/**
	 *
	 */
	public function &schemaByText(&$simpleSchema) {
		$schema = array();
		$id = 0;
		$simpleSchema = preg_replace('/\r/', '', $simpleSchema);
		$lines = preg_split('/\n/', $simpleSchema);
		foreach ($lines as $i => $line) {
			if (count($schema) === 0 || empty($line)) {
				$schema[] = new \stdClass;
			}
			if (empty($line)) {
				continue;
			}
			$def = &$schema[count($schema) - 1];

			if (!isset($def->id)) {
				$def->id = 'c' . (++$id);
				$def->title = $line;
				$def->type = 'multiple';
				$def->ops = array();
			} else {
				$op = new \stdClass;
				$op->v = 'v' . count($def->ops);
				$op->l = $line;
				$def->ops[] = $op;
			}
		}
		return $schema;
	}
	/**
	 *
	 */
	public function &htmlBySimpleSchema(&$simpleSchema, $template) {
		$schema = $this->schemaByText($simpleSchema);
		return $this->htmlBySchema($schema, $template);
	}
	/**
	 *
	 */
	public function &htmlBySchema(&$schema, $template) {
		if (defined('SAE_TMP_PATH')) {
			$tmpfname = tempnam(SAE_TMP_PATH, "template");
		} else {
			$tmpfname = tempnam(sys_get_temp_dir(), "template");
		}
		$handle = fopen($tmpfname, "w");
		fwrite($handle, $template);
		fclose($handle);
		$s = new \Savant3(array('template' => $tmpfname, 'exceptions' => true));
		$s->assign('schema', $schema);
		$html = $s->getOutput();
		unlink($tmpfname);

		return $html;
	}
}