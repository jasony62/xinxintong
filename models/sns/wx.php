<?php
namespace sns;
/**
 * 微信公众号
 */
class wx_model extends \TMS_MODEL {
    /**
     * 站点绑定的公众号
     */
    public function &bySite($siteId, $options = []) {
        $fields = isset($options['fields']) ? $options['fields'] : '*';
        $q = array(
            $fields,
            'xxt_site_wx',
            ['siteid' => $siteId],
        );
        $wx = $this->query_obj_ss($q);

        return $wx;
    }
    /**
     * 创建绑定的公众号配置信息
     */
    public function &create($siteId, $data = []) {
        $data['siteid'] = $this->escape($siteId);

        $this->insert('xxt_site_wx', $data, false);

        $wx = $this->bySite($siteId);

        return $wx;
    }
}