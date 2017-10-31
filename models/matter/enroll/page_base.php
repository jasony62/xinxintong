<?php
namespace matter\enroll;

require_once TMS_APP_DIR . '/lib/PhpSimple/HtmlDomParser.php';

use Sunra\PhpSimple\HtmlDomParser;

abstract class page_base extends \TMS_MODEL {
	/**
	 *
	 */
	public function modify($oNewPage, $aProps) {
		$aUpdated = [];
		if (in_array('html', $aProps)) {
			/* 更新页面内容 */
			$data = [
				'html' => $oNewPage->html,
			];
			$modelCode = $this->model('code\page');
			$oCode = $modelCode->lastByName($oNewPage->siteid, $oNewPage->code_name);
			$rst = $modelCode->modify($oCode->id, $data);
			array_splice($aProps, array_search('html', $aProps), 1);
		}
		foreach ($aProps as $prop) {
			$aUpdated[$prop] = $oNewPage->{$prop};
		}
		$rst = $this->update($this->table(), $aUpdated, ['id' => $oNewPage->id]);

		return $rst;
	}
	/**
	 * 将通讯录题目替换为普通题目
	 */
	public function replaceMemberSchema(&$oPage, $oMschema) {
		$aPageDataSchemas = json_decode($oPage->data_schemas);
		foreach ($aPageDataSchemas as $oPageWrap) {
			if (!empty($oPageWrap->schema)) {
				$oSchema = $oPageWrap->schema;
				if ($oSchema->type === 'member' && $oSchema->schema_id === $oMschema->id) {
					$oBeforeSchema = clone $oSchema;
					/* 更新题目 */
					$oSchema->type = 'shorttext';
					$oSchema->id = str_replace('member.', '', $oSchema->id);
					if (in_array($oSchema->id, ['name', 'mobile', 'email'])) {
						$oSchema->format = $oSchema->id;
					} else {
						$oSchema->format = '';
					}
					unset($oSchema->schema_id);
					/* 更新页面 */
					$this->updHtmlBySchema($oPage, $oSchema, $oBeforeSchema);
				}
			} else {
				$oSchemas = $oPageWrap->schemas;
				foreach ($oSchemas as $schema) {
					if ($schema->type === 'member' && $schema->schema_id === $oMschema->id) {
						$oBeforeSchema = clone $schema;
						/* 更新题目 */
						$schema->type = 'shorttext';
						$schema->id = str_replace('member.', '', $schema->id);
						if (in_array($schema->id, ['name', 'mobile', 'email'])) {
							$schema->format = $schema->id;
						} else {
							$schema->format = '';
						}
						unset($schema->schema_id);
						/* 更新页面 */
						$this->updHtmlBySchema($oPage, $schema, $oBeforeSchema);
					}
				}
			}
		}
		$oPage->data_schemas = $this->toJson($aPageDataSchemas);

		return [true];
	}
	/**
	 * 将通讯录题目替换为普通题目
	 */
	public function replaceAssocSchema(&$oPage, $aAssocAppIds) {
		$aPageDataSchemas = json_decode($oPage->data_schemas);
		foreach ($aPageDataSchemas as $oPageWrap) {
			$oSchema = $oPageWrap->schema;
			if (isset($oSchema->fromApp) && in_array($oSchema->fromApp, $aAssocAppIds)) {
				unset($oSchema->fromApp);
				unset($oSchema->requieCheck);
			}
		}
		$oPage->data_schemas = $this->toJson($aPageDataSchemas);

		return [true];
	}
	/**
	 * 根据题目的变化修改页面
	 */
	public function updHtmlBySchema(&$oPage, $oNewSchema, $oBeforeSchema = null) {
		$beforeId = isset($oBeforeSchema) ? $oBeforeSchema->id : $oNewSchema->id;
		$beforeType = isset($oBeforeSchema) ? $oBeforeSchema->type : $oNewSchema->type;
		$dom = HtmlDomParser::str_get_html($oPage->html);

		/* 获得题目的代码片段 */
		switch ($oPage->type) {
		case 'I':
			foreach ($dom->find('[schema="' . $beforeId . '"]') as $elem) {
				if ($beforeId !== $oNewSchema->id) {
					$elem->schema = $oNewSchema->id;
					$elem->find('input', 0)->{'ng-model'} = 'data.' . $oNewSchema->id;
				}
				if ($beforeType !== $oNewSchema->type) {
					$elem->{'schema-type'} = $oNewSchema->type;
					if ($beforeType === 'member') {
						$elem->find('input', 0)->{'ng-init'} = null;
					}
				}
			}
			break;
		case 'V':
			foreach ($dom->find('[schema="' . $beforeId . '"]') as $elem) {
				if ($beforeId !== $oNewSchema->id) {
					$elem->schema = $oNewSchema->id;
					$innertext = $elem->find('>div', 0)->innertext;
					$innertext = str_replace('data.' . $beforeId, 'data.' . $oNewSchema->id, $innertext);
					$elem->find('>div', 0)->innertext = $innertext;
				}
				if ($beforeType !== $oNewSchema->type) {
					$elem->{'schema-type'} = $oNewSchema->type;
				}
			}
			break;
		case 'L':
			foreach ($dom->find('[schema="' . $beforeId . '"]') as $elem) {
				if ($beforeId !== $oNewSchema->id) {
					$elem->schema = $oNewSchema->id;
					$innertext = $elem->find('>div', 0)->innertext;
					$innertext = str_replace('data.' . $beforeId, 'data.' . $oNewSchema->id, $innertext);
					$elem->find('>div', 0)->innertext = $innertext;
				}
				if ($beforeType !== $oNewSchema->type) {
					$elem->{'schema-type'} = $oNewSchema->type;
				}
			}
			break;
		}

		$oPage->html = strval($dom);

		return [true];
	}
	/**
	 *
	 */
	public function htmlBySchema($aSchemas, $template) {
		if (defined('SAE_TMP_PATH')) {
			$tmpfname = tempnam(SAE_TMP_PATH, "template");
		} else {
			$tmpfname = tempnam(sys_get_temp_dir(), "template");
		}
		$handle = fopen($tmpfname, "w");
		fwrite($handle, $template);
		fclose($handle);
		$s = new \Savant3(array('template' => $tmpfname, 'exceptions' => true));
		$s->assign('schema', $aSchemas);
		$html = $s->getOutput();
		unlink($tmpfname);

		return $html;
	}
	/**
	 * 将模板文件生成为html
	 */
	public function compileHtml($pageType, $tmplhtml, $aSchemas) {
		switch ($pageType) {
		case 'I':
			$basePattern = '/<!-- begin: input_base.html -->.*<!-- end: input_base.html -->/s';
			if (preg_match($basePattern, $tmplhtml)) {
				$baseInputHtml = file_get_contents(TMS_APP_TEMPLATE . '/pl/fe/matter/enroll/scenario/input_base.html');
				$tmplhtml = preg_replace($basePattern, $baseInputHtml, $tmplhtml);
			}
			break;
		case 'V':
			$basePattern = '/<!-- begin: view_base.html -->.*<!-- end: view_base.html -->/s';
			if (preg_match($basePattern, $tmplhtml)) {
				$baseInputHtml = file_get_contents(TMS_APP_TEMPLATE . '/pl/fe/matter/enroll/scenario/view_base.html');
				$tmplhtml = preg_replace($basePattern, $baseInputHtml, $tmplhtml);
			}
			break;
		}
		/* 页面存在动态信息 */
		$matched = [];
		$schemaPattern = '/<!-- begin: generate by schema -->.*<!-- end: generate by schema -->/s';
		if (preg_match($schemaPattern, $tmplhtml, $matched)) {
			$schemahtml = $this->htmlBySchema($aSchemas, $matched[0]);
			$tmplhtml = preg_replace($schemaPattern, $schemahtml, $tmplhtml);
		}

		return $tmplhtml;
	}
}