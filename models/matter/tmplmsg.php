<?php
namespace matter;
/**
 * 模板消息定义
 */
class tmplmsg_model extends \TMS_MODEL {
    /**
     * 获得模板消息定义
     */
    public function byId($id, $options = array()) {
        $fields = isset($options['fields']) ? $options['fields'] : '*';
        $cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'N';
        /**/
        $q = [
            $fields,
            'xxt_tmplmsg',
            ['id' => $id],
        ];
        $tmpl = $this->query_obj_ss($q);
        /*参数*/
        if ($tmpl && $cascaded === 'Y') {
            $q = [
                "id,pname,plabel",
                'xxt_tmplmsg_param',
                ['tmplmsg_id' => $id],
            ];
            $tmpl->params = $this->query_objs_ss($q);
        }

        return $tmpl;
    }
    /**
     * 获得一个团队下的模板消息定义
     */
    public function &bySite($siteId, $options = []) {
        $cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';

        $q = [
            "t.*",
            'xxt_tmplmsg t',
            ["t.siteid" => $siteId, "t.state" => 1],
        ];
        $q2['o'] = 't.create_at desc';
        $tmplmsgs = $this->query_objs_ss($q, $q2);
        if (count($tmplmsgs) === 0 && $siteId !== 'platform') {
            // 如果当前站点内没有定义模板消息，获取平台的模板消息
            $tmplmsgs = $this->bySite('platform', ['cascaded' => $cascaded]);
        } else if ($cascaded === 'Y' && count($tmplmsgs)) {
            $q = [
                "id,pname,plabel",
                'xxt_tmplmsg_param',
            ];
            $q3 = [
                'nickname',
                'account',
            ];
            foreach ($tmplmsgs as &$tmpl) {
                $q[2] = "tmplmsg_id=$tmpl->id";
                $tmpl->params = $this->query_objs_ss($q);

                $q3[2] = "uid='$tmpl->creater'";
                $tmpl->author = $this->query_obj_ss($q3);
            }
        }

        return $tmplmsgs;
    }
}