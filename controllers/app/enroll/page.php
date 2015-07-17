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
    public function schemaGet_action($mpid, $id, $pageid=null, $size=null, $byPage='Y')
    {
        $modelPage = $this->model('app\enroll\page');
        
        $pages = $modelPage->byEnrollId($id);
        
        if ($byPage === 'Y') {
            /**
             * 按页返回
             */
            $defs = array();
            foreach ($pages as $page) {
                $page->type === 'I' && $defs[$page->name] = $modelPage->schemaByHtml($page->html, $size);
            }
        } else {
            /**
             * 按活动返回
             */
            $defs = array();
            foreach ($pages as $page) {
                if ($page->type === 'I') {
                    $pageDefs = $modelPage->schemaByHtml($page->html);
                    $defs = array_merge($defs, $pageDefs);  
                } 
            }
            if ($size !== null && $size > 0 && $size < count($defs)) {
                /**
                 * 随机获得指定数量的登记项
                 */
                $randomDefs = array();
                $upper = count($defs) - 1;
                for ($i = 0; $i < $size; $i++) {
                    $random = mt_rand(0, $upper);
                    $randomDefs[] = $defs[$random];
                    array_splice($defs, $random, 1);
                    $upper--;
                }
                $defs = $randomDefs;
            }
        }
        
        return new \ResponseData($defs);
    }
}
