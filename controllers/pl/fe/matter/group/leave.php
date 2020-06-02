<?php
namespace pl\fe\matter\group;

require_once dirname(__FILE__) . '/main_base.php';
/**
 * 请假
 */
class leave extends main_base {
    /**
     * 指定需要作为事物管理的方法
     */
    public function tmsRequireTransaction() {
        return [
            'create',
            'update',
            'close',
        ];
    }
    /**
     *
     */
    public function create_action($ek) {
        $modelGrpRec = $this->model('matter\group\record');
        $oGrpRec = $modelGrpRec->byIdInApp($this->app->id, $ek);
        if (false === $oGrpRec || $oGrpRec->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $oPosted = $this->getPostJson();

        $modelGrpLev = $this->model('matter\group\leave');
        $oNewLeave = $modelGrpLev->add($this->app, $oGrpRec, $oPosted);
        $oNewLeave->team = (object) ['team_id' => $oGrpRec->team_id, 'title' => $oGrpRec->team_title];

        /* 记录操作日志 */
        $this->model('matter\log')->matterOp($this->app->siteid, $this->user, $this->app, 'leave.create', (object) ['id' => $oNewLeave->id]);

        return new \ResponseData($oNewLeave);
    }
    /**
     *
     */
    public function update_action($id) {
        $modelGrpLev = $this->model('matter\group\leave');
        $oLeave = $modelGrpLev->byId($id);
        if (false === $oLeave) {
            return new \ObjectNotFoundError();
        }

        $oPosted = $this->getPostJson();
        $modelGrpLev = $this->model('matter\group\leave');
        if ($modelGrpLev->modify2($oLeave->id, $oPosted)) {
            $oLeave = $modelGrpLev->byId($id);
        }

        /* 记录操作日志 */
        $this->model('matter\log')->matterOp($this->app->siteid, $this->user, $this->app, 'leave.update', (object) ['id' => $oLeave->id]);

        return new \ResponseData($oLeave);
    }
    /**
     *
     */
    public function list_action() {
        $modelGrpLev = $this->model('matter\group\leave');
        $leaves = $modelGrpLev->byApp($this->app->id);
        if (count($leaves)) {
            $modelGrpRec = $this->model('matter\group\record');
            foreach ($leaves as $oLeave) {
                $oRecs = $modelGrpRec->byUser($this->app, $oLeave->userid, ['fields' => 'team_id,team_title']);
                if (count($oRecs)) {
                    $oRec = $oRecs[0];
                    $oLeave->team = (object) ['team_id' => $oRec->team_id, 'title' => $oRec->team_title];
                }
            }
        }

        return new \ResponseData($leaves);
    }
    /**
     *
     */
    public function close_action($id) {
        $modelGrpLev = $this->model('matter\group\leave');
        $oLeave = $modelGrpLev->byId($id);
        if (false === $oLeave) {
            return new \ObjectNotFoundError();
        }
        $ret = $modelGrpLev->close($oLeave->id);

        /* 记录操作日志 */
        $this->model('matter\log')->matterOp($this->app->siteid, $this->user, $this->app, 'leave.close', (object) ['id' => $oLeave->id]);

        return new \ResponseData($ret);
    }
}