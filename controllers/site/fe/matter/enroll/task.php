<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 记录活动任务
 */
class task extends base {
    /**
     * 当前用户需要完成的任务
     */
    public function list_action($app, $type = null, $state = null, $rid = null, $ek = null) {
        $modelApp = $this->model('matter\enroll');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N', 'appRid' => $rid]);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $oUser = $this->getUser($oApp);

        /* 有效的任务类型 */
        $aTaskTypes = ['baseline', 'question', 'answer', 'vote', 'score'];
        if (!empty($type)) {
            if (!in_array($type, $aTaskTypes)) {
                return new \ParameterError('没有指定有效的任务类型');
            }
            $aTaskTypes = [$type];
        }
        /* 有效的任务状态 */
        $aTaskStates = ['IP', 'BS', 'AE'];
        if (!empty($state)) {
            if (!in_array($state, $aTaskStates)) {
                return new \ParameterError('没有指定有效的任务状态');
            }
            $aTaskStates = [$state];
        }
        // 获取用户所有任务
        $modelTsk = $this->model('matter\enroll\task', $oApp);
        $tasks = $modelTsk->byUser($oApp, $oUser, $aTaskTypes, $aTaskStates, $ek, true);
        if ($tasks[0] === false) {
            return new \ResponseError($tasks[1]);
        }
        $tasks = $tasks[1];
        /* 按照任务的开始时间排序 */
        usort($tasks, function ($a, $b) {
            if ($a->start_at === $b->start_at) {
                return 0;
            }
            return $a->start_at < $b->start_at ? -1 : 1;
        });

        return new \ResponseData($tasks);
    }
    /**
     * 对填写数据进行投票
     *
     * @param int $data xxt_enroll_record_data 的id
     */
    public function vote_action($data, $task) {
        $modelRecDat = $this->model('matter\enroll\data');
        $oRecData = $modelRecDat->byId($data, ['fields' => 'id,aid,rid,enroll_key,state,multitext_seq,userid,nickname']);
        if (false === $oRecData || $oRecData->state !== '1') {
            return new \ObjectNotFoundError('（1）指定的对象不存在或不可用');
        }

        $oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError('（2）指定的对象不存在或不可用');
        }

        $modelTsk = $this->model('matter\enroll\task', $oApp);
        $oTask = $modelTsk->byId($task);
        if (false === $oTask || $oTask->config_type !== 'vote') {
            return new \ObjectNotFoundError('没有找到匹配的投票任务');
        }

        $oUser = $this->getUser($oApp);

        $aVoteResult = $modelRecDat->vote($oApp, $oTask, $oRecData->id, $oUser);
        if (false === $aVoteResult[0]) {
            return new \ResponseError($aVoteResult[1]);
        }
        $oNewVote = $aVoteResult[1];

        /* 记录事件汇总数据 */
        $modelEnlEvt = $this->model('matter\enroll\event');
        if ($oRecData->multitext_seq > 0) {
            $modelEnlEvt->voteRecCowork($oApp, $oRecData, $oUser);
        } else {
            $modelEnlEvt->voteRecSchema($oApp, $oRecData, $oUser);
        }

