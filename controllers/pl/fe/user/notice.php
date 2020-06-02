<?php
namespace pl\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 平台管理端用户通知
 */
class notice extends \pl\fe\base {
    /**
     * 进入平台管理页面用户身份验证页面
     */
    public function index_action() {
        \TPL::output('/pl/fe/user/notice');
        exit;
    }
    /**
     * 查看通知发送日志
     *
     * @param int $batch 通知批次id
     */
    public function list_action($page = 1, $size = 10) {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelTmplBat = $this->model('matter\tmplmsg\plbatch');

        $q = [
            '*',
            'xxt_log_tmplmsg_pldetail',
            ["userid" => $user->id],
        ];
        $q2 = ['o' => 'id desc'];

        $logs = $modelTmplBat->query_objs_ss($q, $q2);
        $result = new \stdClass;
        foreach ($logs as &$log) {
            $batch = $modelTmplBat->byId($log->batch_id);
            $log->batch = $batch;
        }
        $result->logs = $logs;

        $q[0] = 'count(*)';
        $result->total = $modelTmplBat->query_val_ss($q);

        return new \ResponseData($result);
    }
    /**
     * 查看未读通知发送日志
     *
     * @param string $sendTo 发送渠道
     */
    public function uncloseList_action($page = 1, $size = 10, $sendTo = 'pl') {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelTmplBat = $this->model('matter\tmplmsg\plbatch');

        $q = [
            '*',
            'xxt_log_tmplmsg_pldetail',
            ['userid' => $user->id, 'close_at' => 0, 'send_to' => $sendTo],
        ];
        $q2 = ['o' => 'id desc'];

        $logs = $modelTmplBat->query_objs_ss($q, $q2);
        $result = new \stdClass;
        foreach ($logs as &$log) {
            $batch = $modelTmplBat->byId($log->batch_id);
            $log->batch = $batch;
        }
        $result->logs = $logs;

        $q[0] = 'count(*)';
        $result->total = $modelTmplBat->query_val_ss($q);

        return new \ResponseData($result);
    }
    /**
     * 关闭未读通知
     * 只允许自己关闭
     *
     * @param int $id 通知日志id
     *
     */
    public function close_action($id) {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }
        $model = $this->model();
        $q = [
            '*',
            'xxt_log_tmplmsg_pldetail',
            ['id' => $id],
        ];
        $log = $model->query_obj_ss($q);
        if (false === $log) {
            return new \ObjectNotFoundError();
        }
        if ($log->userid !== $user->id) {
            return new \ResponseError('没有删除通知的权限');
        }
        $rst = $model->update('xxt_log_tmplmsg_pldetail', ['close_at' => time()], ['id' => $id]);

        return new \ResponseData($rst);
    }
}