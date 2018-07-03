<?php
namespace matter\enroll;

require_once dirname(__FILE__) . '/page_base.php';

use Sunra\PhpSimple\HtmlDomParser;

/**
 * 记录活动页面
 */
class page_model extends page_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_enroll_page';
	}
	/**
	 * 处理从数据库中获得的页面数据
	 */
	private function _processDb2Obj(&$oPage, $cascaded = 'Y', $published = 'N') {
		if (property_exists($oPage, 'data_schemas')) {
			if (!empty($oPage->data_schemas)) {
				$oPage->dataSchemas = json_decode($oPage->data_schemas);
			} else {
				$oPage->dataSchemas = [];
			}
			unset($oPage->data_schemas);
		}
		if (property_exists($oPage, 'act_schemas')) {
			if (!empty($oPage->act_schemas)) {
				$oPage->actSchemas = json_decode($oPage->act_schemas);
			} else {
				$oPage->actSchemas = [];
			}
			unset($oPage->act_schemas);
		}
		if ($cascaded === 'Y') {
			$modelCode = $this->model('code\page');
			if ($published === 'Y') {
				$oCode = $modelCode->lastPublishedByName($oPage->siteid, $oPage->code_name);
			} else {
				$oCode = $modelCode->lastByName($oPage->siteid, $oPage->code_name);
			}
			$oPage->html = $oCode->html;
			$oPage->css = $oCode->css;
			$oPage->js = $oCode->js;
			$oPage->ext_js = $oCode->ext_js;
			$oPage->ext_css = $oCode->ext_css;
		}

		return $oPage;
	}
	/**
	 * 根据页面的ID获得页面
	 */
	public function &byId($oApp, $apid, $aOptions = []) {
		$published = isset($aOptions['published']) ? ($aOptions['published'] === 'Y' ? 'Y' : 'N') : 'N';

		$q = [
			'*',
			'xxt_enroll_page',
			['aid' => $oApp->id, 'id' => $apid],
		];
		if ($oPage = $this->query_obj_ss($q)) {
			$this->_processDb2Obj($oPage, 'Y', $published);
		}

		return $oPage;
	}
	/**
	 * 根据页面的名称获得页面
	 */
	public function byName($oApp, $name, $aOptions = []) {
		$published = isset($aOptions['published']) ? ($aOptions['published'] === 'Y' ? 'Y' : 'N') : 'N';

		if (in_array($name, ['repos', 'rank', 'votes', 'event', 'score', 'topic', 'share', 'favor'])) {
			$oPage = new \stdClass;
			$oPage->name = $name;
			$oPage->type = '';
		} else {
			$q = [
				'*',
				'xxt_enroll_page',
				['aid' => $oApp->id, 'name' => $name],
			];
			if ($oPage = $this->query_obj_ss($q)) {
				$this->_processDb2Obj($oPage, 'Y', $published);
			}
		}

		return $oPage;
	}
	/**
	 * 返回指定登记活动的页面
	 */
	public function &byApp($appId, $options = array(), $published = 'N') {
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_enroll_page',
			['aid' => $appId],
		];
		$q2 = ['o' => 'seq,create_at'];
		$eps = $this->query_objs_ss($q, $q2);
		if (count($eps)) {
			foreach ($eps as $oPage) {
				$this->_processDb2Obj($oPage, $cascaded, $published);
			}
		}

		return $eps;
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
	public function &schemaByApp($appId) {
		$schema = array();

		$pages = $this->byApp($appId);
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
	public function add(&$user, $siteId, $appId, $data = null) {
		is_object($data) && $data = (array) $data;

		$code = $this->model('code\page')->create($siteId, $user->id);

		if (empty($data['seq'])) {
			$q = array(
				'max(seq)',
				'xxt_enroll_page',
				['aid' => $appId],
			);
			$seq = $this->query_val_ss($q);
			$seq = empty($seq) ? 1 : $seq + 1;
		} else {
			$seq = $data['seq'];
		}
		$newPage = new \stdClass;
		$newPage->siteid = $siteId;
		$newPage->aid = $appId;
		$newPage->creater = $user->id;
		$newPage->create_at = time();
		$newPage->type = isset($data['type']) ? $data['type'] : 'V';
		$newPage->title = isset($data['title']) ? $data['title'] : '新页面';
		$newPage->name = isset($data['name']) ? $data['name'] : 'z' . time();
		$newPage->code_id = $code->id;
		$newPage->code_name = $code->name;
		$newPage->share_page = 'Y';
		$newPage->seq = $seq;

		$newPage->id = $this->insert('xxt_enroll_page', $newPage, true);
		$newPage->html = '';
		$newPage->css = '';
		$newPage->js = '';

		return $newPage;
	}
	/**
	 *
	 */
	public function &schemaByText(&$simpleSchema) {
		$schemaWraps = array();
		$id = 0;
		$simpleSchema = preg_replace('/\r/', '', $simpleSchema);
		$lines = preg_split('/\n/', $simpleSchema);
		foreach ($lines as $i => $line) {
			if (count($schemaWraps) === 0 || empty($line)) {
				$schemaWrap = new \stdClass;
				$schemaWrap->schema = new \stdClass;
				$schemaWraps[] = $schemaWrap;
			}
			if (empty($line)) {
				continue;
			}
			$def = &$schemaWraps[count($schemaWraps) - 1]->schema;

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

		return $schemaWraps;
	}
	/**
	 *
	 */
	public function &htmlBySimpleSchema(&$simpleSchema, $template) {
		$schema = $this->schemaByText($simpleSchema);
		return $this->htmlBySchema($schema, $template);
	}
	/**
	 * 设置动态题目
	 */
	public function setDynaSchemas($oApp, &$oPage) {
		if (in_array($oPage->name, ['event', 'repos', 'cowork', 'share', 'rank', 'score', 'votes', 'favor', 'topic'])) {
			return $oPage;
		}
		
		$dataSchemas = $oApp->dataSchemas;
		$dom = HtmlDomParser::str_get_html($oPage->html);
		$aProtoHtmls = []; // 作为原型的题目
		$aProtoWraps = [];
		$pageWrapsById = []; // 页面上的题目定义
		foreach ($oPage->dataSchemas as $oWrap) {
			if (isset($oWrap->schema->id)) {
				$pageWrapsById[$oWrap->schema->id] = $oWrap;
			}
		}

		foreach ($dataSchemas as $oSchema) {
			if (empty($oSchema) || empty($oSchema->dynamic) || $oSchema->dynamic !== 'Y' || empty($oSchema->prototype->schema->id) || empty($pageWrapsById[$oSchema->prototype->schema->id])) {
				continue;
			}
			$oProtoSchema = $oSchema->prototype->schema;
			$protoElem = $dom->find('[schema="' . $oProtoSchema->id . '"]');
			if (1 === count($protoElem)) {
				/* html */
				$protoElem = $protoElem[0];
				$sProtoHtml = strval($protoElem);
				$aProtoHtmls[$oProtoSchema->id] = $sProtoHtml;
				$oNewElem = str_replace([$oProtoSchema->id, $oProtoSchema->title], [$oSchema->id, $oSchema->title], $sProtoHtml);
				$elemParent = $protoElem->parent();
				$elemParent->innertext = str_replace($sProtoHtml, $oNewElem . $sProtoHtml, $elemParent->innertext);
				/* wrap */
				$oProtoWrap = $pageWrapsById[$oProtoSchema->id];
				$aProtoWraps[$oProtoSchema->id] = $oProtoWrap;
				$oNewWrap = clone $oProtoWrap;
				$oNewWrap->schema = $oSchema;
				$oPage->dataSchemas[] = $oNewWrap;
			}
		}

		/* 清除作为原型的题目 */
		if (count($aProtoHtmls)) {
			/* 清除html */
			foreach ($aProtoHtmls as $html) {
				$dom->innertext = str_replace($html, '', $dom->innertext);
			}
			$oPage->html = $dom->innertext;
			/* 清除wrap */
			foreach ($aProtoWraps as $oProtoWrap) {
				$index = array_search($oProtoWrap, $oPage->dataSchemas);
				if (false !== $index) {
					array_splice($oPage->dataSchemas, $index, 1);
				}
			}
		}

		return $oPage;
	}
}