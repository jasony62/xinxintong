<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 记录活动行为分管理控制器
 */
class coin extends main_base {
    /**
     *
     */
    public function rules_action() {
        $oApp = $this->app;
        $filter = 'ID:' . $oApp->id;
        $modelRule = $this->model('site\coin\rule');
        $rules = $modelRule->byMatterFilter($filter, ['fields' => 'id,act,actor_delta,actor_overlap,matter_type']);

        return new \ResponseData($rules);
    }
    /**
     *
     */
    public function saveRules_action() {
        $oApp = $this->app;

        $newRules = [];
        $rules = $this->getPostJson();

        $modelEnl = $this->model('matter\enroll');
        foreach ($rules as $rule) {
            if (empty($rule->id)) {
                if ($rule->actor_delta != 0) {
                    $rule->siteid = $oApp->siteid;
                    $rule->matter_type = 'enroll';
                    $rule->matter_filter = 'ID:' . $oApp->id;
                    $id = $modelEnl->insert('xxt_coin_rule', $rule, true);
                    $newRules[$rule->act] = $id;
                }
            } else {
                $modelEnl->update(
                    'xxt_coin_rule',
                    [
                        'actor_delta' => $rule->actor_delta,
                        'actor_overlap' => $rule->actor_overlap,
                    ],
                    ['id' => $rule->id]
                );
            }
        }

        return new \ResponseData($newRules);
    }
    /**
     *
     */
    public function logs_action($page = 1, $size = 30) {
        $q = [
            'cl.act,cl.occur_at,cl.userid,cl.nickname,cl.delta,cl.total,e.user_total_coin',
            'xxt_coin_log cl,xxt_enroll_user e',
            "cl.matter_type='enroll' and cl.matter_id='{$this->app->id}' and e.aid = cl.matter_id and e.userid = cl.userid and e.rid = 'ALL'",
        ];
        /**
         * 分页数据
         */
        $q2 = [
            'o' => 'cl.occur_at desc,cl.id desc',
            'r' => [
                'o' => (($page - 1) * $size),
                'l' => $size,
            ],
        ];

        $model = $this->model();
        $oResult = new \stdClass;
        $oResult->logs = $model->query_objs_ss($q, $q2);

        $q[0] = 'count(*)';
        $oResult->total = $model->query_val_ss($q);

        return new \ResponseData($oResult);
    }
}