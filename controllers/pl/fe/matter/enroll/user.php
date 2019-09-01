<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 记录活动用户
 */
class user extends main_base {
    /**
     * 返回提交过填写记录的用户列表
     */
    public function enrollee_action($page = 1, $size = 30) {
        $oApp = $this->app;
        $modelUsr = $this->model('matter\enroll\user');
        $oPost = $this->getPostJson();

        $aOptions = [];
        !empty($oPost->orderby) && $aOptions['orderby'] = $oPost->orderby;
        !empty($oPost->byGroup) && $aOptions['byGroup'] = $oPost->byGroup;
        !empty($oPost->rids) && $aOptions['rid'] = $oPost->rids;
        !empty($oPost->onlyEnrolled) && $aOptions['onlyEnrolled'] = $oPost->onlyEnrolled;
        if (!empty($oPost->filter->by) && !empty($oPost->filter->keyword)) {
            $aOptions[$oPost->filter->by] = $oPost->filter->keyword;
        }
        /**
         * 轮次信息
         */
        if (!empty($oPost->rids)) {
            $modelEnlRnd = $this->model('matter\enroll\round');
            $rounds = $modelEnlRnd->byIds($oPost->rids, ['fields' => 'rid,title,purpose']);
        } else {
            $rounds = [$this->app->appRound->rid => $this->app->appRound];
        }
        if (!empty($rounds)) {
            $aOptions['rid'] = array_keys($rounds);
        }

        $oResult = $modelUsr->enrolleeByApp($oApp, $page, $size, $aOptions);
        if (!empty($rounds)) {
            $oResult->rounds = $rounds;
        }

        return new \ResponseData($oResult);
    }
    /**
     * 返回用户分组列表
     *
     * @param string $rids
     */
    public function group_action($rids = null) {
        if (!isset($this->app->entryRule->group->id)) {
            return new \ResponseError('指定活动没有关联分组活动');
        }
        $modelGrp = $this->model('matter\group');
        $oGroupApp = $modelGrp->byId($this->app->entryRule->group->id, ['fields' => 'id,title', 'cascaded' => 'Y', 'team' => ['fields' => 'team_id,title']]);
        if (false === $oGroupApp) {
            return new \ResponseError('指定活动关联的分组活动不存在');
        }
        if (empty($oGroupApp->teams)) {
            return new \ResponseError('指定活动关联的分组活动没有主分组');
        }

        $aEnlGrpOptions = [];
        if (empty($rids)) {
            $aRounds = [$this->app->appRound->rid => $this->app->appRound];
        } else {
            $modelEnlRnd = $this->model('matter\enroll\round');
            $aRounds = $modelEnlRnd->byIds($rids, ['fields' => 'rid,title,purpose']);
        }
        if (!empty($aRounds)) {
            $aEnlGrpOptions['rid'] = array_keys($aRounds);
        }

        $modelEnlGrp = $this->model('matter\enroll\group');
        $groups = $modelEnlGrp->byApp($this->app, $aEnlGrpOptions);

        $aGroups = [];
        array_walk($groups, function ($oGroup) use (&$aGroups) {
            $aGroups[$oGroup->group_id][$oGroup->rid] = $oGroup;
        });

        $teams = null;
        if (count($aGroups)) {
            if (empty($aRounds) || count($aRounds) === 1) {
                $teams = $oGroupApp->teams;
                array_walk($oGroupApp->teams, function (&$oTeam) use (&$aGroups) {
                    if (isset($aGroups[$oTeam->team_id])) {
                        $oTeam->data = array_values($aGroups[$oTeam->team_id])[0];
                    };
                });
            } else {
                $teams = [];
                foreach ($aRounds as $oRnd) {
                    array_walk($oGroupApp->teams, function (&$oTeam) use (&$aGroups, $oRnd, &$teams) {
                        if (isset($aGroups[$oTeam->team_id])) {
                            $oTeamByRnd = clone $oTeam;
                            $oTeamByRnd->data = $aGroups[$oTeamByRnd->team_id][$oRnd->rid];
                            $teams[] = $oTeamByRnd;
                        };
                    });
                }
            }
        }

        $oResult = new \stdClass;
        $oResult->groups = $teams;
        if (!empty($aRounds)) {
            $oResult->rounds = $aRounds;
        }

        return new \ResponseData($oResult);
    }
    /**
     * 活动指定的所有完成人
     */
    public function assigned_action($rid = null) {
        $modelUsr = $this->model('matter\enroll\user');
        $oResult = $modelUsr->assignedByApp($this->app, $rid, ['inGroupTeam' => true, 'leader' => ['Y', 'S', 'N']]);

        return new \ResponseData($oResult);
    }
    /**
     * 未完成任务用户列表
     */
    public function undone_action($rids = null) {
        $oApp = $this->app;

        $modelUsr = $this->model('matter\enroll\user');
        $modelRnd = $this->model('matter\enroll\round');
        if (empty($rids)) {
            $aRounds = [$this->app->appRound->rid => $this->app->appRound];
        } else if (1 === preg_match('/^all$/i', $rids)) {
            $oResultRounds = $modelRnd->byApp($oApp, ['fields' => 'rid,title,start_at,purpose']);
            $aRounds = [];
            if (count($oResultRounds->rounds)) {
                array_walk($oResultRounds->rounds, function ($oRnd) use (&$aRounds) {
                    $aRounds[$oRnd->rid] = $oRnd;
                });
            }
        } else {
            $aRounds = $modelRnd->byIds($rids, ['fields' => 'rid,title,start_at,purpose']);
        }
        if (empty($aRounds)) {
            return new \ObjectNotFoundError('指定的轮次不存在');
        }

        $oResult = new \stdClass;
        $userCount = 0;
        $oUsers = [];
        foreach ($aRounds as $rid => $oRnd) {
            $oUndoneResult = $modelUsr->undoneByApp($oApp, $rid);
            if (!empty($oUndoneResult->users)) {
                foreach ($oUndoneResult->users as $oUser) {
                    unset($oUser->group_id, $oUser->uid);
                    $oUsers[$rid][] = $oUser;
                    $userCount++;
                }
                if (!isset($oResult->app)) {
                    $oResult->app = $oUndoneResult->app;
                }
            }
        }

        $oResult->userCount = $userCount;
        $oResult->users = $oUsers;
        $oResult->rounds = $aRounds;

        return new \ResponseData($oResult);
    }
    /**
     * 发表过留言的用户
     */
    public function remarker_action($page = 1, $size = 30) {
        $modelUsr = $this->model('matter\enroll\user');
        $oResult = $modelUsr->remarkerByApp($this->app, $page, $size);

        return new \ResponseData($oResult);
    }
}