<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 日志
 */
class log extends \pl\fe\matter\base {
    /**
     * 返回视图
     */
    public function index_action() {
        \TPL::output('/pl/fe/matter/mission/frame');
        exit;
    }
    /**
     * 查询日志
     */
    public function list_action($mission, $page = 1, $size = 30) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $oMission = $this->model('matter\mission')->byId($mission, ['cascaded' => 'N']);
        if (false === $oMission || $oMission->state !== '1') {
            return new \ObjectNotFoundError();
        }
        $modelLog = $this->model('matter\log');
        $aOptions = [];
        $reads = $modelLog->listMatterOp($oMission->id, 'mission', $aOptions, $page, $size);

        return new \ResponseData($reads);
    }
}