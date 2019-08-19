<?php
namespace matter\site;
/**
 *
 */
class coin_model extends \TMS_MODEL {
    /**
     * 返回对应的行为分规则
     *
     * @param object $siteConfig
     */
    public function &rulesByMatter($act, $siteConfig) {
        $q = ['*', 'xxt_coin_rule', "matter_type='site' and act='$act' and matter_filter='ID:{$siteConfig->id}'"];
        $rules = $this->query_objs_ss($q);

        return $rules;
    }
    /**
     * 返回用于记录行为分的文章创建人
     *
     * @param object $siteConfig
     */
    public function getCreator($siteConfig) {

        return false;
    }
}