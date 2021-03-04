<?php
namespace site;
/**
 * 发布在平台主页上的团队
 */
class home_model extends \TMS_MODEL {
    /**
     *
     */
    public function byId($id, $options = []) {
        $fields = isset($options['fields']) ? $options['fields'] : '*';

        $q = [
            $fields,
            'xxt_home_site',
            ["id" => $id],
        ];

        $item = $this->query_obj_ss($q);

        return $item;
    }
    /**
     *
     */
    public function &bySite($siteId, $options = []) {
        $fields = isset($options['fields']) ? $options['fields'] : '*';

        $q = [
            $fields,
            'xxt_home_site',
            ["siteid" => $siteId],
        ];

        $item = $this->query_obj_ss($q);

        return $item;
    }
    /**
     *
     */
    public function &find($options = []) {
        $fields = isset($options['fields']) ? $options['fields'] : '*';
        if (strpos($fields, 'h.') === false && strpos($fields, 's.') === false) {
            $fields = str_replace(',', ',h.', $fields);
            $fields = 'h.' . $fields;
        }
        $page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 8];

        $q = [
            $fields,
            'xxt_home_site h,xxt_site s',
            "h.siteid = s.id and s.state = 1",
        ];

        $q2 = [
            'r' => ['o' => ($page['at'] - 1) * $page['size'], 'l' => $page['size']],
            'o' => 'h.put_at desc',
        ];

        $result = new \stdClass;
        $result->sites = $this->query_objs_ss($q, $q2);
        if (count($result->sites)) {
            $q[0] = 'count(*)';
            $result->total = (int) $this->query_val_ss($q);
        } else {
            $result->total = 0;
        }

        return $result;
    }
    /**
     *
     * @param string $siteId 来源于哪个站点
     * @param object $matter 共享的素材
     */
    public function putSite(&$site, &$account, $options = array()) {
        if ($this->bySite($site->id)) {
            // 更新素材信息
            $current = time();

            $item = [
                'title' => $site->name,
                'pic' => $site->heading_pic,
            ];
            $this->update(
                'xxt_home_site',
                $item,
                ["siteid" => $site->id]
            );
        } else {
            // 新申请素材信息
            $current = time();

            $item = [
                'creater' => $account->id,
                'creater_name' => $account->name,
                'put_at' => $current,
                'siteid' => $site->id,
                'title' => $site->name,
                'pic' => $site->heading_pic,
            ];

            $id = $this->insert('xxt_home_site', $item, true);
            $item = $this->byId($id);
        }

        return $item;
    }
    /**
     * 推送到主页
     */
    public function pushHome($applicationId) {
        $rst = $this->update(
            'xxt_home_site',
            ['approved' => 'Y'],
            ["id" => $applicationId]
        );

        return $rst;
    }
    /**
     * 从主页撤销
     */
    public function pullHome($applicationId) {
        $rst = $this->update(
            'xxt_home_site',
            ['approved' => 'N'],
            ["id" => $applicationId]
        );

        return $rst;
    }
    /**
     * 已经批准在主页上的素材
     */
    public function &atHome($options = []) {
        $fields = isset($options['fields']) ? $options['fields'] : '*';
        if (strpos($fields, 'h.') === false && strpos($fields, 's.') === false) {
            $fields = str_replace(',', ',h.', $fields);
            $fields = 'h.' . $fields;
        }
        $page = isset($options['page']) ? $options['page'] : ['at' => 1, 'size' => 8];

        $q = [
            $fields,
            'xxt_home_site h, xxt_site s',
            "h.approved = 'Y' and h.siteid = s.id and s.state = 1",
        ];

        $q2 = [
            'r' => ['o' => ($page['at'] - 1) * $page['size'], 'l' => $page['size']],
            'o' => 'h.score desc,h.put_at desc',
        ];

        $result = new \stdClass;
        $result->sites = $this->query_objs_ss($q, $q2);
        if (count($result->sites)) {
            $q[0] = 'count(*)';
            $result->total = (int) $this->query_val_ss($q);
        } else {
            $result->total = 0;
        }

        return $result;
    }
}