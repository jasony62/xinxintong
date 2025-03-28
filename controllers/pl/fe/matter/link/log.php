<?php

namespace pl\fe\matter\link;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/*
 * 记录活动日志控制器
 */
class log extends \pl\fe\matter\main_base
{
  /**
   *
   */
  public function get_access_rule()
  {
    $rule_action['rule_type'] = 'white';
    $rule_action['actions'][] = 'renewNickname';

    return $rule_action;
  }
  /**
   *
   */
  public function index_action()
  {
    \TPL::output('/pl/fe/matter/link/frame');
    exit;
  }
  /**
   * 查询日志
   *
   */
  public function list_action($id, $page = 1, $size = 30)
  {
    $modelLog = $this->model('matter\log');

    $reads = $modelLog->listUserMatterOp($id, 'link', [], $page, $size);

    return new \ResponseData($reads);
  }
  /**
   * 查询详细日志
   *
   */
  public function matterActionLog_action($site, $appId, $page = 1, $size = 30)
  {
    $modelLog = $this->model('matter\log');

    $filter = $this->getPostJson();
    $options = [];
    if (!empty($filter->byEvent)) {
      $options['byEvent'] = $filter->byEvent;
    }
    if (!empty($filter->startAt)) {
      $options['startAt'] = $filter->startAt;
    }
    if (!empty($filter->endAt)) {
      $options['endAt'] = $filter->endAt;
    }
    if (!empty($page) && !empty($size)) {
      $options['paging'] = ['page' => $page, 'size' => $size];
    }

    $data = $modelLog->listMatterAction($site, 'link', $appId, $options);

    return new \ResponseData($data);
  }
  /**
   * 导出详细日志
   */
  public function exportMatterActionLog_action($site, $appId, $startAt = '', $endAt = '', $byEvent = '')
  {
    if (empty($startAt)) {
      die('未找到开始时间');
    }
    $modelAct = $this->model('matter\link');
    $oLink = $modelAct->byId($appId, ['fields' => 'id,title']);
    if ($oLink === false) {
      die('指定链接不存在或已删除');
    }

    $options = [];
    $options['startAt'] = $startAt;
    if (!empty($endAt)) {
      $options['endAt'] = $endAt;
    }
    if (!empty($byEvent)) {
      $options['byEvent'] = $byEvent;
    }

    $modelLog = $this->model('matter\log');
    $logs = $modelLog->listMatterAction($site, 'link', $appId, $options)->logs;

    require_once TMS_APP_DIR . '/lib/PHPExcel.php';
    // Create new PHPExcel object
    $objPHPExcel = new \PHPExcel();
    // Set properties
    $objPHPExcel->getProperties()->setCreator(APP_TITLE)
      ->setLastModifiedBy(APP_TITLE)
      ->setTitle($oLink->title)
      ->setSubject($oLink->title)
      ->setDescription($oLink->title);
    $objActiveSheet = $objPHPExcel->getActiveSheet();
    $columnNum1 = 0; //列号
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '时间');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '用户名');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '操作');
    $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '来源');

    // 转换数据
    for ($j = 0, $jj = count($logs); $j < $jj; $j++) {
      $log = $logs[$j];
      $rowIndex = $j + 2;
      $columnNum2 = 0; //列号
      $actionAt = date('Y-m-d H:i:s', $log->action_at);
      $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $actionAt);
      $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $log->nickname);
      if ($log->act_read > 0) {
        $event = '阅读';
      } else if ($log->act_share_timeline > 0) {
        $event = '分享至朋友圈';
      } else if ($log->act_share_friend > 0) {
        $event = '转发给朋友';
      } else {
        $event = '未知';
      }
      $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $event);
      $originNickname = isset($log->origin_nickname) ? $log->origin_nickname : '';
      $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $originNickname);
    }
    // 输出
    header('Content-Type: application/vnd.ms-excel');
    header('Cache-Control: max-age=0');

    $filename = $oLink->title . '.xlsx';
    $ua = $_SERVER["HTTP_USER_AGENT"];
    //if (preg_match("/MSIE/", $ua) || preg_match("/Trident\/7.0/", $ua)) {
    if (preg_match("/MSIE/", $ua)) {
      $encoded_filename = urlencode($filename);
      $encoded_filename = str_replace("+", "%20", $encoded_filename);
      $encoded_filename = iconv('UTF-8', 'GBK//IGNORE', $encoded_filename);
      header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
    } else if (preg_match("/Firefox/", $ua)) {
      header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
    } else {
      header('Content-Disposition: attachment; filename="' . $filename . '"');
    }

    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;
  }
  /**
   * 附件下载日志
   */
  public function attachmentLog_action($appId, $page = 1, $size = 30)
  {
    $modelLink = $this->model('matter\link');
    $oLink = $modelLink->byId($appId, ['fields' => 'id,title,state']);
    if ($oLink === false || $oLink->state != 1) {
      return new \ObjectNotFoundError();
    }

    $filter = $this->getPostJson();

    $q = [
      'ml.id,ml.userid,ml.openid,ml.nickname,ml.download_at,ml.attachment_id,m.name',
      'xxt_matter_download_log ml,xxt_matter_attachment m',
      "ml.matter_id = '{$appId}' and ml.matter_type = 'link' and ml.attachment_id = m.id",
    ];
    if (!empty($filter->start)) {
      $q[2] .= " and ml.download_at > " . $filter->start;
    }
    if (!empty($filter->end)) {
      $q[2] .= " and ml.download_at < " . $filter->end;
    }
    if (!empty($filter->byUser)) {
      $q[2] .= " and ml.nickname like '%" . $filter->byUser . "%'";
    }

    $p = ['o' => 'ml.download_at desc'];
    if (!empty($page) && !empty($size)) {
      $p['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
    }

    $logs = $modelLink->query_objs_ss($q, $p);

    $data = new \stdClass;
    $data->logs = $logs;
    $q[0] = 'count(ml.id)';
    $data->total = $modelLink->query_val_ss($q);

    return new \ResponseData($data);
  }
  /**
   * 
   */
  private function _getUserSource($entryRule)
  {
    if (empty($entryRule->scope)) return false;

    $sources = [];
    /**分组活动*/
    if ($this->getDeepValue($entryRule->scope, 'group') === 'Y' && is_object($entryRule->group)) {
      $source = new \stdClass;
      $source->type = 'group';
      $source->id = $entryRule->group->id;
      $sources[] = $source;
    }
    /**通讯录*/
    if ($this->getDeepValue($entryRule->scope, 'member') === 'Y' && count(array_keys((array)($entryRule->member))) > 0) {
      $source = new \stdClass;
      $source->type = 'member';
      $source->id = array_keys((array)$entryRule->member)[0];
      $sources[] = $source;
    }

    return $sources;
  }
  /**
   * 更新日志数据中的用户昵称
   */
  public function renewNickname_action($appId)
  {
    $modelLink = $this->model('matter\link');
    $oLink = $modelLink->byId($appId);
    if (false === $oLink)
      return new \ObjectNotFoundError();

    $userSource = $this->_getUserSource($oLink->entry_rule);
    if ($userSource === false || count($userSource) === 0)
      return new \ResponseError('没有指定用户来源，无法更新昵称');

    $userApp = $userSource[0];

    $filter = $this->getPostJson();

    $q = [
      'distinct userid',
      'xxt_log_matter_read',
      ['matter_type' => 'link', 'matter_id' => $appId],
    ];
    if (!empty($filter->startAt) && !empty($filter->endAt))
      $q[2]['read_at'] = (object)['op' => 'between', 'pat' => [$filter->startAt, $filter->endAt]];

    $userids = $modelLink->query_vals_ss($q);
    if ($userApp->type === 'group') {
      foreach ($userids as $userid) {
        $q = [
          'nickname',
          'xxt_group_record',
          ['aid' => $userApp->id, 'userid' => $userid, 'state' => 1],
        ];
        $nickname = $modelLink->query_val_ss($q);
        // 更新数据
        $modelLink->update(
          'xxt_log_matter_read',
          ['nickname' => $nickname],
          ['matter_type' => 'link', 'matter_id' => $appId, 'userid' => $userid],
        );
      }
    } else  if ($userApp->type === 'member') {
      foreach ($userids as $userid) {
        $q = [
          'name',
          'xxt_site_member',
          ['schema_id' => $userApp->id, 'userid' => $userid, 'forbidden' => 'N'],
        ];
        $nickname = $modelLink->query_val_ss($q);
        // 更新数据
        $modelLink->update(
          'xxt_log_matter_read',
          ['nickname' => $nickname],
          ['matter_type' => 'link', 'matter_id' => $appId, 'userid' => $userid],
        );
      }
    }

    return new \ResponseData($userids);
  }
}
