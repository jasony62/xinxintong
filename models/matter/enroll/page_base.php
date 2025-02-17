<?php

namespace matter\enroll;

require_once TMS_APP_DIR . '/lib/PhpSimple/HtmlDomParser.php';

use Sunra\PhpSimple\HtmlDomParser;

abstract class page_base extends \TMS_MODEL
{
  /**
   *
   */
  public function modify($oNewPage, $aProps)
  {
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
      if ($prop === 'data_schemas') {
        $aUpdated['data_schemas'] = $this->escape($this->toJson($oNewPage->dataSchemas));
      } else {
        $aUpdated[$prop] = $oNewPage->{$prop};
      }
    }
    $rst = $this->update($this->table(), $aUpdated, ['id' => $oNewPage->id]);

    return $rst;
  }
  /**
   * 将通讯录题目替换为普通题目
   */
  public function replaceMemberSchema(&$oPage, $oMschema)
  {
    $modelSch = $this->model('matter\enroll\schema');
    $aPageDataSchemas = $oPage->dataSchemas;
    foreach ($aPageDataSchemas as $oPageWrap) {
      switch ($oPage->type) {
        case 'I':
        case 'V':
          if (isset($oPageWrap->schema)) {
            $oSchema = $oPageWrap->schema;
            $oBeforeSchema = clone $oSchema;
            if ($modelSch->wipeMschema($oSchema, $oMschema)) {
              $this->updHtmlBySchema($oPage, $oSchema, $oBeforeSchema);
            }
          }
          break;
        case 'L':
          if (!empty($oPageWrap->schemas)) {
            $oSchemas = $oPageWrap->schemas;
            foreach ($oSchemas as $oSchema) {
              $oBeforeSchema = clone $oSchema;
              if ($modelSch->wipeMschema($oSchema, $oMschema)) {
                $this->updHtmlBySchema($oPage, $oSchema, $oBeforeSchema);
              }
            }
          }
          break;
      }
    }

    $oPage->dataSchemas = $aPageDataSchemas;

    return [true];
  }
  /**
   * 将通讯录题目替换为普通题目
   */
  public function replaceAssocSchema(&$oPage, $aAssocAppIds)
  {
    $modelSch = $this->model('matter\enroll\schema');
    $aPageDataSchemas = $oPage->dataSchemas;

    foreach ($aPageDataSchemas as $oPageWrap) {
      switch ($oPage->type) {
        case 'I':
        case 'V':
          if (isset($oPageWrap->schema)) {
            $oSchema = $oPageWrap->schema;
            $modelSch->wipeAssoc($oSchema, $aAssocAppIds);
          }
          break;
        case 'L':
          if (!empty($oPageWrap->schemas)) {
            $oSchemas = $oPageWrap->schemas;
            foreach ($oSchemas as $oSchema) {
              $modelSch->wipeAssoc($oSchema, $aAssocAppIds);
            }
          }
          break;
      }
    }
    $oPage->dataSchemas = $aPageDataSchemas;

    return [true];
  }
  /**
   * 根据题目的变化修改页面
   */
  public function updHtmlBySchema(&$oPage, $oNewSchema, $oBeforeSchema = null)
  {
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
  public function htmlBySchema($aSchemas, $template)
  {
    $tmpfname = tempnam(sys_get_temp_dir(), "template");
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
  public function compileHtml($pageType, $tmplhtml, $aSchemas)
  {
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
