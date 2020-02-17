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
     * 添加提交后台任务
     */
    public function create($aid, $rid, $record_id, $params, $userid, $current = null) {
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
        $oUser->uid = $daemon->userid;
        /**
         * 生成或更新用户轮次汇总数据
         */
        $modelRec->setSummaryRec($oUser, $oEnlApp, $oRecord->rid);
        $modelDaemon->finish($daemonId, 'summary_rec');
        /**
         * 更新数据分题目排名
         */
        $modelRec->setSchemaScoreRank($oEnlApp, $oRecord->rid);
        $modelDaemon->finish($daemonId, 'schema_score_rank');
        /**
         * 处理用户汇总数据，行为分数据
         */
        $modelEvt = $this->model('matter\enroll\event');
        $this->model('matter\enroll\event')->submitRecord($oEnlApp, $oRecord, $oUser, $daemon->params->isNewRecord);
        $modelDaemon->finish($daemonId, 'summary_behavior');
        /**
         * 更新用户数据分排名
         */
        $modelEnlUsr = $this->model('matter\enroll\user')->setOnlyWriteDbConn(true);
        $modelEnlUsr->setUserScoreRank($oEnlApp, $oRecord->rid);
        $modelDaemon->finish($daemonId, 'user_score_rank');

        /* 生成提醒 */
        if ($daemon->params->isNewRecord) {
            $this->model('matter\enroll\notice')->addRecord($oEnlApp, $oRecord, $oUser);
        }
        /* 通知记录活动事件接收人 */
        if ($this->getDeepValue($oEnlApp, 'notifyConfig.submit.valid') === true) {
            $this->_notifyReceivers($oEnlApp, $oRecord);
        }
        $modelDaemon->finish($daemonId, 'notice');

        // 结束所有任务
        $modelDaemon->finish($daemonId);

        return [true];
    }
}