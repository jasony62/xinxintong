<?php

namespace pl;

/**
 * 站点访问控制器基类
 */
class main extends \TMS_CONTROLLER
{
  /**
   *
   */
  public function get_access_rule()
  {
    $ruleAction = [
      'rule_type' => 'black',
    ];

    return $ruleAction;
  }
  /**
   * 返回系统配置信息
   */
  public function config_action()
  {
    $config = [];

    if (defined('TMS_FINDER_ADDRESS')) $config['TMS_FINDER_ADDRESS'] = TMS_FINDER_ADDRESS;

    return new \ResponseData($config);
  }
}
