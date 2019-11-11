<?php
namespace pl\fe\matter;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 定时任务
 */
class timer extends \pl\fe\base {

    public function get_access_rule() {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';

        return $rule_action;
    }
    /**
     * 在调用每个控制器的方法前调用
     */
    public function tmsBeforeEach() {
        // 要求登录用户操作
        if (false === ($oUser = $this->accountUser())) {
            return [false, new \ResponseTimeout()];
        }
        $this->user = $oUser;
        return [true];
    }
    /**
     *
     */
    public function get_action($task) {
        $modelTim = $this->model('matter\timer');
        $oTask = $modelTim->byId($task);

        return new \ResponseData($oTask);
    }
    /**
     * 素材指定的定时任务
     */
    public function byMatter_action($type, $id, $model = '') {
        $aOptions = ['model' => $model];

        $oPosted = $this->getPostJson();
        if ($oPosted) {
            if (isset($oPosted->taskArguments)) {
                $aOptions['taskArguments'] = $oPosted->taskArguments;
            }
        }

        $modelTim = $this->model('matter\timer');
        $tasks = $modelTim->byMatter($type, $id, $aOptions);

        return new \ResponseData($tasks);
    }
    /**
     * 给指定素材添加定时任务
     */
    public function create_action() {
        $oConfig = $this->getPostJson();

        if (empty($oConfig->matter->type) || empty($oConfig->matter->id)) {
            return new \ParameterError();
        }
        $oMatter = $this->model('matter\\' . $oConfig->matter->type)->byId($oConfig->matter->id, ['fields' => 'id,siteid,round_cron', 'cascaded' => 'N']);
        if (false === $oMatter) {
            return new \ObjectNotFoundError();
        }

        $modelTim = $this->model('matter\timer');
        $oCreateResult = $modelTim->create($oMatter, $oConfig);
        if (false === $oCreateResult[0]) {
            return new ResponseError($oCreateResult[1]);
        }

        $oNewTimer = $oCreateResult[1];

        /*记录操作日志*/
        $this->model('matter\log')->matterOp($oNewTimer->siteid, $this->user, $oConfig->matter, 'C-timer', $oNewTimer);

        return new \ResponseData($oNewTimer);
    }
    /**
     * 更新定时任务属性信息
     *
     * @param int $id 任务ID
     */
    public function update_action($id) {
        $modelTim = $this->model('matter\timer');
        $oBeforeTimer = $modelTim->byId($id);
        if (false === $oBeforeTimer) {
            return new \ObjectNotFoundError();
        }

        $oNewUpdate = $this->getPostJson();

        if (empty($oNewUpdate->offset_matter_type) || !in_array($oNewUpdate->offset_matter_type, ['N', 'RC'])) {
            return new \ParameterError('没有指定定时任务的相对时间模式');
        }
        if ($oNewUpdate->offset_matter_type === 'N' && empty($oNewUpdate->pattern)) {
            return new \ParameterError('没有指定定时任务时间周期');
        }
        if ($oNewUpdate->offset_matter_type === 'N' && empty($oNewUpdate->task_expire_at)) {
            return new \ParameterError('没有指定定时任务的【终止时间】');
        }

        switch ($oNewUpdate->offset_matter_type) {
        case 'N': // 固定时间
            $pattern = $oNewUpdate->pattern;
            /* 时间规则 */
            switch ($pattern) {
            case 'Y': // year
                $oNewUpdate->wday = -1;
                break;
            case 'M': // month
                $oNewUpdate->mon = -1;
                $oNewUpdate->wday = -1;
                break;
            case 'W': // week
                $oNewUpdate->mon = -1;
                $oNewUpdate->mday = -1;
                break;
            default:
                return new \ParameterError('指定了不支持的定时任务时间周期【' . $pattern . '】');
            }
            break;
        case 'RC': // 相对轮次规则的时间
            if (empty($oNewUpdate->offset_matter_id)) {
                return new \ParameterError('没有指定定时任务的相对时间参照的【填写轮次生成规则】');
            }
            if (empty($oNewUpdate->offset_mode) || !in_array($oNewUpdate->offset_mode, ['AS', 'BE'])) {
                return new \ParameterError('没有指定定时任务的相对时间的参照模式');
            }
            $oMatter = $this->model('matter\\' . $oBeforeTimer->matter_type)->byId($oBeforeTimer->matter_id);
            if (false === $oMatter || empty($oMatter->roundCron)) {
                return new \ParameterError('定时任务的相对时间参照的【填写轮次生成规则】不存在');
            }
            foreach ($oMatter->roundCron as $oRule) {
                if ($oRule->id === $oNewUpdate->offset_matter_id) {
                    $oReferCron = $oRule;
                    break;
                }
            }
            if (!isset($oReferCron)) {
                return new \ParameterError('定时任务的相对时间参照的【填写轮次生成规则】不存在');
            }
            $oResult = $modelTim->setTimeByRoundCron($oNewUpdate, $oReferCron, false);
            if (false === $oResult[0]) {
                return new \ParameterError($oResult[1]);
            }
            foreach ($oResult[1] as $prop => $val) {
                $oNewUpdate->{$prop} = $val;
            }
            break;
        }
        if (isset($oNewUpdate->task_arguments)) {
            $oTaskArguments = $oNewUpdate->task_arguments;
            if (is_object($oNewUpdate->task_arguments)) {
                $oNewUpdate->task_arguments = $modelTim->escape($modelTim->toJson($oNewUpdate->task_arguments));
            }
            $oNewUpdate->task_arguments = $oNewUpdate->task_arguments;
        }

        $rst = $modelTim->update(
            'xxt_timer_task',
            $oNewUpdate,
            ['id' => $id]
        );

        if (isset($oTaskArguments)) {
            $oNewUpdate->task_arguments = $oTaskArguments;
        }

        $oNewUpdate->name = $modelTim->readableTaskName($oNewUpdate);

        /*记录操作日志*/
        $oMatter = new \stdClass;
        $oMatter->id = $oBeforeTimer->matter_id;
        $oMatter->type = $oBeforeTimer->matter_type;
        $this->model('matter\log')->matterOp($oBeforeTimer->siteid, $this->user, $oMatter, 'U-timer', $oNewUpdate);

        return new \ResponseData($oNewUpdate);
    }
    /**
     * 删除定时任务
     *
     * @param int $id 任务ID
     */
    public function remove_action($id) {
        $modelTim = $this->model('matter\timer');
        $oBeforeTimer = $modelTim->byId($id);
        if (false === $oBeforeTimer) {
            return new \ObjectNotFoundError();
        }

        $rsp = $this->model()->delete('xxt_timer_task', ['id' => $oBeforeTimer->id]);

        /*记录操作日志*/
        $oMatter = new \stdClass;
        $oMatter->id = $oBeforeTimer->matter_id;
        $oMatter->type = $oBeforeTimer->matter_type;
        $this->model('matter\log')->matterOp($oBeforeTimer->siteid, $this->user, $oMatter, 'D-timer');

        return new \ResponseData($rsp);
    }
}
