<?php
namespace app\merchant;

require_once dirname(dirname(dirname(__FILE__))).'/xxt_base.php';
/**
 * 讨论组 
 */
class catelog extends \xxt_base {
    /**
     *
     */
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 获得属性的可选值
     * 
     * $propId 属性ID
     * $assoPropVid 关联的属性ID
     */
    public function propValueGet_action($propId, $assoPropVid=null)
    {
        $pvs = $this->model('app\merchant\catelog')->valuesById($propId, $assoPropVid);
        
        return new \ResponseData($pvs);
    }
}
