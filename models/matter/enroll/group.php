<?php
namespace matter\enroll;
/**
 * 参加记录活动的用户分组数据
 */
class group_model extends \TMS_MODEL {
    /**
     * 添加一个活动用户
     */
    public function addUser($oApp, $oAppUser) {
        if (empty($oUser->group_id)) {
            return false;
        }

    }
    /**
     * 修改用户数据
     */
    public function modify($oBeforeData, $oUpdatedData) {
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
                $aDbData[$field] = $value + (int) $oBeforeData->{$field};
                break;
            case 'score':
            case 'state':
            case 'group_id':
                $aDbData[$field] = $value;
                break;
            }
        }

        $rst = $this->update('xxt_enroll_group', $aDbData, ['id' => $oBeforeData->id]);

        return $rst;
    }
}