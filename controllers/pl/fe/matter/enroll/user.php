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
    public function enrollee_action($app, $page = 1, $size = 30) {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $modelEnl = $this->model('matter\enroll');
        $oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }
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

        $oResult = $modelUsr->enrolleeByApp($oApp, $page, $size, $aOptions);

        return new \ResponseData($oResult);
    }
    /**
     * 未完成任务用户列表
     */
    public function undone_action($app, $rid = '') {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $modelEnl = $this->model('matter\enroll');
        $oApp = $modelEnl->byId($app, ['cascaded' => 'N', 'fields' => 'siteid,id,state,mission_id,entry_rule,action_rule,absent_cause']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

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
     * 根据通讯录返回用户完成情况
     */
    public function byMschema_action($app, $mschema, $rid = '', $page = 1, $size = 30) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelEnl = $this->model('matter\enroll');
        $oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        $modelMs = $this->model('site\user\memberschema');
        $oMschema = $modelMs->byId($mschema, ['cascaded' => 'N']);
        if (false === $oMschema) {
            return new \ObjectNotFoundError();
        }

        $modelUsr = $this->model('matter\enroll\user');
        $options = [];
        !empty($rid) && $options['rid'] = $rid;
        $oResult = $modelUsr->enrolleeByMschema($oApp, $oMschema, $page, $size, $options);
        /*查询有openid的用户发送消息的情况*/
        if (count($oResult->members)) {
            foreach ($oResult->members as $member) {
                $q = [
                    'd.tmplmsg_id,d.status,b.create_at',
                    'xxt_log_tmplmsg_detail d,xxt_log_tmplmsg_batch b',
                    "d.userid = '{$member->userid}' and d.batch_id = b.id and b.send_from = 'enroll:" . $oApp->id . "'",
                ];
                $q2 = [
                    'r' => ['o' => 0, 'l' => 1],
                    'o' => 'b.create_at desc',
                ];
                if ($tmplmsg = $modelUsr->query_objs_ss($q, $q2)) {
                    $member->tmplmsg = $tmplmsg[0];
                } else {
                    $member->tmplmsg = new \stdClass;
                }
            }
        }

        return new \ResponseData($oResult);
    }
    /**
     * 发表过留言的用户
     */
    public function remarker_action($app, $page = 1, $size = 30) {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $modelEnl = $this->model('matter\enroll');
        $oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        $modelUsr = $this->model('matter\enroll\user');
        $oResult = $modelUsr->remarkerByApp($oApp, $page, $size);

        return new \ResponseData($oResult);
    }
    /**
     * 根据用户的填写记录更新用户数据
     */
    public function repair_action($app, $rid = '', $onlyCheck = 'Y') {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelEnl = $this->model('matter\enroll');
        $oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        $modelUsr = $this->model('matter\enroll\user');
        $aUpdatedResult = $modelUsr->renew($oApp, $rid, $onlyCheck);

        return new \ResponseData($aUpdatedResult);
    }
    /**
     * 更新用户对应的分组信息
     */
    public function repairGroup_action($app) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelEnl = $this->model('matter\enroll');
        $oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        if (!isset($oApp->entryRule->group->id)) {
            return new \ResponseError('没有指定关联的分组活动');
        }

        $updatedCount = $this->model('matter\enroll\user')->repairGroup($oApp);

        return new \ResponseData($updatedCount);
    }
}