<?php
namespace matter\enroll;
/**
 * 参加记录活动的用户分组数据
 */
class group_model extends \TMS_MODEL {
    /**
     * 获得指定活动下的指定用户
     */
    public function byId($oApp, $groupId, $aOptions = []) {
        $fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
        $q = [
            $fields,
            'xxt_enroll_group',
            ['aid' => $oApp->id, 'group_id' => $groupId],
        ];
        $q[2]['rid'] = isset($aOptions['rid']) ? $aOptions['rid'] : 'ALL';

        $oGroup = $this->query_obj_ss($q);

        return $oGroup;
    }
    /**
     * 修改用户数据
     */
    public function modify($oAppUser, $oUpdatedData) {
        if (empty($oAppUser->siteid) || empty($oAppUser->aid) || empty($oAppUser->group_id) || empty($oAppUser->rid)) {
            return false;
        }

        $oBeforeGroup = $this->query_obj_ss(['*', 'xxt_enroll_group', ['aid' => $oAppUser->aid, 'group_id' => $oAppUser->group_id, 'rid' => $oAppUser->rid]]);
        if (false === $oBeforeGroup) {
            $oBeforeGroup = new \stdClass;
            $oBeforeGroup->siteid = $oAppUser->siteid;
            $oBeforeGroup->aid = $oAppUser->aid;
            $oBeforeGroup->group_id = $oAppUser->group_id;
            $oBeforeGroup->rid = $oAppUser->rid;
            $oBeforeGroup->id = $this->insert('xxt_enroll_group', $oBeforeGroup, true);
        }

        $aDbData = [];
        foreach ($oUpdatedData as $field => $value) {
            switch ($field) {
            case 'entry_num':
            case 'total_elapse':
            case 'enroll_num':
            case 'revise_num':
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
            case 'vote_schema_num':
            case 'vote_cowork_num':
                $aDbData[$field] = $value + (isset($oBeforeGroup->{$field}) ? (int) $oBeforeGroup->{$field} : 0);
                break;
            case 'score':
                $aDbData[$field] = $value + (isset($oBeforeGroup->{$field}) ? (float) $oBeforeGroup->{$field} : 0);
                break;
            case 'state':
                $aDbData[$field] = $value;
                break;
            }
        }

        if (empty($aDbData)) {
            return false;
        }

        /* 更新分组行为分 */

        $rst = $this->update('xxt_enroll_group', $aDbData, ['aid' => $oAppUser->aid, 'group_id' => $oAppUser->group_id, 'rid' => $oAppUser->rid]);

        return $rst;
    }
    /**
     * 活动用户分组获得奖励行为分
     */
    public function awardCoin($oApp, $groupId, $rid, $coinEvent, $coinRules = null) {
        if (empty($coinRules)) {
            $modelCoinRule = $this->model('matter\enroll\coin');
            $coinRules = $modelCoinRule->rulesByMatter($coinEvent, $oApp);
        }
        if (empty($coinRules)) {
            return [false];
        }

        $deltaCoin = 0; // 增加的行为分
        foreach ($coinRules as $rule) {
            $deltaCoin += (int) $rule->actor_delta;
        }
        if ($deltaCoin === 0) {
            return [false];
        }

        /* 参与活动的用户 */
        //$oEnlGrpUsr = $this->byId($oApp, $groupId, ['fields' => 'id,group_id,group_total_coin', 'rid' => $rid]);
        //if (false === $oEnlGrpUsr) {
        //    return [false, $deltaCoin];
        //}

        /* 奖励行为分 */
        //$modelCoinLog = $this->model('site\coin\log')->setOnlyWriteDbConn(true);
        //$oResult = $modelCoinLog->award($oApp, $oEnlGrpUsr, $coinEvent, $coinRules);

        return [true, $deltaCoin];
    }
    /**
     * 是否都完成了提交任务
     */
    public function isAllSubmit($oApp, $rid, $groupId) {
        if (!isset($oApp->entryRule)) {
            $oApp2 = $this->model('matter\enroll')->byId($oApp->id, ['fields' => 'entry_rule']);
            if ($oApp2) {
                $oApp->entryRule = $oApp2->entryRule;
            }
        }
        if (empty($oApp->entryRule->group->id)) {
            return [false];
        }

        $oEntryRule = $oApp->entryRule;

        $modelGrpRec = $this->model('matter\group\record');

        $users = $modelGrpRec->byTeam($groupId, ['fields' => 'userid,is_leader', 'is_leader' => ['N', 'Y']]);
        if (empty($users)) {
            return [false];
        }
        $modelUsr = $this->model('matter\enroll\user');
        $submiters = [];
        array_walk($users, function ($user, $index) use ($oApp, $modelUsr, &$submiters) {
            $oEnlUsr = $modelUsr->byId($oApp, $user->userid, ['fields' => 'enroll_num']);
            if ($oEnlUsr && $oEnlUsr->enroll_num > 0) {
                $submiters[] = $user->userid;
            }
        });

        if (count($users) === count($submiters)) {
            return [true];
        }

        return [false];
    }
}