<?php
namespace app\enroll;

include_once dirname(dirname(dirname(__FILE__))).'/member_base.php';
/**
 * 登记活动数据定义
 */
class page extends \member_base {

    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 获得登记项定义
     *
     * $mpid
     * $id enroll's id
     * $pageid
     * $size
     */
    public function schemaGet_action($mpid, $id, $pageid=null, $size=null)
    {
        $modelPage = $this->model('app\enroll\page');
        
        $pages = $modelPage->byEnrollId($id);
        
        $defs = array();
        foreach ($pages as $page) {
            $page->type === 'I' && $defs[$page->name] = $modelPage->schemaByHtml($page->html, $size);
        }
        
        return new \ResponseData($defs);
    }
}
