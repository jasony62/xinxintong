<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 记录活动排行榜
 */
class rank extends base {
    /**
     * 根据用户的行为数据进行排行
     */
    private function _userByBehavior($oApp, $oCriteria, $page = 1, $size = 100) {
        $modelUsr = $this->model('matter\enroll\user');

        $q = [
            'u.userid,u.nickname,a.headimgurl',
            'xxt_enroll_user u left join xxt_site_account a on u.userid = a.uid and u.siteid = a.siteid',
            "u.aid='{$oApp->id}' and u.state=1",
        ];

        if (!empty($oCriteria->round) && is_string($oCriteria->round)) {
            $oCriteria->round = explode(',', $oCriteria->round);
        }
        if (empty($oCriteria->round) || in_array('ALL', $oCriteria->round)) {
            $q[2] .= " and u.rid = 'ALL'";
        } else {
            $whereByRound = ' and rid in("';
            $whereByRound .= implode('","', $oCriteria->round);
            $whereByRound .= '")';
            $q[2] .= $whereByRound;
        }

        switch ($oCriteria->orderby) {
        case 'enroll':
            $q[0] .= ',sum(u.enroll_num) enroll_num';
            $q[2] .= ' and u.enroll_num>0';
            $q2 = ['o' => 'enroll_num desc'];
            break;
        case 'remark':
            $q[0] .= ',sum(u.remark_num) remark_num';
            $q[2] .= ' and u.remark_num>0';
            $q2 = ['o' => 'remark_num desc'];
            break;
        case 'like':
            $q[0] .= ',sum(u.like_num) like_num';
            $q[2] .= ' and u.like_num>0';
            $q2 = ['o' => 'like_num desc'];
            break;
        case 'remark_other':
            $q[0] .= ',sum(u.do_remark_num) do_remark_num';
            $q[2] .= ' and u.do_remark_num>0';
            $q2 = ['o' => 'do_remark_num desc'];
            break;
        case 'do_like':
            $q[0] .= ',sum(u.do_like_num) do_like_num';
            $q[2] .= ' and u.do_like_num>0';
            $q2 = ['o' => 'do_like_num desc'];
            break;
        case 'total_coin':
            $q[0] .= ',sum(u.user_total_coin) user_total_coin';
            $q[2] .= ' and u.user_total_coin>0';
            $q2 = ['o' => 'user_total_coin desc'];
            break;
        case 'score':
            $q[0] .= ',sum(u.score) score';
            if (empty($oApp->rankConfig->scoreIncludeZero)) {
                $q[2] .= ' and u.score>0';
            }
            $q2 = ['o' => 'score desc'];
            break;
        case 'vote_schema':
            $q[0] .= ',sum(u.vote_schema_num) vote_schema_num';
            $q[2] .= ' and u.vote_schema_num>0';
            $q2 = ['o' => 'vote_schema_num desc'];
            break;
        case 'vote_cowork':
            $q[0] .= ',sum(u.vote_cowork_num) vote_cowork_num';
            $q[2] .= ' and u.vote_cowork_num>0';
            $q2 = ['o' => 'vote_cowork_num desc'];
            break;
        }
        $q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
        $q2['g'] = ['userid'];

        $oResult = new \stdClass;
        $users = $modelUsr->query_objs_ss($q, $q2);
        if (count($users) && !empty($oApp->entryRule->group->id)) {
            $q = [
                'userid,team_id,team_title',
                'xxt_group_record',
                ['aid' => $oApp->entryRule->group->id],
            ];
            $userGroups = $modelUsr->query_objs_ss($q);
            if (count($userGroups)) {
                $userGroups2 = new \stdClass;
                foreach ($userGroups as $oUserGroup) {
                    if (!empty($oUserGroup->userid)) {
                        $userGroups2->{$oUserGroup->userid} = new \stdClass;
                        $userGroups2->{$oUserGroup->userid}->team_id = $oUserGroup->team_id;
                        $userGroups2->{$oUserGroup->userid}->team_title = $oUserGroup->team_title;
                    }
                }
                foreach ($users as $oUser) {
                    $oUser->group = isset($userGroups2->{$oUser->userid}) ? $userGroups2->{$oUser->userid} : new \stdClass;
                }
            }
        }
        $oResult->users = $users;

        $q[0] = 'count(*)';
        $oResult->total = (int) $modelUsr->query_val_ss($q);

        return $oResult;
    }
    /**
     * 根据用户的填写数据进行排行
     */
    private function _userByRecord($oApp, $oCriteria, $page = 1, $size = 100) {
        $schemaId = substr($oCriteria->orderby, 7);
        $schemaSumCol = 'schema_' . $schemaId . '_sum';

        $modelRecDat = $this->model('matter\enroll\data');

        $q = [
            'userid,sum(value) ' . $schemaSumCol,
            'xxt_enroll_record_data',
            ['aid' => $oApp->id, 'state' => 1, 'schema_id' => $schemaId, 'userid' => (object) ['op' => '<>', 'pat' => '']],
        ];
        if (!empty($oCriteria->round) && is_string($oCriteria->round)) {
            $oCriteria->round = explode(',', $oCriteria->round);
        }
        if (!empty($oCriteria->round) && !in_array('ALL', $oCriteria->round)) {
            $q[2]['rid'] = $oCriteria->round;
        }

        $q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
        $q2['g'] = ['userid'];
        $q2['o'] = [$schemaSumCol . ' desc'];

        $users = $modelRecDat->query_objs_ss($q, $q2);
        if (!empty($users)) {
            if (!empty($oApp->entryRule->group->id)) {
                $q = [
                    'userid,team_id,team_title',
                    'xxt_group_record',
                    ['aid' => $oApp->entryRule->group->id],
                ];
                $userGroups = $modelRecDat->query_objs_ss($q);
                if (count($userGroups)) {
                    $userGroups2 = new \stdClass;
                    array_walk($userGroups, function ($oUserGroup, $key, $userGroups2) {
                        !empty($oUserGroup->userid) && $userGroups2->{$oUserGroup->userid} = $oUserGroup;
                    }, $userGroups2);
                }
            }
            /**
             * 补充用户信息
             */
            $q = [
                'u.nickname,a.headimgurl',
                'xxt_enroll_user u left join xxt_site_account a on u.userid = a.uid and u.siteid = a.siteid',
                ['u.aid' => $oApp->id, 'u.state' => 1, 'rid' => 'ALL'],
            ];
            foreach ($users as $oUser) {
                $q[2]['userid'] = $oUser->userid;
                $oEnlUsr = $modelRecDat->query_obj_ss($q);
                if ($oEnlUsr) {
                    $oUser->nickname = $oEnlUsr->nickname;
                    $oUser->headimgurl = $oEnlUsr->headimgurl;
                    $oUser->group = isset($userGroups2->{$oUser->userid}) ? $userGroups2->{$oUser->userid} : new \stdClass;
                }
            }
        }
        $oResult = new \stdClass;
        $oResult->users = $users;

        $q[0] = 'count(distinct userid)';
        $oResult->total = (int) $modelRecDat->query_val_ss($q);

        return $oResult;
    }
    /**
     * 用户排行榜
     */
    public function userByApp_action($app, $page = 1, $size = 100) {
        $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $oCriteria = $this->getPostJson();
        if (empty($oCriteria->orderby)) {
            return new \ParameterError();
        }

        if (0 === strpos($oCriteria->orderby, 'schema_')) {
            $oResult = $this->_userByRecord($oApp, $oCriteria, $page, $size);
        } else {
            $oResult = $this->_userByBehavior($oApp, $oCriteria, $page, $size);
        }

        return new \ResponseData($oResult);
    }
    /**
     * 根据行为对用户组排行
     */
    private function _groupByBehavior($oApp, $oCriteria, $userGroups) {
        $sql = 'select ';
        switch ($oCriteria->orderby) {
        case 'enroll':
            $sql .= 'sum(enroll_num)';
            break;
        case 'remark':
            $sql .= 'sum(remark_num)';
            break;
        case 'like':
            $sql .= 'sum(like_num)';
            break;
        case 'remark_other':
            $sql .= 'sum(do_remark_num)';
            break;
        case 'like_other':
            $sql .= 'sum(do_like_num)';
            break;
        case 'total_coin':
            $sql .= 'sum(user_total_coin)';
            break;
        case 'score':
        case 'average_score':
            $sql .= 'sum(score)';
            break;
        case 'vote_schema':
            $sql .= 'sum(vote_schema_num)';
            break;
        case 'vote_cowork':
            $sql .= 'sum(vote_cowork_num)';
            break;
        default:
            return new \ParameterError('不支持的排行数据类型【' . $oCriteria->orderby . '】');
        }

        $sql .= ' from xxt_enroll_user where aid=\'' . $oApp->id . "' and state=1";
        if (!empty($oCriteria->round) && is_string($oCriteria->round)) {
            $oCriteria->round = explode(',', $oCriteria->round);
        }
        if (empty($oCriteria->round) || in_array('ALL', $oCriteria->round)) {
            $sql .= " and rid = 'ALL'";
        } else {
            $whereByRound = ' and rid in("';
            $whereByRound .= implode('","', $oCriteria->round);
            $whereByRound .= '")';
            $sql .= $whereByRound;
        }

        /* 获取分组的数据 */
        $modelUsr = $this->model('matter\enroll\user');
        foreach ($userGroups as $oUserGroup) {
            $sqlByGroup = $sql . ' and group_id=\'' . $oUserGroup->v . '\'';
            $oUserGroup->id = $oUserGroup->v;
            $oUserGroup->title = $oUserGroup->l;
            unset($oUserGroup->v);
            unset($oUserGroup->l);
            if (in_array($oCriteria->orderby, ['score', 'average_score'])) {
                if ($oCriteria->orderby === 'score') {
                    $oUserGroup->num = round((float) $modelUsr->query_value($sqlByGroup), 2);
                } else {
                    if (!empty($oUserGroup->playerCount)) {
                        $oUserGroup->num = round((float) ($modelUsr->query_value($sqlByGroup) / $oUserGroup->playerCount), 2);
                    } else {
                        $oUserGroup->num = 0;
                    }
                }
            } else {
                $oUserGroup->num = (int) $modelUsr->query_value($sqlByGroup);
            }
        }
        /* 对分组数据进行排序 */
        usort($userGroups, function ($a, $b) {
            return $a->num < $b->num ? 1 : -1;
        });

        $oResult = new \stdClass;
        $oResult->groups = $userGroups;

        return $oResult;
    }
    /**
     * 根据记录数据对用户组排行
     */
    private function _groupByRecord($oApp, $oCriteria, $aUserGroups) {
        $schemaId = substr($oCriteria->orderby, 7);
        $schemaSumCol = 'schema_' . $schemaId . '_sum';

        $modelRecDat = $this->model('matter\enroll\data');

        $q = [
            'group_id,sum(value) ' . $schemaSumCol,
            'xxt_enroll_record_data',
            ['aid' => $oApp->id, 'state' => 1, 'schema_id' => $schemaId, 'group_id' => (object) ['op' => '<>', 'pat' => '']],
        ];
        if (!empty($oCriteria->round) && is_string($oCriteria->round)) {
            $oCriteria->round = explode(',', $oCriteria->round);
        }
        if (!empty($oCriteria->round) && !in_array('ALL', $oCriteria->round)) {
            $q[2]['rid'] = $oCriteria->round;
        }

        $q2['g'] = ['group_id'];
        $q2['o'] = [$schemaSumCol . ' desc'];

        $groups = $modelRecDat->query_objs_ss($q, $q2);
        if (!empty($groups)) {
            foreach ($groups as $oGroup) {
                if (!isset($aUserGroups[$oGroup->group_id])) {
                    continue;
                }
                $oGroup->id = $oGroup->group_id;
                $oGroup->title = $aUserGroups[$oGroup->group_id]->l;
                unset($oGroup->group_id);
            }
        }
        $oResult = new \stdClass;
        $oResult->groups = $groups;

        return $oResult;
    }
    /**
     * 分组排行榜
     */
    public function groupByApp_action($app) {
        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N', 'id,state,entry_rule,data_schemas']);
        if ($oApp === false || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }
        $modelGrpTeam = $this->model('matter\group\team');
        if (!empty($oApp->entryRule->group->id)) {
            $teams = $modelGrpTeam->byApp($oApp->entryRule->group->id, ['cascade' => 'playerCount']);
        }
        if (empty($teams)) {
            return new \ObjectNotFoundError();
        }

        $userGroups = [];
        foreach ($teams as $oTeam) {
            $oNewGroup = new \stdClass;
            $oNewGroup->v = $oTeam->team_id;
            $oNewGroup->l = $oTeam->title;
            $oNewGroup->playerCount = $oTeam->playerCount;
            $userGroups[$oTeam->team_id] = $oNewGroup;
        }

        $oCriteria = $this->getPostJson();
        if (empty($oCriteria->orderby)) {
            return new \ParameterError();
        }
        if (0 === strpos($oCriteria->orderby, 'schema_')) {
            $oResult = $this->_groupByRecord($oApp, $oCriteria, $userGroups);
        } else {
            $oResult = $this->_groupByBehavior($oApp, $oCriteria, $userGroups);
        }

        return new \ResponseData($oResult);
    }
    /**
     * 根据行为数据对单选项数据排行
     */
    private function _schemaByBehavior($oApp, $oCriteria, $oRankSchema, $aSchemaOps) {
        $modelRecDat = $this->model('matter\enroll\data');

        switch ($oCriteria->orderby) {
        case 'enroll': // 填写次数
            $q = [
                'value,count(*) num',
                'xxt_enroll_record_data',
                ['aid' => $oApp->id, 'state' => 1, 'schema_id' => $oRankSchema->id, 'value' => (object) ['op' => '<>', 'pat' => '']],
            ];
            if (!empty($oCriteria->round) && is_array($oCriteria->round) && !in_array('ALL', $oCriteria->round)) {
                $q[2]['rid'] = $oCriteria->round;
            }
            $q2 = ['g' => 'value', 'o' => 'num desc'];
            $oRankResult = $modelRecDat->query_objs_ss($q, $q2);
            if (count($oRankResult)) {
                array_walk($oRankResult, function (&$oData) use ($aSchemaOps) {$oData->l = isset($aSchemaOps[$oData->value]) ? $aSchemaOps[$oData->value] : '!未知';unset($oData->value);});
            }
            break;
        case 'score': // 总得分
        case 'average_score':
            $oRankResult = [];
            $aScoreSchemas = $this->model('matter\enroll\schema')->asAssoc($oApp->dynaDataSchemas, ['filter' => function ($oSchema) {return $this->getDeepValue($oSchema, 'requireScore') === 'Y';}]);
            if (count($aScoreSchemas)) {
                $q = [
                    'sum(score) num,count(distinct userid) user_num',
                    'xxt_enroll_record_data rd1',
                    ['aid' => $oApp->id, 'state' => 1, 'schema_id' => array_keys($aScoreSchemas)],
                ];
                if (!empty($oCriteria->round) && is_array($oCriteria->round) && !in_array('ALL', $oCriteria->round)) {
                    $q[2]['rid'] = $oCriteria->round;
                }
                foreach ($aSchemaOps as $opv => $opl) {
                    $q[2]['value'] = (object) ['op' => 'exists', 'pat' => 'select 1 from xxt_enroll_record_data rd2 where rd1.enroll_key=rd2.enroll_key and rd2.state=1 and rd2.schema_id=\'' . $oRankSchema->id . '\' and rd2.value=\'' . $opv . '\''];
                    $oNum = $modelRecDat->query_obj_ss($q);
                    $oNum->l = $opl;
                    if ($oCriteria->orderby === 'average_score') {
                        if (!empty($oNum->num) && !empty($oNum->user_num)) {
                            $oNum->num = round((float) ($oNum->num / $oNum->user_num), 2);
                        }
                    }
                    $oRankResult[] = $oNum;
                }
                /* 数据排序 */
                usort($oRankResult, function ($a, $b) {
                    return $a->num < $b->num ? 1 : -1;
                });
            } else {
                return [false, '活动中没有打分题'];
            }
            break;
        default:
            return [false, '不支持的排行指标类型【' . $oCriteria->orderby . '】'];
        }

        return [true, $oRankResult];
    }
    /**
     * 根据记录对用户组排行
     */
    private function _schemaByRecord($oApp, $oCriteria, $oRankSchema, $aSchemaOps) {
        $schemaId = substr($oCriteria->orderby, 7);

        $modelRecDat = $this->model('matter\enroll\data');
        $q = [
            'sum(value) num',
            'xxt_enroll_record_data rd1',
            ['aid' => $oApp->id, 'state' => 1, 'schema_id' => $schemaId],
        ];
        if (!empty($oCriteria->round) && is_array($oCriteria->round) && !in_array('ALL', $oCriteria->round)) {
            $q[2]['rid'] = $oCriteria->round;
        }
        foreach ($aSchemaOps as $opv => $opl) {
            $q[2]['value'] = (object) ['op' => 'exists', 'pat' => 'select 1 from xxt_enroll_record_data rd2 where rd1.enroll_key=rd2.enroll_key and rd2.state=1 and rd2.schema_id=\'' . $oRankSchema->id . '\' and rd2.value=\'' . $opv . '\''];
            $oNum = $modelRecDat->query_obj_ss($q);
            $oNum->l = $opl;
            $oRankResult[] = $oNum;
        }
        /* 数据排序 */
        usort($oRankResult, function ($a, $b) {
            return $a->num < $b->num ? 1 : -1;
        });

        return [true, $oRankResult];
    }
    /**
     * 题目排行榜（仅限单选题）
     */
    public function schemaByApp_action($app, $schema) {
        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N']);
        if ($oApp === false || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }
        $oRankSchema = tms_array_search($oApp->dynaDataSchemas, function ($oSchema) use ($schema) {return $oSchema->id === $schema;});
        if (false === $oRankSchema) {
            return new \ObjectNotFoundError('指定的题目不存在');
        }
        if ($oRankSchema->type !== 'single' || empty($oRankSchema->ops)) {
            return new \ParameterError('指定的题目不支持进行排行');
        }
        $aSchemaOps = [];
        array_walk($oRankSchema->ops, function ($oOp) use (&$aSchemaOps) {$aSchemaOps[$oOp->v] = $oOp->l;});

        $oCriteria = $this->getPostJson();
        if (empty($oCriteria->orderby)) {
            return new \ParameterError();
        }
        if (0 === strpos($oCriteria->orderby, 'schema_')) {
            $aResult = $this->_schemaByRecord($oApp, $oCriteria, $oRankSchema, $aSchemaOps);
        } else {
            $aResult = $this->_schemaByBehavior($oApp, $oCriteria, $oRankSchema, $aSchemaOps);
        }

        if (false === $aResult[0]) {
            return new \ResponseError($aResult[1]);
        }

        return new \ResponseData($aResult[1]);
    }
}