<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 记录活动主控制器
 */
class round extends \pl\fe\matter\base {
    /**
     * 返回指定记录活动下的轮次
     *
     * @param string $app app's id
     * @param string $checked round's id 指定的轮次
     *
     */
    public function list_action($app, $checked = null, $page = 1, $size = 10) {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $modelRnd = $this->model('matter\enroll\round');

        /* 自动生成汇总轮次和目标轮次 */
        $modelRnd->byCron($oApp, 'S');
        $modelRnd->byCron($oApp, 'B');

        $oPage = new \stdClass;
        $oPage->at = $page;
        $oPage->size = $size;

        $fields = 'id,state,rid,title,purpose,start_at,end_at,mission_rid';

        $oResult = $modelRnd->byApp($oApp, ['page' => $oPage, 'fields' => $fields, 'state' => [0, 1, 2]]);
        if (!empty($checked)) {
            if ($oChecked = $modelRnd->byId($checked, ['fields' => $fields])) {
                $oResult->checked = $oChecked;
            }
        }

        return new \ResponseData($oResult);
    }
    /**
     * 获取设置定时轮次的时间
     *
     * @param string $app
     *
     */
    public function getCron_action() {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelRnd = $this->model('matter\enroll\round');

        $oPosted = $this->getPostJson();

        if (empty($oPosted->roundCron)) {
            return new \ResponseError('请先设置定时规则！');
        }

        $rules[] = $oPosted->roundCron;
        $oExampleRnd = $modelRnd->sampleByCron($rules);

        return new \ResponseData($oExampleRnd);
    }
    /**
     * 添加轮次
     *
     * @param string $app
     *
     */
    public function add_action($app) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        $modelRnd = $this->model('matter\enroll\round');
        $oPosted = $this->getPostJson();

        $aResult = $modelRnd->create($oApp, $oPosted, $oUser);
        if ($aResult[0] === false) {
            return new \ResponseError($aResult[1]);
        }

        return new \ResponseData($aResult[1]);
    }
    /**
     * 根据填写时段规则，将指定的时段设置为启用时段
     */
    public function activeByCron_action($app, $rid) {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        $modelRnd = $this->model('matter\enroll\round');
        $oRound = $modelRnd->byId($rid);
        if (false === $oRound) {
            return new \ObjectNotFoundError();
        }

        $oSampleRnd = $modelRnd->sampleByCron($oApp->roundCron);

        $modelRnd->update(
            'xxt_enroll_round',
            $oSampleRnd,
            ['rid' => $oRound->rid]
        );

        return new \ResponseData($oSampleRnd);
    }
    /**
     * 和项目轮次一致
     */
    public function syncMissionRound_action($app) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelEnl = $this->model('matter\enroll');
        $oApp = $this->model('matter\enroll')->byId($app, ['fields' => 'id,siteid,state,mission_id,sync_mission_round', 'cascaded' => 'N']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }
        if (empty($oApp->mission_id)) {
            return new \ResponseError('活动不属于任何项目，无法关联项目轮次');
        }
        $oMission = $this->model('matter\mission')->byId($oApp->mission_id);
        if (false === $oMission) {
            return new \ObjectNotFoundError('活动所属项目不存在');
        }

        $modelRnd = $this->model('matter\enroll\round');
        $oResult = $modelRnd->byApp($oApp, ['page' => (object) ['at' => 1, 'size' => 1]]);
        if (empty($oResult->total) || $oResult->total !== 1) {
            return new \ResponseError('活动中已经有多个轮次，不支持与项目轮次自动关联');
        }
        $oAppRnd = $oResult->rounds[0];

        $oMisRnd = $this->model('matter\mission\round')->getActive($oMission);
        if (false === $oMisRnd) {
            return new \ResponseError('活动所属项目中没有有效轮次，无法进行关联');
        }

        /* 更新轮次 */
        $oUpdate = new \stdClass;
        $oUpdate->title = $modelRnd->escape($oMisRnd->title);
        $oUpdate->start_at = $oMisRnd->start_at;
        $oUpdate->end_at = $oMisRnd->end_at;
        $oUpdate->mission_rid = $oMisRnd->rid;

        $modelRnd->update('xxt_enroll_round', $oUpdate, ['rid' => $oAppRnd->rid]);

        foreach ($oUpdate as $prop => $val) {
            $oAppRnd->{$prop} = $val;
        }

        /*更新活动*/
        if ($oApp->sync_mission_round !== 'Y') {
            $modelEnl->update('xxt_enroll', ['sync_mission_round' => 'Y'], ['id' => $oApp->id]);
        }

        return new \ResponseData($oAppRnd);
    }
    /**
     * 更新轮次
     *
     * @param string $app
     * @param string $rid
     */
    public function update_action($app, $rid) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        $modelRnd = $this->model('matter\enroll\round');
        $oRound = $modelRnd->byId($rid);
        if (false === $oRound) {
            return new \ObjectNotFoundError();
        }

        $oPosted = $this->getPostJson();
        $oUpdate = new \stdClass;
        foreach ($oPosted as $prop => $value) {
            switch ($prop) {
            case 'title':
                $oUpdate->title = $value;
                break;
            case 'purpose':
                $oUpdate->purpose = in_array($value, ['C', 'B', 'S']) ? $value : 'C';
                break;
            case 'state':
                //$oUpdate->state = (int)$value;
                break;
            case 'start_at':
            case 'end_at':
                $oUpdate->{$prop} = (int) $value;
                break;
            case 'mission_rid':
                $oUpdate->mission_rid = $value;
                break;
            }
        }

        if (count((array) $oUpdate) === 0) {
            return new \ResponseError('没有要更新的数据');
        }

        $aResult = $modelRnd->checkProperties($oUpdate, true);
        if (false === $aResult[0]) {
            return new \ResponseError($aResult[1]);
        }

        $modelRnd->update(
            'xxt_enroll_round',
            $oUpdate,
            ['aid' => $oApp->id, 'rid' => $rid]
        );

        $oRound = $modelRnd->byId($rid);

        return new \ResponseData($oRound);
    }
    /**
     * 删除轮次
     *
     * @param string $app
     * @param string $rid
     */
    public function remove_action($app, $rid) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        $modelRnd = $this->model('matter\enroll\round');
        $oRound = $modelRnd->byId($rid);
        if (false === $oRound || $oRound->state === '100') {
            return new \ObjectNotFoundError();
        }
        if (1 === $modelRnd->countByApp($oApp)) {
            return new \ResponseError('每个活动至少要保留一个填写时段');
        }

        $rst = $modelRnd->remove($oApp, $oRound);

        return new \ResponseData($rst);
    }
}