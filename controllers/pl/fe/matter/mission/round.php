<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目轮次控制器
 */
class round extends \pl\fe\matter\base {
    /**
     * 返回指定项目下的轮次
     *
     * @param string $mission app's id
     *
     */
    public function list_action($mission, $checked = null, $page = 1, $size = 10) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $oMission = $this->model('matter\mission')->byId($mission, ['cascaded' => 'N']);
        if (false === $oMission || $oMission->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $modelRnd = $this->model('matter\mission\round');

        $oPage = new \stdClass;
        $oPage->num = $page;
        $oPage->size = $size;

        $oResult = $modelRnd->byMission($oMission, ['page' => $oPage, 'state' => [1, 2], 'fields' => 'id,state,rid,title,start_at,end_at']);
        if (!empty($checked)) {
            if ($checked = $modelRnd->byId($checked)) {
                $oResult->checked = $checked;
            }
        }

        return new \ResponseData($oResult);
    }
    /**
     * 获取设置定时轮次的时间
     *
     * @param string $mission
     *
     */
    public function getCron_action() {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelRnd = $this->model('matter\mission\round');

        $oPosted = $this->getPostJson();

        if (empty($oPosted->roundCron)) {
            return new \ResponseError('请先设置定时规则！');
        }

        $rules[] = $oPosted->roundCron;
        $rst = $modelRnd->sampleByCron($rules);

        return new \ResponseData($rst);
    }
    /**
     * 添加轮次
     *
     * @param string $mission
     *
     */
    public function add_action($mission) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $oMission = $this->model('matter\mission')->byId($mission, ['cascaded' => 'N']);
        if (false === $oMission) {
            return new \ObjectNotFoundError();
        }

        $modelRnd = $this->model('matter\mission\round');
        $oPosted = $this->getPostJson();

        $aResult = $modelRnd->create($oMission, $oPosted, $oUser);
        if ($aResult[0] === false) {
            return new \ResponseError($aResult[1]);
        }

        return new \ResponseData($aResult[1]);
    }
    /**
     * 根据填写时段规则，将指定的时段设置为启用时段
     */
    public function activeByCron_action($mission, $rid) {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $oMission = $this->model('matter\mission')->byId($mission, ['cascaded' => 'N']);
        if (false === $oMission) {
            return new \ObjectNotFoundError();
        }

        $modelRnd = $this->model('matter\mission\round');
        $oRound = $modelRnd->byId($rid);
        if (false === $oRound) {
            return new \ObjectNotFoundError();
        }

        $oSampleRnd = $modelRnd->sampleByCron($oMission->roundCron);

        $modelRnd->update(
            'xxt_mission_round',
            $oSampleRnd,
            ['rid' => $oRound->rid]
        );

        return new \ResponseData($oSampleRnd);
    }
    /**
     * 更新轮次
     *
     * @param string $mission
     * @param string $rid
     */
    public function update_action($mission, $rid) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $oMission = $this->model('matter\mission')->byId($mission, ['cascaded' => 'N']);
        if (false === $oMission) {
            return new \ObjectNotFoundError();
        }

        $modelRnd = $this->model('matter\mission\round');
        $oRound = $modelRnd->byId($rid);
        if (false === $oRound) {
            return new \ObjectNotFoundError();
        }

        $oPosted = $this->getPostJson();

        if (!empty($oPosted->start_at) && !empty($oPosted->end_at) && $oPosted->start_at > $oPosted->end_at) {
            return new \ResponseError('更新失败，本轮次的开始时间不能晚于结束时间！');
        }

        $aResult = $modelRnd->checkProperties($oPosted, true);
        if (false === $aResult[0]) {
            return new \ResponseError($aResult[1]);
        }

        $rst = $modelRnd->update(
            'xxt_mission_round',
            $oPosted,
            ['mission_id' => $oMission->id, 'rid' => $rid]
        );

        $oRound = $modelRnd->byId($rid);

        return new \ResponseData($oRound);
    }
    /**
     * 删除轮次
     *
     * @param string $mission
     * @param string $rid
     */
    public function remove_action($mission, $rid) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $oMission = $this->model('matter\mission')->byId($mission, ['cascaded' => 'N']);
        if (false === $oMission) {
            return new \ObjectNotFoundError();
        }

        $modelRnd = $this->model('matter\mission\round');
        $oRound = $modelRnd->byId($rid);
        if (false === $oRound) {
            return new \ObjectNotFoundError();
        }
        /**
         * 删除轮次
         */
        $rst = $modelRnd->update(
            'xxt_mission_round',
            ['state' => 0],
            ['mission_id' => $oMission->id, 'rid' => $rid]
        );

        return new \ResponseData($rst);
    }
}