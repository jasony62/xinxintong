<?php

namespace pl\fe\matter\article;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 阅读用户
 */
class user extends \pl\fe\matter\base
{
  /**
   * 
   */
  private $logger;
  /**
   * 
   */
  public function __construct()
  {
    $this->logger = \Logger::getLogger(__CLASS__);
  }
  /**
   *
   */
  public function index_action($id)
  {
    $access = $this->accessControlUser('article', $id);
    if ($access[0] === false) {
      die($access[1]);
    }

    \TPL::output('/pl/fe/matter/article/frame');
    exit;
  }
  /**
   * 获得用户阅读数据 
   */
  private function _getAccessLog($articleId, $aUserIds)
  {
    $posted = json_encode(['uids' => $aUserIds]);
    $url = BACK_API_ADDRESS . "/pl/fe/matter/article/event/users?article=$articleId";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $posted);
    curl_setopt(
      $ch,
      CURLOPT_HTTPHEADER,
      [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($posted)
      ]
    );
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);

    if (false === ($response = curl_exec($ch))) {
      $err = curl_error($ch);
      $this->logger->warn('调用API[' . $url . ']失败，原因：' . $err);
      curl_close($ch);
      return [false, $err];
    }
    if (empty($response)) {
      $this->logger->warn('调用API[' . $url . ']返回结果为空');
      $info = curl_getinfo($ch);
      curl_close($ch);
      return [false, 'response is empty'];
    } else {
      curl_close($ch);
    }

    $rsp = json_decode($response);
    if (!is_object($rsp)) {
      $this->logger->warn('调用API[' . $url . ']返回结果不是对象');
      return [false, $response];
    }

    if ($rsp->code !== 0) {
      $this->logger->warn('调用API[' . $url . "]返回结果\n" . $response);
      return [false, $rsp->msg];
    }

    $result = $rsp->result;

    return [true, $result];
  }
  /**
   * 查看阅读情况
   * 
   * 如果指定了分组活动，以分组活动中的用户作为全部用户，不包含观察者角色
   * 如果指定了通讯录，以通讯录中的用户作为全部用户
   * 
   */
  public function watch_action($id)
  {
    $modelArt = $this->model('matter\article');
    $oArt = $modelArt->byId($id);
    if (false === $oArt || $oArt->state !== '1') {
      return new \ObjectNotFoundError();
    }

    $entryRule = $oArt->entryRule;


    if ($this->getDeepValue($entryRule, 'scope.group') === 'Y') {
      /*指定了分组活动作为活动用户*/
      if (isset($entryRule->group) && is_object($entryRule->group)) {
        $modelGrp = $this->model('matter\group');
        $oGrpApp = $modelGrp->byId($entryRule->group->id, ['fields' => 'id,title']);
        if ($oGrpApp === false)
          return new \ObjectNotFoundError('指定的分组活动不存在');

        $modelGrpRec = $this->model('matter\group\record');
        $oByGrpAppResult = $modelGrpRec->byApp($oGrpApp);
        $records = $oByGrpAppResult->records;
        $uids = array_map(function ($record) {
          return $record->userid;
        }, $records);
        $aResult = $this->_getAccessLog($oArt->id, $uids);
        if (false === $aResult[0]) return new \ResponseError($aResult[1]);

        $watchData = $aResult[1];
        $readers = array_map(function ($record) use ($watchData) {
          $reader = new \stdClass;
          $reader->userid = $record->userid;
          $reader->name = $record->nickname;
          $reader->team_id = $record->team_id;
          $reader->is_leader = $record->is_leader;
          if (isset($watchData->{$record->userid})) {
            $reader->read = $watchData->{$record->userid}->read;
          }
          return $reader;
        }, $records);

        $oResult = new \stdClass;
        $oResult->userapp = $oGrpApp;
        $oResult->readers = $readers;
        $oResult->total = $oByGrpAppResult->total;

        return new \ResponseData($oResult);
      }
    } else if ($this->getDeepValue($entryRule, 'scope.member') === 'Y') {
      /*指定了通讯录作为活动用户*/
      if (isset($entryRule->member) && is_object($entryRule->member)) {
        $mschemaId = array_keys((array)$entryRule->member)[0];
        $modelMs = $this->model('site\user\memberschema');
        $oMschema = $modelMs->byId($mschemaId, ['fields' => 'id,title', 'cascaded' => 'N']);
        if ($oMschema === false)
          return new \ObjectNotFoundError('指定的通讯录不存在');

        $q = [
          'userid,name',
          'xxt_site_member',
          ['schema_id' => $mschemaId, 'forbidden' => 'N']
        ];
        $q2['o'] = 'create_at desc';
        $members = $modelMs->query_objs_ss($q, $q2);
        // 没有要监控的用户，直接返回
        if (count($members) === 0) {
          return new \ResponseData((object)['readers' => [], 'total' => 0]);
        }

        $uids = array_map(function ($member) {
          return $member->userid;
        }, $members);
        $aResult = $this->_getAccessLog($oArt->id, $uids);
        if (false === $aResult[0]) return new \ResponseError($aResult[1]);

        $watchData = $aResult[1];
        array_map(function ($member) use ($watchData) {
          if (isset($watchData->{$member->userid})) {
            $member->read = $watchData->{$member->userid}->read;
          }
        }, $members);

        $oResult = new \stdClass;
        if (isset($oMschema->attrs)) unset($oMschema->attrs);
        $oResult->userapp = $oMschema;
        $oResult->readers = $members;

        $q[0] = 'count(*)';
        $total = (int) $modelMs->query_val_ss($q);
        $oResult->total = $total;

        return new \ResponseData($oResult);
      }
    }

    return new \ResponseData([]);
  }
}
