<?php

namespace pl\be\sns\wx;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 微信公众号
 */
class main extends \pl\be\base
{
  /**
   *
   */
  public function index_action()
  {
    \TPL::output('/pl/be/sns/wx/main');
    exit;
  }
  /**
   * 获得公众号配置信息
   */
  public function get_action()
  {
    $uid = \TMS_CLIENT::get_client_uid();

    $modelWx = $this->model('sns\wx');
    $wx = $modelWx->bySite('platform');
    if ($wx === false) {
      /* 不存在就创建一个 */
      $data['creator'] = $uid;
      $data['create_at'] = time();
      $modelWx->create('platform', $data);
      $wx = $modelWx->bySite('platform');
    }

    return new \ResponseData($wx);
  }
  /**
   * 更新账号配置信息
   */
  public function update_action()
  {
    $nv = $this->getPostJson();

    /* 如果修改了token，需要重新重新进行连接验证 */
    isset($nv->token) && $nv->joined = 'N';

    $rst = $this->model()->update(
      'xxt_site_wx',
      $nv,
      "siteid='platform'"
    );

    return new \ResponseData($rst);
  }
  /**
   *
   */
  public function checkJoin_action()
  {
    $wx = $this->model('sns\wx')->bySite('platform');

    return new \ResponseData($wx->joined);
  }
}
