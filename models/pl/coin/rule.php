<?php
namespace pl\coin;
/**
 * 站点内行为分规则
 */
class rule_model extends \TMS_MODEL {
    /**
     *
     */
    public function byAct($act) {
        $q = array(
            '*',
            'xxt_coin_rule',
            "act='$act'",
        );

        $rules = $this->query_objs_ss($q);

        return $rules;
    }
    /**
     * 根据素材过滤器获得
     */
    public function byMatterFilter($filter) {
        $q = [
            '*',
            'xxt_coin_rule',
            "matter_filter='$filter'",
        ];

        $rules = $this->query_objs_ss($q);

        return $rules;
    }
}