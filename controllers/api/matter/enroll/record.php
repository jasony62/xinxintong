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
    $addUser->uid = isset($posted->uid) ? $posted->uid : '';

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
  /**
   * 获得活动记录列表
   * 
   * body
   * -- record
   * -- data
   */
  public function list_action($accessToken, $app, $page = 1, $size = 30, $fields = '')
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

    // 登记数据过滤条件
    $oCriteria = $this->getPostJson();

    $aOptions = [
      'page' => $page,
      'size' => $size,
      'fields' => empty($fields) ? 'id,state,enroll_key,rid,purpose,enroll_at,userid,group_id,nickname,verified,comment,data,score,supplement,agreed,like_num,remark_num,favor_num,dislike_num,vote_schema_num' : $fields,
    ];

    $modelRec = $this->model('matter\enroll\record');
    $oResult = $modelRec->byApp($oApp, $aOptions, $oCriteria);

    return new \ResponseData($oResult);
  }
  /**
   * 更新一条记录
   */
  public function update_action($accessToken, $app, $ek)
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
      return  new \ObjectNotFoundError();
    }

    $modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);
    // 数据对应的记录记录
    $oRecord = $modelRec->byId($ek);
    if (false === $oRecord || !in_array($oRecord->state, ['1', '99'])) {
      return new \ParameterError('指定的记录不存在');
    }

    $posted = $this->getPostJson();

    if (empty($posted->data)) {
      return new \ParameterError('参数不完整，没有指定要更新的数据');
    }

    $oUser = new \stdClass;
    $oUser->uid = isset($posted->uid) ? $posted->uid : '';

    $newData = (object)array_merge((array)$oRecord->data, (array)$posted->data);

    $oResult = $modelRec->setData($oUser, $oApp, $ek, $newData);

    if ($oResult[0] === true) {
      return new \ResponseData($oResult[1]);
    }

    return new \ResponseError($oResult[1]);
  }
}
