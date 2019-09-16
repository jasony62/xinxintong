<?php
namespace pl\fe\matter\group;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 分组活动主控制器
 */
class main extends main_base {
    /**
     * 返回一个分组活动
     */
    public function get_action() {
        $oApp = $this->app;
        /*所属项目*/
        if ($oApp->mission_id) {
            $oApp->mission = $this->model('matter\mission')->byId($oApp->mission_id);
        }
        /*关联应用*/
        if (!empty($oApp->source_app)) {
            $sourceApp = json_decode($oApp->source_app);
            if ($sourceApp->type === 'mschema') {
                $oApp->sourceApp = $this->model('site\user\memberschema')->byId($sourceApp->id);
                $oApp->sourceApp->type = 'mschema';
            } else {
                $options = ['cascaded' => 'N', 'fields' => 'siteid,id,title'];
                if (in_array($sourceApp->type, ['enroll', 'signin'])) {
                    $options['fields'] .= ',assigned_nickname';
                }
                $oApp->sourceApp = $this->model('matter\\' . $sourceApp->type)->byId($sourceApp->id, $options);
            }
        }

        return new \ResponseData($oApp);
    }
    /**
     * 返回分组活动列表
     */
    public function list_action($site = null, $mission = null, $page = 1, $size = 30, $cascaded = 'N') {
        $oPosted = $this->getPostJson();
        $modelGrp = $this->model('matter\group');
        $q = [
            "*",
            'xxt_group g',
            "state<>0",
        ];
        if (!empty($oPosted->byTitle)) {
            $q[2] .= " and title like '%" . $oPosted->byTitle . "%'";
        }
        if (!empty($oPosted->byCreator)) {
            $q[2] .= " and creater_name like '%" . $oPosted->byCreator . "%'";
        }
        if (!empty($oPosted->byTags)) {
            foreach ($oPosted->byTags as $tag) {
                $q[2] .= " and matter_mg_tag like '%" . $tag->id . "%'";
            }
        }
        if (empty($mission)) {
            $site = $modelGrp->escape($site);
            $q[2] .= " and siteid='$site'";
        } else {
            $mission = $modelGrp->escape($mission);
            $q[2] .= " and mission_id='$mission'";
        }
        if (isset($oPosted->byStar) && $oPosted->byStar === 'Y') {
            $q[2] .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='group' and t.matter_id=g.id and userid='{$this->user->id}')";
        }

        $q2['o'] = 'modify_at desc';
        $q2['r']['o'] = ($page - 1) * $size;
        $q2['r']['l'] = $size;

        $aResult = ['apps' => null, 'total' => 0];

        if ($apps = $modelGrp->query_objs_ss($q, $q2)) {
            $modelGrpTeam = $this->model('matter\group\team');
            foreach ($apps as &$oApp) {
                $oApp->type = 'group';
                if ($cascaded === 'Y') {
                    $teams = $modelGrpTeam->byApp($oApp->id);
                    $oApp->teams = $teams;
                }
            }
            $aResult['apps'] = $apps;
        }
        if ($page == 1) {
            $aResult['total'] = count($apps) > 0 ? count($apps) : 0;
        } else {
            $q[0] = 'count(*)';
            $total = (int) $modelGrp->query_val_ss($q);
            $aResult['total'] = $total;
        }

