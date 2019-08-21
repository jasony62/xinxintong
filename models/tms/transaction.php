<?php
namespace tms;
/**
 * tms事物
 */
class transaction_model extends \TMS_MODEL {
    /**
     *
     */
    public function table() {
        return 'tms_transaction';
    }
    /**
     * byId方法中的id字段
     */
    public function id() {
        return 'id';
    }
    /**
     * 开始事物
     */
    public function begin($oRequest) {
        $trans = new \stdClass;
        $trans->begin_at = isset($oRequest->begin_at) ? $oRequest->begin_at : microtime(TRUE);
        $trans->request_uri = isset($oRequest->request_uri) ? $oRequest->request_uri : '';
        $trans->user_agent = isset($oRequest->user_agent) ? $oRequest->user_agent : '';
        $trans->referer = isset($oRequest->referer) ? $oRequest->referer : '';
        $trans->remote_addr = isset($oRequest->remote_addr) ? $oRequest->remote_addr : '';

        $trans->id = $this->insert($this->table(), $trans, true);

        return $trans;
    }
    /**
     * 完成事物
     */
    public function end($transId) {
        $endAt = microtime(TRUE);
        $this->update($this->table(), ['end_at' => $endAt], [$this->id() => $transId]);
    }
}