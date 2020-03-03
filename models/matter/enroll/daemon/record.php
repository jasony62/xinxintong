<?php
namespace matter\enroll\daemon;
/**
 * 后台进程任务：提交记录
 */
class record_model extends \TMS_MODEL {
    /**
     *
     */
    public function table() {
        return 'xxt_enroll_daemon_submit_record';
    }
    /**
     * byId方法中的id字段
     */
    public function id() {
        return 'id';
    }
    /**
     *
     */
    public function byId($id, $options = []) {
        $daemon = parent::byId($id, $options);
        if (isset($daemon->params)) {
            $daemon->params = json_decode($daemon->params);
        }

        return $daemon;
    }
    /**
     * 根据记录id获得任务
     */
    public function byRecordId($id, $options = []) {
        $fields = isset($options['fields']) ? $options['fields'] : '*';
        $q = [$fields, $this->table(), ['state' => 1, 'record_id' => $id]];
        $daemons = $this->query_objs_ss($q);

        return $daemons;
    }
    /**
     * 添加提交后台任务
     *
     * 如果有正在等待执行的后台任务，返回该任务的id
     */
    public function create($aid, $rid, $record_id, $params, $userid, $current = null) {
        $daemons = $this->byRecordId($record_id, ['fields' => 'id']);
        if (count($daemons) === 1) {
            return $daemons[0]->id;
        } else if (count($daemons) > 1) {
            $ids = array_map(function ($daemon) {return $daemon->id;}, $daemons);
            return max($ids);
        }

        $daemon = new \stdClass;
        $daemon->aid = $aid;
        $daemon->rid = $rid;
        $daemon->record_id = $record_id;
        $daemon->params = $this->escape($this->toJson($params));
        $daemon->userid = $userid;
        $daemon->state = 1;
        if (empty($current)) {
            $current = time();
        }
        $daemon->create_at = $current;

        $id = $this->insert($this->table(), $daemon, true);

        return $id;
    }
    /**
     * 更新任务状态
     */
    public function finish($daemonId, $taskName = null) {
        $task = new \stdClass;
        if (!empty($taskName)) {
            $task->{$taskName . '_at'} = time();
        } else {
            $task->state = 0;
        }

        $ret = $this->update($this->table(), $task, ['id' => $daemonId]);

        return $ret;
    }
    /**
     * 执行所有任务
     */
    public function exec() {
        $q = ['id', $this->table(), ['state' => 1]];
        $daemons = $this->query_objs_ss($q);
        foreach ($daemons as $daemon) {
            $this->_execDaemonTasks($daemon->id);
        }

        return true;
    }
    /**
     * 执行后台任务
     */
    private function _execDaemonTasks($daemonId) {
        $modelDaemon = $this->model('matter\enroll\daemon\record');
        $daemon = $modelDaemon->byId($daemonId);
        if (empty($daemon)) {
            return [false, '指定的后台任务不存在'];
        }
        $modelApp = $this->model('matter\enroll');
        $oEnlApp = $modelApp->byId($daemon->aid);
        $modelRec = $this->model('matter\enroll\record');
        $oRecord = $modelRec->byPlainId($daemon->record_id);
        $modelUsr = $this->model('matter\enroll\user');
        $oUser = $modelUsr->byIdInApp($oEnlApp, $daemon->userid, ['fields' => 'nickname,group_id']);
        if (empty($oUser)) {
            return [false, '记录对应的用户不存在'];
        }
        $oUser->uid = $daemon->userid;
        /**
         * 生成或更新用户轮次汇总数据
         */
        if ($daemon->summary_rec_at == 0) {
            $modelRec->setSummaryRec($oUser, $oEnlApp, $oRecord->rid);
            $modelDaemon->finish($daemonId, 'summary_rec');
        }
        /**
         * 更新数据分题目排名
         */
        if ($daemon->schema_score_rank_at == 0) {
            $modelRec->setSchemaScoreRank($oEnlApp, $oRecord->rid);
            $modelDaemon->finish($daemonId, 'schema_score_rank');
        }
        /**
         * 处理用户汇总数据，行为分数据
         */
        if ($daemon->summary_behavior_at == 0) {
            $modelEvt = $this->model('matter\enroll\event');
            $this->model('matter\enroll\event')->submitRecord($oEnlApp, $oRecord, $oUser, $daemon->params->isNewRecord);
            $modelDaemon->finish($daemonId, 'summary_behavior');
        }
        /**
         * 更新用户数据分排名
         */
        if ($daemon->user_score_rank_at == 0) {
            $modelEnlUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
            $modelEnlUsr->setUserScoreRank($oEnlApp, $oRecord->rid);
            $modelDaemon->finish($daemonId, 'user_score_rank');
        }

        /* 生成提醒 */
        if ($daemon->notice_at == 0) {
            if ($daemon->params->isNewRecord) {
                $this->model('matter\enroll\notice')->addRecord($oEnlApp, $oRecord, $oUser);
            }
            /* 通知记录活动事件接收人 */
            if ($this->getDeepValue($oEnlApp, 'notifyConfig.submit.valid') === true) {
                $this->_notifyReceivers($oEnlApp, $oRecord);
            }
            $modelDaemon->finish($daemonId, 'notice');
        }
        // 结束所有任务
        $modelDaemon->finish($daemonId);

        return [true];
    }
    /**
     * 通知记录活动事件接收人
     *
     * @param object $app
     * @param string $ek
     *
     */
    private function _notifyReceivers($oApp, $oRecord) {
        /* 通知接收人 */
        $receivers = $this->model('matter\enroll\user')->getSubmitReceivers($oApp, $oRecord, $oApp->notifyConfig->submit);
        if (empty($receivers)) {
            return false;
        }

        // 指定的提醒页名称，默认为讨论页
        $page = empty($oApp->notifyConfig->submit->page) ? 'cowork' : $oApp->notifyConfig->submit->page;
        switch ($page) {
        case 'repos':
            $noticeURL = $oApp->entryUrl . '&page=repos';
            break;
        default:
            $noticeURL = $oApp->entryUrl . '&ek=' . $oRecord->enroll_key . '&page=cowork';
        }

        $noticeName = 'site.enroll.submit';

        /*获取模板消息id*/
        $oTmpConfig = $this->model('matter\tmplmsg\config')->getTmplConfig($oApp, $noticeName, ['onlySite' => false, 'noticeURL' => $noticeURL]);
        if ($oTmpConfig[0] === false) {
            return false;
        }
        $oTmpConfig = $oTmpConfig[1];

        $modelTmplBat = $this->model('matter\tmplmsg\batch');
        $oCreator = new \stdClass;
        $oCreator->uid = $noticeName;
        $oCreator->name = 'system';
        $oCreator->src = 'pl';
        $modelTmplBat->send($oApp->siteid, $oTmpConfig->tmplmsgId, $oCreator, $receivers, $oTmpConfig->oParams, ['send_from' => $oApp->type . ':' . $oApp->id]);

        return true;
    }
}