<?php
namespace matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户分组
 */
class team_model extends \matter\base_model {
    /**
     *
     */
    protected function id() {
        return 'team_id';
    }
    /**
     *
     */
    protected function table() {
        return 'xxt_group_team';
    }
    /**
     * 创建轮次
     */
    public function &create($app, $prototype = array()) {
        $targets = isset($prototype['targets']) ? $this->toJson($prototype['targets']) : '[]';
        $aNewTeam = [
            'aid' => $app,
            'team_id' => uniqid(),
            'create_at' => time(),
            'title' => isset($prototype['title']) ? $prototype['title'] : '新分组',
            'times' => isset($prototype['times']) ? $prototype['times'] : 0,
            'targets' => $targets,
        ];
        $this->insert('xxt_group_team', $aNewTeam, false);

        $oNewTeam = (object) $aNewTeam;

        return $oNewTeam;
    }
    /**
     * 获得分组列表
     *
     * @param string $appId 活动id
     * @param array $aOptions
     */
    public function &byApp($appId, $aOptions = []) {
        $fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
        $teamType = isset($aOptions['team_type']) ? $aOptions['team_type'] : 'T';
        $cascade = isset($aOptions['cascade']) ? $aOptions['cascade'] : '';
        $cascade = explode(',', $cascade);

        $q = [
            $fields,
            'xxt_group_team',
            ['aid' => $appId],
        ];
        if (!empty($teamType)) {
            $q[2]['team_type'] = $teamType;
        }
        $teams = $this->query_objs_ss($q);

        /* 获得指定的级联数据 */
        if (count($teams) && count($cascade)) {
            $modelGrpRec = $this->model('matter\group\record');
            if (in_array('playerCount', $cascade)) {
                $handlers[] = function ($oTeam) use ($modelGrpRec) {
                    $oTeam->playerCount = $modelGrpRec->countByTeam($oTeam->team_id);
                };
            }
            if (in_array('onlookerCount', $cascade)) {
                $handlers[] = function ($oTeam) use ($modelGrpRec) {
                    $oTeam->onlookerCount = $modelGrpRec->countByTeam($oTeam->team_id, ['is_leader' => 'O']);
                };
            }
            if (in_array('leaveCount', $cascade)) {
                // 因为存在请假的情况，所以需要指定查找的开始和结束时间
                $startAt = $this->getDeepValue($aOptions, 'start_at', 0);
                $endAt = $this->getDeepValue($aOptions, 'end_at', 0);
                if ($startAt > 0 && $endAt > 0) {
                    $modelGrpLev = $this->model('matter\group\leave');
                    $handlers[] = function ($oTeam) use ($modelGrpRec, $modelGrpLev, $startAt, $endAt) {
                        $records = $modelGrpRec->byTeam($oTeam->team_id, ['fields' => 'userid']);
                        $leaveCount = 0;
                        foreach ($records as $oRec) {
                            if (!empty($oRec->leaves) && $modelGrpLev->isOnLeave($oRec->leaves, $startAt, $endAt)) {
                                $leaveCount++;
                            }
                        }
                        $oTeam->leaveCount = $leaveCount;
                    };
                }
            }
            if (!empty($handlers)) {
                foreach ($teams as $oTeam) {
                    foreach ($handlers as $handler) {
                        $handler($oTeam);
                    }
                }
            }
        }

        return $teams;
    }
    /**
     * 清除轮次结果
     *
     * @param string $appId
     */
    public function clean($appId) {
        $rst = $this->update(
            'xxt_group_record',
            [
                'team_id' => 0,
                'team_title' => '',
            ],
            ['aid' => $appId]
        );

        return $rst;
    }
}