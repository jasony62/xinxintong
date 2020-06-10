<?php

namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/record_base.php';

class TmsExcel
{
  /**
   *
   */
  public function __construct($title)
  {
    require_once TMS_APP_DIR . '/lib/PHPExcel.php';
    // Create new PHPExcel object
    $this->objPHPExcel = new \PHPExcel();
    // Set properties
    $this->objPHPExcel->getProperties()->setCreator(APP_TITLE)
      ->setLastModifiedBy(APP_TITLE)
      ->setTitle($title)
      ->setSubject($title)
      ->setDescription($title);

    $this->objPHPExcel->setActiveSheetIndex(0);
    $this->objActiveSheet = $this->objPHPExcel->getActiveSheet();
  }
  /**
   * 输出
   */
  public function output($filename)
  {
    header('Content-Type: application/vnd.ms-excel');
    header('Cache-Control: max-age=0');

    $ua = $_SERVER["HTTP_USER_AGENT"];
    if (preg_match("/MSIE/", $ua) || preg_match("/Trident\/7.0/", $ua)) {
      $encoded_filename = urlencode($filename);
      $encoded_filename = str_replace("+", "%20", $encoded_filename);
      header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
    } else if (preg_match("/Firefox/", $ua)) {
      header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
    } else {
      header('Content-Disposition: attachment; filename="' . $filename . '"');
    }

    $objWriter = \PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;
  }
}
/*
 * 导出记录活动数据
 */
