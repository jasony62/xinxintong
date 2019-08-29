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
     * @param string $rid
     */
    public function group_action($rid = null) {
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
        if (empty($rid)) {
            $aRounds = [$this->app->appRound->rid => $this->app->appRound];
        } else {
            $modelEnlRnd = $this->model('matter\enroll\round');
            $aRounds = $modelEnlRnd->byIds($rid, ['fields' => 'rid,title,purpose']);
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
    public function assigned_action() {
        $modelUsr = $this->model('matter\enroll\user');
        $oResult = $modelUsr->assignedByApp($this->app, ['inGroupTeam' => true, 'leader' => ['Y', 'S', 'N']]);

        return new \ResponseData($oResult);
    }
    /**
     * 未完成任务用户列表
     */
    public function undone_action($app, $rid = '') {
        $oApp = $this->app;

        $modelUsr = $this->model('matter\enroll\user');

        $oPosted = $this->getPostJson();
        if (empty($oPosted->rids)) {
            $oResult = $modelUsr->undoneByApp($oApp, 'ALL');
        } else {
            $aRounds = [];
            $aUsers = [];
            $modelRnd = $this->model('matter\enroll\round');
            foreach ($oPosted->rids as $rid) {
                $oRnd = $modelRnd->byId($rid, ['fields' => 'title,start_at']);
                if ($oRnd) {
                    $oRnd->rid = $rid;
                    $aRounds[] = $oRnd;
                    $oResult = $modelUsr->undoneByApp($oApp, $rid);
                    if (!empty($oResult->users)) {
                        foreach ($oResult->users as $oUser) {
                            if (!isset($aUsers[$oUser->userid])) {
                                /* 清除不必要的数据 */
                                unset($oUser->groupid);
                                unset($oUser->uid);
                                $aUsers[$oUser->userid] = $oUser;
                            }
                            $aUsers[$oUser->userid]->rounds[] = $rid;
                            $aUsers[$oUser->userid]->undones[] = $oUser->undoneTasks;
                            unset($oUser->undoneTasks);
                        }
                    }
                }
            }
            $oResult = new \stdClass;
            $oResult->users = array_values($aUsers);
            usort($aRounds, function ($a, $b) {
                return $a->start_at > $b->start_at ? 1 : -1;
            });
            $oResult->rounds = $aRounds;
        }

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