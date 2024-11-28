<?php

namespace api\matter\enroll;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 记录活动控制器
 */
class Record extends \api\base
{
  /*
	 * 添加记录
	 */
  public function add_action($accessToken, $app)
  {
    if (empty($accessToken) || empty($app)) {
      return new \ParameterError('参数不完整');
    }

    // 校验accessToken
    $checkRes = $this->checkToken($accessToken);
    if (!$checkRes[0]) {
      return new \ParameterError($checkRes[1]);
    }

    // 记录活动
    $modelApp = $this->model('matter\enroll');
    $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
    if (false === $oApp) {
      return [false, new \ObjectNotFoundError()];
    }


    // $oOperator = new \stdClass;

    $posted = $this->getPostJson();

    $addUser = new \stdClass;

    $modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);

    /* 创建填写记录 */
    $oNewRec = $modelRec->enroll($oApp, $addUser);

    $record = [];
    $record['verified'] = isset($posted->verified) ? $posted->verified : 'N';
    $record['comment'] = isset($posted->comment) ? $posted->comment : '';
    $modelRec->update('xxt_enroll_record', $record, ['enroll_key' => $oNewRec->enroll_key]);

    /* 记录登记数据 */
    // $addUser = $this->model('site\fe\way')->who($oApp->siteid);
    $modelRec->setData(null, $oApp, $oNewRec->enroll_key, $posted->data, $addUser->uid, true);

    /* 记录操作日志 */
    // $oRecord = $modelRec->byId($oNewRec->enroll_key, ['fields' => 'enroll_key,data,rid']);
    // $this->model('matter\log')->matterOp($oApp->siteid, $oOperator, $oApp, 'add', $oRecord);

    return new \ResponseData($oNewRec->enroll_key);
  }
}
