<?php

namespace api\site\member;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 记录活动控制器
 */
class Main extends \api\base
{
  /**
   *
   */
  public function list_action($schema, $page = 1, $size = 30, $kw = '', $by = '')
  {
    $modelMs = $this->model('site\user\memberschema');
    $oMschema = $modelMs->byId($schema, ['fields' => 'siteid,id,title,attr_mobile,attr_email,attr_name,ext_attrs,auto_verified,require_invite']);
    if ($oMschema === false) {
      return new \ObjectNotFoundError();
    }

    $w = "m.schema_id=$schema and m.forbidden='N'";
    if (!empty($kw) && !empty($by)) {
      $w .= " and m.$by like '%{$kw}%'";
    }
    if (!empty($dept)) {
      $w .= " and m.depts like '%\"$dept\"%'";
    }
    if (!empty($tag)) {
      $w .= " and concat(',',m.tags,',') like '%,$tag,%'";
    }
    $q = [
      'm.*',
      'xxt_site_member m',
      $w,
    ];
    $q2['o'] = 'm.create_at desc';
    $q2['r']['o'] = ($page - 1) * $size;
    $q2['r']['l'] = $size;
    $members = $modelMs->query_objs_ss($q, $q2);

    $oResult = new \stdClass;
    if (count($members)) {
      $modelAcnt = $this->model('site\user\account');
      $modelWxfan = $this->model('sns\wx\fan');
      foreach ($members as $oMember) {
        if (property_exists($oMember, 'extattr')) {
          $oMember->extattr = empty($oMember->extattr) ? new \stdClass : json_decode($oMember->extattr);
        }
        if (!empty($oMember->userid)) {
          $oAccount = $modelAcnt->byId($oMember->userid, ['fields' => 'wx_openid']);
          if (!empty($oAccount->wx_openid)) {
            $oWxfan = $modelWxfan->byOpenid($oMschema->siteid, $oAccount->wx_openid, 'nickname,headimgurl', 'Y');
            if ($oWxfan) {
              $oMember->wxfan = $oWxfan;
            }
          }
        }
      }
    }

    $oResult->members = $members;

    $q[0] = 'count(*)';
    $total = (int) $modelMs->query_val_ss($q);
    $oResult->total = $total;

    return new \ResponseData($oResult);
  }
}