        return new \ResponseData([$oNewVote, $aVoteResult[2]]);
    }
    /**
     * 对填写数据撤销投票
     *
     * @param int $data xxt_enroll_record_data 的id
     */
    public function unvote_action($data, $task) {
        $modelRecDat = $this->model('matter\enroll\data');
        $oRecData = $modelRecDat->byId($data, ['fields' => 'id,aid,rid,enroll_key,state,multitext_seq,userid,nickname']);
        if (false === $oRecData || $oRecData->state !== '1') {
            return new \ObjectNotFoundError('（1）指定的对象不存在或不可用');
        }

        $oApp = $this->model('matter\enroll')->byId($oRecData->aid, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError('（2）指定的对象不存在或不可用');
        }

        $modelTsk = $this->model('matter\enroll\task', $oApp);
        $oTask = $modelTsk->byId($task);
        if (false === $oTask || $oTask->config_type !== 'vote') {
            return new \ObjectNotFoundError('没有找到匹配的投票任务');
        }

        $oUser = $this->getUser($oApp);

        $aVoteResult = $modelRecDat->unvote($oApp, $oTask, $oRecData->id, $oUser);
        if (false === $aVoteResult[0]) {
            return new \ResponseError($aVoteResult[1]);
        }

        /* 记录事件汇总数据 */
        $modelEnlEvt = $this->model('matter\enroll\event');
        if ($oRecData->multitext_seq > 0) {
            $modelEnlEvt->unvoteRecCowork($oApp, $oRecData, $oUser);
        } else {
            $modelEnlEvt->unvoteRecSchema($oApp, $oRecData, $oUser);
        }

        return new \ResponseData($aVoteResult[1]);
    }
    /**
     * 批量投票
     * 所有新的或取消的投票
     */
    public function batchVote_action($app, $task) {
        $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError('（1）指定的对象不存在或不可用');
        }

        $modelTsk = $this->model('matter\enroll\task', $oApp);
        $oTask = $modelTsk->byId($task);
        if (false === $oTask || $oTask->config_type !== 'vote') {
            return new \ObjectNotFoundError('（2）没有找到匹配的投票任务');
        }

        // 变更投票的数据
        $aRecDataVotes = $this->getPostJson();
        if (empty($aRecDataVotes)) {
            return new \ResponseError('（3）没有提交有效数据');
        }

        $oUser = $this->getUser($oApp);

        $modelRecDat = $this->model('matter\enroll\data');
        $modelEnlEvt = $this->model('matter\enroll\event');

        $oResult = new \stdClass; // 记录更新结果
        foreach ($aRecDataVotes as $oRecDataVote) {
            $oRecData = $modelRecDat->byId($oRecDataVote->id, ['fields' => 'id,aid,rid,enroll_key,state,multitext_seq,userid,nickname']);
            if (empty($oRecDataVote->vote_at)) {
                $aVoteResult = $modelRecDat->unvote($oApp, $oTask, $oRecData->id, $oUser);
                $oResult->{$oRecData->id} = $aVoteResult;
                if (false === $aVoteResult[0]) {
                    continue;
                }
                /* 记录事件汇总数据 */
                if ($oRecData->multitext_seq > 0) {
                    $modelEnlEvt->unvoteRecCowork($oApp, $oRecData, $oUser);
                } else {
                    $modelEnlEvt->unvoteRecSchema($oApp, $oRecData, $oUser);
                }
            } else {
                $aVoteResult = $modelRecDat->vote($oApp, $oTask, $oRecData->id, $oUser);
                $oResult->{$oRecData->id} = $aVoteResult;
                if (false === $aVoteResult[0]) {
                    continue;
                }
                /* 记录事件汇总数据 */
                if ($oRecData->multitext_seq > 0) {
                    $modelEnlEvt->voteRecCowork($oApp, $oRecData, $oUser);
                } else {
                    $modelEnlEvt->voteRecSchema($oApp, $oRecData, $oUser);
                }
            }
        }

        return new \ResponseData($oResult);
    }
    /**
     * 投票任务的完成情况
     */
    public function votePerformance_action($app, $task) {
        $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError('（1）指定的对象不存在或不可用');
        }

        $modelTsk = $this->model('matter\enroll\task', $oApp);
        $oTask = $modelTsk->byId($task);
        if (false === $oTask || $oTask->config_type !== 'vote') {
            return new \ObjectNotFoundError('（2）没有找到匹配的投票任务');
        }

        $oActiveRnd = $oApp->appRound;
        $oVoteRule = $modelTsk->ruleByTask($oTask, $oActiveRnd);
        if (false === $oVoteRule[0]) {
            return new \ParameterError($oVoteRule[1]);
        }
        $oVoteRule = $oVoteRule[1];

        $oUser = $this->getUser($oApp);

        $oResult = new \stdClass;
        if (isset($oVoteRule->limit)) {
            $oResult->limit = $oVoteRule->limit;
        }

        $q = [
            'data_id',
            'xxt_enroll_vote',
            ['aid' => $oApp->id, 'rid' => $oActiveRnd->rid, 'userid' => $oUser->uid, 'state' => 1],
        ];
        $oResult->data_ids = $modelTsk->query_vals_ss($q);

        return new \ResponseData($oResult);
    }
}