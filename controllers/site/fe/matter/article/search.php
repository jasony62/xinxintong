<?php

namespace site\fe\matter\article;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 *[查看更多]搜索更多
 */
class search extends \site\fe\matter\base
{
  /**
   * 搜索页面
   */
  public function index_action()
  {
    \TPL::output('/site/fe/matter/article/list');
    exit;
  }
  /**
   * 返回所有的搜索结果
   */
  public function list_action($site, $keyword = '', $mission = 0, $channel = 0, $page = 1, $size = 12)
  {
    $matters = $this->model('matter\article')->search_all($site, $keyword, $page, $size, $mission, $channel);
    return new \ResponseData($matters);
  }
}
