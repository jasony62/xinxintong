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
			break;
		}

		$oPage->html = strval($dom);

		return [true];
	}
}