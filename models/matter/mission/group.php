<?php
namespace matter\mission;
/**
 * 分组汇总数据
 */
class group_model extends \TMS_MODEL {
    /**
     * 获得指定活动下的指定用户
     */
    public function byId($oMis, $groupId, $aOptions = []) {
        $fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
        $q = [
            $fields,
            'xxt_mission_group',
            ['mission_id' => $oMis->id, 'group_id' => $groupId],
        ];

        $oGroup = $this->query_obj_ss($q);

        return $oGroup;
    }
    /**
     * 修改用户组汇总数据
     */
    public function modify($oMisGrp, $oUpdatedData) {
        if (empty($oMisGrp->siteid) || empty($oMisGrp->mission_id) || empty($oMisGrp->group_id)) {
            return false;
        }

        $oBeforeGroup = $this->query_obj_ss(['*', 'xxt_mission_group', ['mission_id' => $oMisGrp->mission_id, 'group_id' => $oMisGrp->group_id]]);
        if (false === $oBeforeGroup) {
            $oBeforeGroup = new \stdClass;
            $oBeforeGroup->siteid = $oMisGrp->siteid;
            $oBeforeGroup->mission_id = $oMisGrp->mission_id;
            $oBeforeGroup->group_id = $oMisGrp->group_id;
            $oBeforeGroup->id = $this->insert('xxt_mission_group', $oBeforeGroup, true);
        }

        $aDbData = [];
        foreach ($oUpdatedData as $field => $value) {
            switch ($field) {
            case 'entry_num':
            case 'total_elapse':
            case 'enroll_num':
            case 'cowork_num':
            case 'do_cowork_num':
            case 'do_like_num':
            case 'do_dislike_num':
            case 'do_like_cowork_num':
            case 'do_dislike_cowork_num':
            case 'do_like_remark_num':
            case 'do_dislike_remark_num':
            case 'like_num':
            case 'dislike_num':
            case 'like_cowork_num':
            case 'dislike_cowork_num':
            case 'like_remark_num':
            case 'dislike_remark_num':
            case 'do_remark_num':
            case 'remark_num':
            case 'remark_cowork_num':
            case 'agree_num':
            case 'agree_cowork_num':
            case 'agree_remark_num':
            case 'user_total_coin':
            case 'group_total_coin':
            case 'topic_num':
            case 'do_repos_read_num':
            case 'do_topic_read_num':
            case 'topic_read_num':
            case 'do_cowork_read_num':
            case 'cowork_read_num':
            case 'do_rank_read_num':
            case 'do_cowork_read_elapse':
            case 'cowork_read_elapse':
            case 'do_topic_read_elapse':
            case 'topic_read_elapse':
            case 'do_repos_read_elapse':
            case 'do_rank_read_elapse':
                $aDbData[$field] = (isset($oBeforeGroup->{$field}) ? (int) $oBeforeGroup->{$field} : 0) + $value;
                break;
            case 'score':
                /* 更新时传入的数据分可能只是用户在某个活动中的数据分，需要重新计算用户在整个项目中的数据分 */
                $aDbData['score'] = $this->_scoreByGroup($oBeforeGroup);
                break;
            }
        }
        if (!empty($aDbData)) {
            $rst = $this->update('xxt_mission_group', $aDbData, ['id' => $oBeforeGroup->id]);
        }

        return true;
    }
    /**
     * 用户在整个项目中的数据分
     */
    private function _scoreByGroup($oMisGrp) {
        $q = [
            'id',
            'xxt_enroll',
            ['mission_id' => $oMisGrp->mission_id, 'state' => 1],
        ];
        $appIds = $this->query_vals_ss($q);
        if (count($appIds)) {
            $q = [
                'sum(score)',
                'xxt_enroll_group',
                ['group_id' => $oMisGrp->userid, 'aid' => $appIds, 'rid' => 'ALL'],
            ];
            $sum = (float) $this->query_val_ss($q);
        } else {
            $sum = 0;
        }

        return $sum;
    }
}