class export extends record_base
{
  /**
   * 填写记录导出
   */
  public function record_action($filter = '', $joinDirs = 'Y')
  {
    $oApp = $this->app;
    //是否有协作题
    $isCowork = false;
    //是否有目录
    $isAsdir = false;
    $schemas = $oApp->dynaDataSchemas;
    foreach ($schemas as $oSchema) {
      if ($this->getDeepValue($oSchema, 'cowork') === 'Y') {
        $isCowork = true;
      }
      if ($joinDirs === 'Y') {
        if ($this->getDeepValue($oSchema, 'asdir') === 'Y') {
          $isAsdir = true;
        }
      }
    }

    $modelSch = $this->model('matter\enroll\schema');
    // 加入关联活动的题目
    $modelSch->getUnionSchemas($oApp, $schemas);
    // 关联的分组题目
    $oAssocGrpTeamSchema = $modelSch->getAssocGroupTeamSchema($oApp);

    /* 获得所有有效的填写记录 */
    $modelRec = $this->model('matter\enroll\record');

    // 筛选条件
    $filter = $modelRec->unescape($filter);
    $oCriteria = empty($filter) ? new \stdClass : json_decode($filter);
    $rid = empty($oCriteria->record->rid) ? '' : $oCriteria->record->rid;
    if (!empty($oCriteria->record->group_id)) {
      $gid = $oCriteria->record->group_id;
    } else if (!empty($oAssocGrpTeamSchema) && !empty($oCriteria->data->{$oAssocGrpTeamSchema->id})) {
      $gid = $oCriteria->data->{$oAssocGrpTeamSchema->id};
    } else {
      $gid = '';
    }
    $aOptions = [
      'fields' => 'id,state,enroll_key,rid,purpose,enroll_at,userid,group_id,nickname,verified,comment,data,score,supplement,agreed,like_num,remark_num,favor_num,dislike_num,vote_schema_num',
    ];
    $oResult = $modelRec->byApp($oApp, $aOptions, $oCriteria);
    if ($oResult->total === 0) {
      die('导出数据为空');
    }

    $records = $oResult->records;
    // 处理数据
    $this->_processDatas($oApp, $records);
    $oTmsExcel = new TmsExcel($oApp->title);
    $objActiveSheet = $oTmsExcel->objActiveSheet;
    $columnNum1 = 0; //列号
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '填写时间');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '审核通过');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '填写轮次');
    if ($isAsdir === true) {
      $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '目录');
    }

    // 转换标题
    $aNumberSum = []; // 数值型题目的合计
    $aScoreSum = []; // 题目的分数合计
    $columnNum4 = $columnNum1; //列号
    $bRequireNickname = true;
    if ($this->getDeepValue($oApp, 'assignedNickname.valid') === 'Y' || isset($oApp->assignedNickname->schema->id)) {
      $bRequireNickname = false;
    }
    $bRequireSum = false; // 是否需要计算合计
    $bRequireScore = false; // 是否需要计算总分
    for ($a = 0, $ii = count($schemas); $a < $ii; $a++) {
      $oSchema = $schemas[$a];
      /* 跳过图片,描述说明和文件 */
      if (in_array($oSchema->type, ['html'])) {
        continue;
      }
      // 跳过目录题
      if ($joinDirs === 'Y') {
        if ($this->getDeepValue($oSchema, 'asdir') === 'Y') {
          continue;
        }
      }

      if ($oSchema->type === 'shorttext') {
        /* 数值型，需要计算合计 */
        if (isset($oSchema->format) && $oSchema->format === 'number') {
          $aNumberSum[$columnNum4] = $oSchema->id;
          $bRequireSum = true;
        }
        $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $oSchema->title);
      } else if ($oSchema->type === 'score') {
        /* 打分题，需要计算合计 */
        $aNumberSum[$columnNum4] = $oSchema->id;
        $bRequireSum = true;
        $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $oSchema->title);
        if (!empty($oSchema->ops)) {
          foreach ($oSchema->ops as $op) {
            $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $op->l);
          }
        }
      } else {
        $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $oSchema->title);
      }
      /* 需要补充说明 */
      if ($this->getDeepValue($oSchema, 'supplement') === 'Y') {
        $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '补充说明');
      }
      /* 需要计算数据分 */
      if ($this->getDeepValue($oSchema, 'requireScore') === 'Y') {
        $aScoreSum[$columnNum4] = $oSchema->id;
        $bRequireScore = true;
        $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '数据分');
      }
    }
    if ($bRequireNickname) {
      $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '昵称');
    }
    if (null === $oAssocGrpTeamSchema) {
      $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '分组');
    }
    $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '备注');
    // 记录分数
    if ($oApp->scenario === 'voting') {
      $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总分数');
      $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '平均分数');
      $titles[] = '总分数';
      $titles[] = '平均分数';
    }
    if ($bRequireScore) {
      $aScoreSum[$columnNum4] = 'sum';
      $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总分');
      $titles[] = '总分';
    }
    if ($isCowork === true) {
      $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '答案数');
    }
    $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '赞同数');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '反对数');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '评论数');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总得票数');
    // 转换数据
    for ($j = 0, $jj = count($records); $j < $jj; $j++) {
      $oRecord = $records[$j];
      $rowIndex = $j + 2;
      $recColNum = 0; // 记录列号
      $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, date('y-m-j H:i', $oRecord->enroll_at));
      $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $oRecord->verified);
      // 轮次名
      if (isset($oRecord->round)) {
        $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $oRecord->round->title);
      }
      // 目录
      if ($isAsdir === true) {
        if (!empty($oRecord->recordDir)) {
          $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, implode('/', $oRecord->recordDir));
        } else {
          $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, '');
        }
      }
      // 处理登记项
      $oRecData = $oRecord->data;

      $oRecScore = empty($oRecord->score) ? new \stdClass : $oRecord->score;
      $oRecSupplement = $oRecord->supplement;
      for ($i2 = 0, $ii = count($schemas); $i2 < $ii; $i2++) {
        $oSchema = $schemas[$i2];
        if (in_array($oSchema->type, ['html'])) {
          continue;
        }
        if ($joinDirs === 'Y') {
          if ($this->getDeepValue($oSchema, 'asdir') === 'Y') {
            continue;
          }
        }

        $v = $modelRec->getDeepValue($oRecData, $oSchema->id, '');

        switch ($oSchema->type) {
          case 'single':
            $cellValue = $this->replaceHTMLTags($v, "\n");
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $cellValue, \PHPExcel_Cell_DataType::TYPE_STRING);
            $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
            break;
          case 'multiple':
            if (empty($v)) {
              $cellValue = '';
            } else {
              if (is_object($v)) {
                $v = (array) $v;
              }
              $cellValue = empty($v) ? '' : implode(',', array_values($v));
            }
            $cellValue = $this->replaceHTMLTags($cellValue, "\n");
            $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $cellValue);
            $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
            break;
          case 'score':
            $recColNum2 = $recColNum;
            $labelsSum = 0;
            if (!empty($oSchema->ops)) {
              for ($opi = 0; $opi < count($oSchema->ops); $opi++) {
                $op = $oSchema->ops[$opi];
                $vSr = '';
                if (is_array($v)) {
                  foreach ($v as $vv) {
                    if (isset($op->v) && isset($vv->v) && $vv->v === $op->v) {
                      $labelsSum += $vv->score;
                      $vSr = $vv->score;
                    }
                  }
                }
                $objActiveSheet->setCellValueByColumnAndRow($recColNum2 + $opi + 1, $rowIndex, $vSr);
                $recColNum++;
              }
            }
            $objActiveSheet->setCellValueByColumnAndRow($recColNum2, $rowIndex, $labelsSum);
            $recColNum++;
            break;
          case 'image':
            $v0 = '';
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
            $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
            break;
          case 'file':
            $v0 = '';
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
            $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
            break;
          case 'voice':
            $v0 = is_array($v) ? $v[0]->text : '';
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
            $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
            break;
          case 'date':
            $v = (!empty($v) && is_numeric($v)) ? date('y-m-j H:i', $v) : '';
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
            break;
          case 'shorttext':
            if (isset($oSchema->format) && $oSchema->format === 'number') {
              $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            } else {
              $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
            }
            break;
          case 'multitext':
            if (is_array($v) || is_object($v)) {
              if (is_object($v)) {
                $v = (array) $v;
              }
              $values = [];
              foreach ($v as $val) {
                $values[] = strip_tags($val->value);
              }
              $v = implode("\n", $values);
            }
            if (is_string($v)) {
              $v = str_replace(['&nbsp;', '&amp;'], [' ', '&'], $v);
            } else {
              $v = '';
            }
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
            $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
            break;
          case 'url':
            $v0 = '';
            !empty($v->title) && $v0 .= '【' . $v->title . '】';
            !empty($v->description) && $v0 .= $v->description;
            !empty($v->url) && $v0 .= $v->url;
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
            break;
          default:
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
            break;
        }
        // 补充说明
        if ($this->getDeepValue($oSchema, 'supplement') === 'Y') {
          $supplement = $this->getDeepValue($oRecSupplement, $oSchema->id, '');
          $supplement = preg_replace('/<(style|script|iframe)[^>]*?>[\s\S]+?<\/\1\s*>/i', '', $supplement);
          $supplement = preg_replace('/<[^>]+?>/', '', $supplement);
          $supplement = preg_replace('/\s+/', '', $supplement);
          $supplement = preg_replace('/>/', '', $supplement);
          $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $supplement, \PHPExcel_Cell_DataType::TYPE_STRING);
        }
        // 分数
        if ($this->getDeepValue($oSchema, 'requireScore') === 'Y') {
          $cellScore = $this->getDeepValue($oRecScore, $oSchema->id, 0);
          $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $cellScore, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        }
      }
      // 昵称
      if ($bRequireNickname) {
        $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $oRecord->nickname);
      }
      // 分组
      if (null === $oAssocGrpTeamSchema) {
        $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, isset($oRecord->group->title) ? $oRecord->group->title : '');
      }
      // 备注
      $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $oRecord->comment);
      // 记录投票分数
      if ($oApp->scenario === 'voting') {
        $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $oRecord->_score);
        $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, sprintf('%.2f', $oRecord->_average));
      }
      // 记录测验分数
      if ($bRequireScore) {
        $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, isset($oRecScore->sum) ? $oRecScore->sum : '');
      }
      // 答案数 coworkDataTotal
      if ($isCowork === true) {
        if (isset($oRecord->coworkDataTotal)) {
          $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $oRecord->coworkDataTotal, \PHPExcel_Cell_DataType::TYPE_STRING);
        } else {
          $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, 0, \PHPExcel_Cell_DataType::TYPE_STRING);
        }
      }
      // 点赞
      $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $oRecord->like_num, \PHPExcel_Cell_DataType::TYPE_STRING);
      // 点踩
      $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $oRecord->dislike_num, \PHPExcel_Cell_DataType::TYPE_STRING);
      // 评论
      $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $oRecord->remark_num, \PHPExcel_Cell_DataType::TYPE_STRING);
      // 投票
      $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $oRecord->vote_schema_num, \PHPExcel_Cell_DataType::TYPE_STRING);
    }
    if (!empty($aNumberSum)) {
      // 数值型合计
      $rowIndex = count($records) + 2;
      $oSum4Schema = $modelRec->sum4Schema($oApp, $rid, $gid);
      $objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
      foreach ($aNumberSum as $key => $val) {
        $objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, $oSum4Schema->$val);
      }
    }
    if (!empty($aScoreSum)) {
      // 分数合计
      $rowIndex = count($records) + 2;
      $oScore4Schema = $modelRec->score4Schema($oApp, $rid, $gid);
      $objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
      foreach ($aScoreSum as $key => $val) {
        $objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, isset($oScore4Schema->$val) ? $oScore4Schema->$val : '');
      }
    }

    $oTmsExcel->output($oApp->title . '.xlsx');
  }
  /**
   * 导出记录中的图片
   *
   * @param string $rid 轮次id
   */
  public function image_action($app, $rid = '', $range = '1,30')
  {
    $oNameSchema = null;
    $aImageSchemas = [];

    // 记录活动
    $oApp = $this->app;

    $schemas = $oApp->dynaDataSchemas;
    $modelSch = $this->model('matter\enroll\schema');
    // 加入关联活动的题目
    $modelSch->getUnionSchemas($oApp, $schemas);

    foreach ($schemas as $oSchema) {
      if ($oSchema->type === 'image') {
        $aImageSchemas[] = $oSchema;
      } else if ($oSchema->id === 'name' || (in_array($oSchema->title, ['姓名', '名称']))) {
        $oNameSchema = $oSchema;
      }
    }

    if (count($aImageSchemas) === 0) {
      die('活动不包含图片数据');
    }

    $aOptions2 = null;
    if (!empty($range)) {
      list($page, $size) = explode(',', $range);
      $aOptions2 = [
        'page' => $page,
        'size' => $size,
      ];
    }

    // 获得所有有效的填写记录
    $oOptions = new \stdClass;
    $oOptions->record = new \stdClass;
    $oOptions->record->rid = empty($rid) ? 'all' : explode(',', $rid);
    $oResult = $this->model('matter\enroll\record')->byApp($oApp, $aOptions2, $oOptions);
    if (empty($oResult->records)) {
      die('填写记录为空');
    }
    $records = $oResult->records;
    // 转换数据
    $aImages = [];
    array_walk($records, function ($oRecord) use (&$aImages, $aImageSchemas) {
      // 处理登记项
      $oRecData = $oRecord->data;
      array_walk($aImageSchemas, function ($oSchema) use (&$aImages, $oRecData) {
        if (!empty($oRecData->{$oSchema->id})) {
          $urls = explode(',', $oRecData->{$oSchema->id});
          foreach ($urls as $url) {
            $aImages[] = ['url' => $url, 'schema' => $oSchema, 'data' => $oRecData];
          }
        }
      });
    });

    if (count($aImages) === 0) {
      die('填写记录(' . count($records) . '条)中不包含图片');
    }

    // 输出
    $usedRecordName = [];
    // 输出打包文件
    $zipFilename = tempnam('/tmp', $oApp->id);
    $zip = new \ZipArchive;
    if ($zip->open($zipFilename, \ZIPARCHIVE::CREATE) === false) {
      die('无法打开压缩文件，或者文件创建失败');
    }
    $validImageNumber = 0;
    foreach ($aImages as $image) {
      $imageFilename = TMS_APP_DIR . '/' . $image['url'];
      if (file_exists($imageFilename)) {
        $imageName = basename($imageFilename);
        /**
         * 图片文件名称替换
         */
        if (isset($oNameSchema)) {
          $data = $image['data'];
          if (!empty($data->{$oNameSchema->id})) {
            $recordName = $data->{$oNameSchema->id};
            if (isset($usedRecordName[$recordName])) {
              $usedRecordName[$recordName]++;
              $recordName = $recordName . '_' . $usedRecordName[$recordName];
            } else {
              $usedRecordName[$recordName] = 0;
            }
            $imageName = $recordName . '.' . explode('.', $imageName)[1];
          }
        }
        $zip->addFile($imageFilename, $image['schema']->title . '/' . $imageName);
        $validImageNumber++;
      }
    }
    $zip->close();

    if (!file_exists($zipFilename)) {
      exit("无法找到压缩文件");
    }
    $downloadFilename = $oApp->title;
    if (!empty($aOptions2['page'])) {
      $downloadFilename .= '-' . $aOptions2['page'];
    }
    $downloadFilename .= '.zip';
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header('Content-disposition: attachment; filename=' . $downloadFilename);
    header("Content-Type: application/zip");
    header("Content-Transfer-Encoding: binary");
    header('Content-Length: ' . filesize($zipFilename));
    header("Export-Image-Number: " . $validImageNumber);
    @readfile($zipFilename);

    exit;
  }
  /**
   * 填写记录导出
   */
  public function cowork_action($app, $filter = '')
  {
    $oApp = $this->app;
    //是否有协作题
    $isCowork = false;
    //是否有目录
    $isAsdir = false;
    $schemas = $oApp->dynaDataSchemas;
    foreach ($schemas as $oSchema) {
      if ($this->getDeepValue($oSchema, 'cowork') === 'Y') {
        $isCowork = true;
      }
      if ($this->getDeepValue($oSchema, 'asdir') === 'Y') {
        $isAsdir = true;
      }
    }
    if ($isCowork === false) {
      die('没有多人协作题');
    }

    $modelSch = $this->model('matter\enroll\schema');
    // 加入关联活动的题目
    $modelSch->getUnionSchemas($oApp, $schemas);
    // 关联的分组题目
    $oAssocGrpTeamSchema = $modelSch->getAssocGroupTeamSchema($oApp);

    /* 获得所有有效的填写记录 */
    $modelRec = $this->model('matter\enroll\record');

    // 筛选条件
    $filter = $modelRec->unescape($filter);
    $oPosted = empty($filter) ? new \stdClass : json_decode($filter);

    $oOptions = new \stdClass;
    $oOptions->fields = 'r.id record_id,rd.id data_id,rd.enroll_key,rd.rid,rd.purpose,rd.submit_at enroll_at,rd.userid,rd.group_id,rd.nickname,rd.schema_id,rd.value,rd.score,rd.agreed,rd.like_num,rd.remark_num,rd.dislike_num,r.data,rd.vote_num,r.verified,r.supplement,r.comment';

    // 查询结果
    $modelRecDat = $this->model('matter\enroll\data');
    $oCriteria = new \stdClass;
    !empty($oPosted->keyword) && $oCriteria->keyword = $oPosted->keyword;

    $oCriteria->recordData = new \stdClass;
    $rid = $oCriteria->recordData->rid = !empty($oPosted->record->rid) ? $oPosted->record->rid : '';

    /* 指定了分组过滤条件 */
    if (!empty($oPosted->record->group_id)) {
      $gid = $oCriteria->recordData->group_id = $oPosted->record->group_id;
    } else if (!empty($oAssocGrpTeamSchema) && !empty($oPosted->data->{$oAssocGrpTeamSchema->id})) {
      $gid = $oPosted->data->{$oAssocGrpTeamSchema->id};
    } else {
      $gid = '';
    }
    // 指定题目填写数据筛选
    if (!empty($oPosted->data)) {
      $oCriteria->data = $oPosted->data;
    }
    // 指定了答案题
    $coworkSchemaIds = [];
    if (!empty($oPosted->coworkSchemaIds) && is_array($oPosted->coworkSchemaIds)) {
      $coworkSchemaIds = $oPosted->coworkSchemaIds;
    }

    $oResult = $modelRecDat->coworkDataByApp($oApp, $oOptions, $oCriteria, null, $coworkSchemaIds);
    if ($oResult->total === 0) {
      die('导出数据为空');
    }
    $records = $oResult->recordDatas;
    // 处理数据
    $this->_processDatas($oApp, $records, 'coworkDataList');

    $oTmsExcel = new TmsExcel($oApp->title);
    $objActiveSheet = $oTmsExcel->objActiveSheet;
    $columnNum1 = 0; //列号
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '填写时间');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '审核通过');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '填写轮次');
    if ($isAsdir === true) {
      $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '目录');
    }

    // 转换标题
    $aNumberSum = []; // 数值型题目的合计
    $aScoreSum = []; // 题目的分数合计
    $columnNum4 = $columnNum1; //列号
    $bRequireNickname = true;
    if ($this->getDeepValue($oApp, 'assignedNickname.valid') === 'Y' || isset($oApp->assignedNickname->schema->id)) {
      $bRequireNickname = false;
    }
    $bRequireSum = false; // 是否需要计算合计
    $bRequireScore = false; // 是否需要计算总分
    for ($a = 0, $ii = count($schemas); $a < $ii; $a++) {
      $oSchema = $schemas[$a];
      /* 跳过图片,描述说明和文件 */
      if (in_array($oSchema->type, ['html'])) {
        continue;
      }
      // 跳过目录题
      if ($this->getDeepValue($oSchema, 'asdir') === 'Y') {
        continue;
      }
      if ($oSchema->type === 'shorttext') {
        /* 数值型，需要计算合计 */
        if (isset($oSchema->format) && $oSchema->format === 'number') {
          $aNumberSum[$columnNum4] = $oSchema->id;
          $bRequireSum = true;
        }
        $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $oSchema->title);
      } else if ($oSchema->type === 'score') {
        /* 打分题，需要计算合计 */
        $aNumberSum[$columnNum4] = $oSchema->id;
        $bRequireSum = true;
        $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $oSchema->title);
        if (!empty($oSchema->ops)) {
          foreach ($oSchema->ops as $op) {
            $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $op->l);
          }
        }
      } else {
        $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, $oSchema->title);
      }
      /* 需要补充说明 */
      if ($this->getDeepValue($oSchema, 'supplement') === 'Y') {
        $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '补充说明');
      }
      /* 需要计算数据分 */
      if ($this->getDeepValue($oSchema, 'requireScore') === 'Y') {
        $aScoreSum[$columnNum4] = $oSchema->id;
        $bRequireScore = true;
        $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '数据分');
      }
    }
    if ($bRequireNickname) {
      $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '昵称');
    }
    if (null === $oAssocGrpTeamSchema) {
      $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '分组');
    }
    $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '备注');
    // 记录分数
    if ($oApp->scenario === 'voting') {
      $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总分数');
      $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '平均分数');
      $titles[] = '总分数';
      $titles[] = '平均分数';
    }
    if ($bRequireScore) {
      $aScoreSum[$columnNum4] = 'sum';
      $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '总分');
      $titles[] = '总分';
    }
    $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '赞同数');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '反对数');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '评论数');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum4++, 1, '得票数');
    // 转换数据
    for ($j = 0, $jj = count($records); $j < $jj; $j++) {
      $oRecord = $records[$j];
      $rowIndex = $j + 2;
      $recColNum = 0; // 记录列号
      $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, date('y-m-j H:i', $oRecord->enroll_at));
      $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $oRecord->verified);
      // 轮次名
      if (isset($oRecord->round)) {
        $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $oRecord->round->title);
      }
      // 目录
      if ($isAsdir === true) {
        if (!empty($oRecord->recordDir)) {
          $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, implode('/', $oRecord->recordDir));
        } else {
          $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, '');
        }
      }
      // 处理登记项
      $oRecData = $oRecord->data;
      $oRecScore = empty($oRecord->score) ? new \stdClass : $oRecord->score;
      $oRecSupplement = $oRecord->supplement;
      for ($i2 = 0, $ii = count($schemas); $i2 < $ii; $i2++) {
        $oSchema = $schemas[$i2];
        if (in_array($oSchema->type, ['html'])) {
          continue;
        }
        $v = $modelRec->getDeepValue($oRecData, $oSchema->id, '');
        switch ($oSchema->type) {
          case 'single':
            if ($this->getDeepValue($oSchema, 'asdir') === 'Y') {
              continue 2;
            }
            $cellValue = $this->replaceHTMLTags($v, "\n");
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $cellValue, \PHPExcel_Cell_DataType::TYPE_STRING);
            $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
            break;
          case 'multiple':
            $cellValue = implode(',', $v);
            $cellValue = $this->replaceHTMLTags($cellValue, "\n");
            $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $cellValue);
            $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
            break;
          case 'score':
            $recColNum2 = $recColNum;
            $labelsSum = 0;
            if (!empty($oSchema->ops)) {
              for ($opi = 0; $opi < count($oSchema->ops); $opi++) {
                $op = $oSchema->ops[$opi];
                $vSr = '';
                foreach ($v as $vv) {
                  if ($vv->v == $op->v) {
                    $labelsSum += $vv->score;
                    $vSr = $vv->score;
                  }
                }
                $objActiveSheet->setCellValueByColumnAndRow($recColNum2 + $opi + 1, $rowIndex, $vSr);
                $recColNum++;
              }
            }
            $objActiveSheet->setCellValueByColumnAndRow($recColNum2, $rowIndex, $labelsSum);
            $recColNum++;
            break;
          case 'image':
            $v0 = '';
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
            $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
            break;
          case 'file':
            $v0 = '';
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
            $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
            break;
          case 'voice':
            $v0 = is_array($v) ? $v[0]->text : '';
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
            $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
            break;
          case 'date':
            $v = (!empty($v) && is_numeric($v)) ? date('y-m-j H:i', $v) : '';
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
            break;
          case 'shorttext':
            if (isset($oSchema->format) && $oSchema->format === 'number') {
              $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            } else {
              $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
            }
            break;
          case 'multitext':
            if (is_array($v)) {
              $values = [];
              foreach ($v as $val) {
                $values[] = strip_tags($val->value);
              }
              $v = implode("\n", $values);
            }
            if (is_string($v)) {
              $v = str_replace(['&nbsp;', '&amp;'], [' ', '&'], $v);
            } else {
              $v = '';
            }
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
            $objActiveSheet->getStyleByColumnAndRow($recColNum - 1, $rowIndex)->getAlignment()->setWrapText(true);
            break;
          case 'url':
            $v0 = '';
            !empty($v->title) && $v0 .= '【' . $v->title . '】';
            !empty($v->description) && $v0 .= $v->description;
            !empty($v->url) && $v0 .= $v->url;
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v0, \PHPExcel_Cell_DataType::TYPE_STRING);
            break;
          default:
            $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $v, \PHPExcel_Cell_DataType::TYPE_STRING);
            break;
        }
        // 补充说明
        if ($this->getDeepValue($oSchema, 'supplement') === 'Y') {
          $supplement = $this->getDeepValue($oRecSupplement, $oSchema->id, '');
          $supplement = preg_replace('/<(style|script|iframe)[^>]*?>[\s\S]+?<\/\1\s*>/i', '', $supplement);
          $supplement = preg_replace('/<[^>]+?>/', '', $supplement);
          $supplement = preg_replace('/\s+/', '', $supplement);
          $supplement = preg_replace('/>/', '', $supplement);
          $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $supplement, \PHPExcel_Cell_DataType::TYPE_STRING);
        }
        // 分数
        if ($this->getDeepValue($oSchema, 'requireScore') === 'Y') {
          $cellScore = $this->getDeepValue($oRecScore, $oSchema->id, 0);
          $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $cellScore, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
        }
      }
      // 昵称
      if ($bRequireNickname) {
        $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $oRecord->nickname);
      }
      // 分组
      if (null === $oAssocGrpTeamSchema) {
        $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, isset($oRecord->group->title) ? $oRecord->group->title : '');
      }
      // 备注
      $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $oRecord->comment);
      // 记录投票分数
      if ($oApp->scenario === 'voting') {
        $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, $oRecord->_score);
        $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, sprintf('%.2f', $oRecord->_average));
      }
      // 记录测验分数
      if ($bRequireScore) {
        $objActiveSheet->setCellValueByColumnAndRow($recColNum++, $rowIndex, isset($oRecScore->sum) ? $oRecScore->sum : '');
      }
      // 点赞
      $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $oRecord->like_num, \PHPExcel_Cell_DataType::TYPE_STRING);
      // 点踩
      $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $oRecord->dislike_num, \PHPExcel_Cell_DataType::TYPE_STRING);
      // 评论
      $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $oRecord->remark_num, \PHPExcel_Cell_DataType::TYPE_STRING);
      // 投票
      $objActiveSheet->setCellValueExplicitByColumnAndRow($recColNum++, $rowIndex, $oRecord->vote_num, \PHPExcel_Cell_DataType::TYPE_STRING);
    }
    if (!empty($aNumberSum)) {
      // 数值型合计
      $rowIndex = count($records) + 2;
      $oSum4Schema = $modelRec->sum4Schema($oApp, $rid, $gid);
      $objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
      foreach ($aNumberSum as $key => $val) {
        $objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, $oSum4Schema->$val);
      }
    }
    if (!empty($aScoreSum)) {
      // 分数合计
      $rowIndex = count($records) + 2;
      $oScore4Schema = $modelRec->score4Schema($oApp, $rid, $gid);
      $objActiveSheet->setCellValueByColumnAndRow(0, $rowIndex, '合计');
      foreach ($aScoreSum as $key => $val) {
        $objActiveSheet->setCellValueByColumnAndRow($key, $rowIndex, isset($oScore4Schema->$val) ? $oScore4Schema->$val : '');
      }
    }
    $oTmsExcel->output($oApp->title . '.xlsx');
  }
  /**
   * 导出用户完成情况
   */
  public function enrollee_action($rids = '')
  {
    $oApp = $this->app;

    $modelUsr = $this->model('matter\enroll\user');

    $rids = empty($rids) ? [] : explode(',', $rids);

    $aOptions = [];
    $aOptions['rid'] = $rids;
    $oResult = $modelUsr->enrolleeByApp($oApp, $page = '', $size = '', $aOptions);

    // 判断关联公众号
    $road = ['wx', 'qy'];
    $sns = new \stdClass;
    foreach ($road as $v) {
      $arr = array();
      $config = $modelUsr->query_obj_ss(['joined', 'xxt_site_' . $v, ['siteid' => $oApp->siteid]]);
      if (!empty($config->joined)) {
        $arr['joined'] = $config->joined;
        $sns->{$v} = (object) $arr;
      }
    }

    $oTmsExcel = new TmsExcel($oApp->title);
    $objActiveSheet = $oTmsExcel->objActiveSheet;
    $objActiveSheet->setTitle('已参与');

    $columnNum1 = 0; //列号
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '序号');
    // 转换标题
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '用户');
    if (!empty($oApp->entryRule->group->id)) {
      $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '分组');
    }
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '总访问次数');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '总访问时长');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '记录');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '留言');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '点赞');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '获得推荐');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '行为分');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '数据分');
    if (isset($sns->wx->joined) && $sns->wx->joined === 'Y') {
      $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '已关联微信');
    }
    if (isset($sns->qy->joined) && $sns->qy->joined === 'Y') {
      $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '已关联微企');
    }

    // 转换数据
    for ($j = 0; $j < count($oResult->users); $j++) {
      $oUser = $oResult->users[$j];
      $rowIndex = $j + 2;
      $columnNum2 = 0; //列号

      $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $j + 1);
      $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->nickname);
      if (!empty($oApp->entryRule->group->id)) {
        $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, empty($oUser->group) ? '' : $oUser->group->title);
      }
      $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->entry_num);
      $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, date('y-m-j H:i', $oUser->last_entry_at));
      $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->enroll_num);
      $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->do_remark_num);
      $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->do_like_num);
      $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->agree_num);
      $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->user_total_coin);
      $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->score);
      if (isset($sns->wx->joined) && $sns->wx->joined === 'Y') {
        $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, !empty($oUser->wx_openid) ? "是" : '');
      }
      if (isset($sns->qy->joined) && $sns->qy->joined === 'Y') {
        $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, !empty($oUser->qy_openid) ? "是" : '');
      }
    }
    $oTmsExcel->output($oApp->title . '.xlsx');
  }
  /**
   * 未完成
   */
  public function undone_action($rids = null)
  {
    $oApp = $this->app;

    $modelUsr = $this->model('matter\enroll\user');
    $modelRnd = $this->model('matter\enroll\round');
    if (empty($rids)) {
      $aRounds = [$this->app->appRound->rid => $this->app->appRound];
    } else if (1 === preg_match('/^all$/i', $rids)) {
      $oResultRounds = $modelRnd->byApp($oApp, ['fields' => 'rid,title,start_at,purpose']);
      $aRounds = [];
      if (count($oResultRounds->rounds)) {
        array_walk($oResultRounds->rounds, function ($oRnd) use (&$aRounds) {
          $aRounds[$oRnd->rid] = $oRnd;
        });
      }
    } else {
      $aRounds = $modelRnd->byIds($rids, ['fields' => 'rid,title,start_at,purpose']);
    }
    if (empty($aRounds)) {
      die('指定的轮次不存在');
    }

    foreach ($aRounds as $rid => $oRnd) {
      $oUndoneResult = $modelUsr->undoneByApp($oApp, $rid);
      if (!empty($oUndoneResult->users)) {
        foreach ($oUndoneResult->users as $oUser) {
          $oUser->round = $oRnd;
          $aUsers[] = $oUser;
        }
      }
    }

    if (empty($aUsers)) {
      die('没有数据');
    }

    $oTmsExcel = new TmsExcel($oApp->title);
    $oTmsExcel->objActiveSheet->setTitle('缺席');
    $objActiveSheet = $oTmsExcel->objActiveSheet;

    $colNumber = 0;
    $objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '序号');
    $objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '轮次');
    $objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '姓名');
    $objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '分组');
    $objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '请假（开始）');
    $objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '请假（结束）');

    $rowNumber = 2;
    foreach ($aUsers as $oUndoneUser) {
      $colNumber = 0;
      $objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $rowNumber - 1);
      $objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $oUndoneUser->round->title);
      $objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $oUndoneUser->nickname);
      $objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $modelUsr->getDeepValue($oUndoneUser, 'group.title', ''));
      if ($oValidLeave = $modelUsr->getDeepValue($oUndoneUser, 'validLeave')) {
        $objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, date('y-m-j H:i', $oValidLeave->begin_at));
        $objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, date('y-m-j H:i', $oValidLeave->end_at));
      } else {
        $objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
        $objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, '');
      }
      $rowNumber++;
    }

    $oTmsExcel->output($oApp->title . '（缺席）.xlsx');
  }
  /**
   * 将应用定义导出为模板
   */
  public function appTemplate_action()
  {
    $modelApp = $this->model('matter\enroll');
    $oApp = $this->app;

    $template = new \stdClass;
    /* setting */
    !empty($oApp->scenario) && $template->scenario = $oApp->scenario;
    $template->count_limit = $oApp->count_limit;

    /* schema */
    $template->schema = json_decode($oApp->data_schemas);

    /* pages */
    $pages = $oApp->pages;
    foreach ($pages as &$rec) {
      $rec->data_schemas = json_decode($rec->data_schemas);
      $rec->act_schemas = json_decode($rec->act_schemas);
      $code = new \stdClass;
      $code->css = $rec->css;
      $code->js = $rec->js;
      $code->html = $rec->html;
      $rec->code = $code;
    }
    $template->pages = $pages;

    /* entry_rule */
    $template->entryRule = $oApp->entryRule;

    /* records */
    $records = $modelApp->query_objs_ss([
      'id,userid,nickname,data',
      'xxt_enroll_record',
      ['siteid' => $oApp->siteid, 'aid' => $oApp->id],
    ]);

    foreach ($records as &$rec) {
      $rec->data = json_decode($rec->data);
    }
    $template->records = $records;

    $template = $modelApp->toJson($template);
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header('Content-disposition: attachment; filename=' . $oApp->title . '.json');
    header("Content-Type: text/plain");
    header('Content-Length: ' . strlen($template));
    die($template);
  }
}
