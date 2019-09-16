<?php
namespace matter\signin;
/**
 *
 */
class coin_model extends \TMS_MODEL {
    /**
     * 返回签到活动对应的行为分规则
     *
     * @param
     */
    public function &rulesByMatter($act, $oApp) {
        $q = ['*', 'xxt_coin_rule', "matter_type='signin' and act='$act' and "];

        $w = "(";
        $w .= "matter_filter='*'";
        $w .= "or matter_filter='ID:{$oApp->id}'";
        $w .= ")";

        $q[2] .= $w;

        $rules = $this->query_objs_ss($q);

        return $rules;
    }
    /**
     * 返回用于记录行为分的活动创建人
     *
     * @param object $oApp
     */
    public function getCreator($oApp) {
        return false;
    }
}