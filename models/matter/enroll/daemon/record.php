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
}