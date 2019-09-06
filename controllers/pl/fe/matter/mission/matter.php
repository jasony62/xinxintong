<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class matter extends \pl\fe\matter\base {
    /**
     * 返回视图
     */
    public function index_action() {
        \TPL::output('/pl/fe/matter/mission/frame');
        exit;
    }
    /**
     * 获得任务下的素材
     *
     * @param int $id
     */
    public function list_action($id, $matterType = null, $verbose = 'Y') {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        /* 检查权限 */
        $modelAcl = $this->model('matter\mission\acl');
        if (false === ($acl = $modelAcl->byCoworker($id, $oUser->id))) {
            return new \ResponseError('数据不存在');
        }

        $oCriteria = $this->getPostJson();
        $aOptions = ['user' => $oUser];
        if (!empty($oCriteria->byTitle)) {
            $aOptions['byTitle'] = $oCriteria->byTitle;
        }
        if (!empty($oCriteria->byTime)) {
            $aOptions['byTime'] = $oCriteria->byTime;
        }
        if (!empty($oCriteria->byScenario)) {
            $aOptions['byScenario'] = $oCriteria->byScenario;
        }
        if (!empty($oCriteria->byStar)) {
            $aOptions['byStar'] = $oCriteria->byStar;
        }

        if (!empty($matterType)) {
            if ($matterType === 'doc') {
                $matterType = ['article', 'link', 'channel'];
            } else if ($matterType === 'app') {
                $matterType = ['enroll', 'signin', 'group', 'memberschema'];
            }
        }

        $modelMM = $this->model('matter\mission\matter');
        $matters = $modelMM->byMission($id, $matterType, $aOptions, $verbose);
        foreach ($matters as $oMatter) {
            /* 是否已经星标 */
            $qStar = [
                'id',
                'xxt_account_topmatter',
                ['matter_id' => $oMatter->id, 'matter_type' => $oMatter->type, 'userid' => $oUser->id],
            ];
            if ($oStar = $modelMM->query_obj_ss($qStar)) {
                $oMatter->star = $oStar->id;
            }
        }

        return new \ResponseData($matters);
    }
    /**
     * 获得任务下素材的数量
     *
     * @param int $id mission'is
     */
    public function count_action($id) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        /* 检查权限 */
        $modelAcl = $this->model('matter\mission\acl');
        if (false === ($acl = $modelAcl->byCoworker($id, $oUser->id))) {
            return new \ResponseError('项目不存在或没有访问项目的权限');
        }

        $count = $this->model('matter\mission\matter')->count($id);

        return new \ResponseData($count);
    }
    /**
     * 给项目添加素材
     *
     * @param string $site
     * @param int $id
     */
    public function add_action($site, $id) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $matter = $this->getPostJson();

        $modelMis = $this->model('matter\mission');
        if ($matter->type === 'enroll') {
            $app = $this->model('matter\enroll')->byId($matter->id, ['fields' => 'siteid,id,title,scenario,start_at,end_at', 'cascaded' => 'N']);
        } else {
            $app = $this->model('matter\\' . $matter->type)->byId($matter->id, ['fields' => 'siteid,id,title', 'cascaded' => 'N']);
        }
        if (!$app) {
            return new \ObjectNotFoundError();
        }

        $modelMis->addMatter($oUser, $site, $id, $app);

        $mission = $modelMis->byId($id);

        return new \ResponseData($mission);
    }
    /**
     * 更新素材设置
     *
     * @param int $id mission'id
     */
    public function update_action($id, $matterType, $matterId) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $posted = $this->getPostJson();

        $updated = [];
        foreach ($posted as $k => $v) {
            if ($k === 'is_public') {
                $updated['is_public'] = $v === 'Y' ? 'Y' : 'N';
            }
        }

        $model = $this->model();
        $rst = $model->update(
            'xxt_mission_matter',
            $updated,
            ['mission_id' => $id, 'matter_type' => $matterType, 'matter_id' => $matterId]
        );

        return new \ResponseData($rst);
    }
    /**
     * 更新素材设置
     *
     * @param int $id mission'id
     */
    public function updateSeq_action($id) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $posted = $this->getPostJson();

        if (count($posted)) {
            $model = $this->model();
            foreach ($posted as $index => $id) {
                $rst = $model->update(
                    'xxt_mission_matter',
                    ['seq' => $index],
                    ['id' => $id]
                );
            }
        }

        return new \ResponseData($rst);
    }
    /**
     * 删除项目下的素材（这个方法有问题，对应的素材不更新吗？）
     *
     * @param int $id
     */
    public function remove_action($id) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $oMatter = $this->getPostJson();

        $modelMis = $this->model('matter\mission');
        $rst = $modelMis->removeMatter($id, $oMatter);

        return new \ResponseData($rst);
    }
}