        return new \ResponseData($aResult);
    }
    /**
     * 创建分组活动
     *
     * @param string $site
     * @param string $missioon
     * @param string $scenario
     */
    public function create_action($site, $mission = null, $scenario = 'split') {
        $oSite = $this->model('site')->byId($site, array('fields' => 'id,heading_pic'));
        if (false === $oSite) {
            return new \ObjectNotFoundError();
        }
        if (!empty($mission)) {
            $modelMis = $this->model('matter\mission');
            $oMission = $modelMis->byId($mission);
            if (false === $oMission) {
                return new \ObjectNotFoundError();
            }
        } else {
            $oMission = null;
        }

        $oCustomConfig = $this->getPostJson();
        $modelApp = $this->model('matter\group')->setOnlyWriteDbConn(true);

        $oNewApp = $modelApp->createByConfig($this->user, $oSite, $oCustomConfig, $oMission, $scenario);

        return new \ResponseData($oNewApp);
    }
    /**
     *
     * 复制一个记录活动
     *
     * @param string $site
     * @param int $mission
     *
     */
    public function copy_action($site, $mission = null) {
        $oUser = $this->user;
        $oCopied = $this->app;

        $modelApp = $this->model('matter\group')->setOnlyWriteDbConn(true);
        $current = time();
        $modelCode = $this->model('code\page');
        /**
         * 获得的基本信息
         */
        $oNewApp = new \stdClass;
        $oNewApp->siteid = $site;
        $oNewApp->title = $modelApp->escape($oCopied->title) . '（副本）';
        $oNewApp->pic = $oCopied->pic;
        $oNewApp->summary = $modelApp->escape($oCopied->summary);
        $oNewApp->scenario = $oCopied->scenario;
        $oNewApp->data_schemas = $modelApp->escape($modelApp->toJson($oCopied->dataSchemas));
        $oNewApp->group_rule = $modelApp->escape($oCopied->group_rule);
        if (!empty($mission)) {
            $oNewApp->mission_id = $mission;
        }
        $oNewApp = $modelApp->create($oUser, $oNewApp);

        /* 记录操作日志 */
        $this->model('matter\log')->matterOp($site, $oUser, $oNewApp, 'C', (object) ['id' => $oCopied->id, 'title' => $oCopied->title]);

        return new \ResponseData($oNewApp);
    }
    /**
     * 更新活动的属性信息
     */
    public function update_action() {
        $oOpUser = $this->user;
        $oMatter = $this->app;
        /**
         * 处理数据
         */
        $oUpdated = $this->getPostJson();
        foreach ($oUpdated as $n => $v) {
            $oMatter->{$n} = $v;
        }

        $modelApp = $this->model('matter\group')->setOnlyWriteDbConn(true);
        if ($oMatter = $modelApp->modify($oOpUser, $oMatter, $oUpdated)) {
            $this->model('matter\log')->matterOp($oMatter->siteid, $oOpUser, $oMatter, 'U');
        }

        return new \ResponseData($oMatter);
    }
    /**
     * 更新分组规则
     */
    public function configRule_action() {
        $oApp = $this->app;

        $modelGrpTeam = $this->model('matter\group\team');
        // 清除现有分组结果
        $modelGrpTeam->update('xxt_group_record', ['team_id' => '', 'team_title' => ''], ['aid' => $oApp->id]);
        // 清除原有的规则
        $modelGrpTeam->delete(
            'xxt_group_team',
            ["aid" => $oApp->id]
        );

        $targets = [];
        $rule = $this->getPostJson();
        if (!empty($rule->schemas)) {
            /*create targets*/
            $schemasForGroup = new \stdClass;
            foreach ($oApp->dataSchemas as $oSchema) {
                if (in_array($oSchema->id, $rule->schemas) && !empty($oSchema->ops)) {
                    foreach ($oSchema->ops as $op) {
                        $target = new \stdClass;
                        $target->{$oSchema->id} = $op->v;
                        $targets[] = $target;
                    }
                }
            }
        }

        $rounds = [];
        /*create round*/
        if (isset($rule->count)) {
            for ($i = 0; $i < $rule->count; $i++) {
                $prototype = [
                    'title' => '分组' . ($i + 1),
                    'targets' => $targets,
                    'times' => $rule->times,
                ];
                $round = $modelGrpTeam->create($oApp->id, $prototype);
                $round->targets = json_decode($round->targets);
                $rounds[] = $round;
            }
        }
        // 记录规则
        $rst = $modelGrpTeam->update(
            'xxt_group',
            ['group_rule' => $modelGrpTeam->toJson($rule)],
            ["id" => $oApp->id]
        );

        return new \ResponseData($rounds);
    }
    /**
     * 删除一个活动
     */
    public function remove_action() {
        $oOpUser = $this->user;
        $oApp = $this->app;
        if ($oApp->creater !== $oOpUser->id) {
            return new \ResponseError('没有删除数据的权限');
        }

        $modelGrp = $this->model('matter\group');
        $q = [
            'count(*)',
            'xxt_group_record',
            ["aid" => $oApp->id],
        ];
        if ((int) $modelGrp->query_val_ss($q) > 0) {
            $rst = $modelGrp->remove($oOpUser, $oApp, 'Recycle');
        } else {
            $modelGrp->delete(
                'xxt_group_team',
                ["aid" => $oApp->id]
            );
            $rst = $modelGrp->remove($oOpUser, $oApp, 'D');
        }

        return new \ResponseData($rst);
    }
    /**
     * 进行分组
     */
    public function execute_action($app) {
        $modelGrp = $this->model('matter\group');
        /* 执行分组 */
        $winners = $modelGrp->execute($app);
        if ($winners[0] === false) {
            return new \ResponseError($winners[1]);
        }

        return new \ResponseData($winners[1]);
    }
}