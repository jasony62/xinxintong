<?php
namespace matter\template;
/**
 *
 */
class coin_model extends \TMS_MODEL {
    /**
     * 返回文章对应的行为分规则
     *
     * @param object $article
     */
    public function &rulesByMatter($act, $template) {
        $q = [
            '*',
            'xxt_coin_rule',
            "matter_type='template' and act='$act'",
        ];

        $rules = $this->query_objs_ss($q);

        return $rules;
    }
    /**
     * 返回用于记录行为分的文章创建人
     *
     * @param object $article
     */
    public function getCreator($template) {
        $creator = false;

        return $creator;
    }
}