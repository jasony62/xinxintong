<?php
namespace matter;

require_once dirname(__FILE__) . '/base.php';
/**
 * 定时推送事件
 */
class timer_model extends base_model {
    /**
     *
     */
    protected function table() {
        return 'xxt_timer_task';
    }
    /*
     *
     */
    public function getTypeName() {
        return 'timer';
    }
    /**
     *
     */
    public function create($oMatter, $oConfig) {
        $oNewTimer = new \stdClass;
        $oNewTimer->matter_type = $oConfig->matter->type;
        $oNewTimer->matter_id = $oConfig->matter->id;
        $oNewTimer->siteid = $oMatter->siteid;
        $oNewTimer->enabled = 'N';

        $oNewTimer->task_model = $oConfig->task->model;
        !empty($oConfig->task->arguments) && $oNewTimer->task_arguments = $this->escape($this->toJson($oConfig->task->arguments));
        $oNewTimer->task_expire_at = isset($oNewTimer->expireAt) ? $oNewTimer->expireAt : 0;

        if (isset($oConfig->timer)) {
            isset($oConfig->timer->offset_matter_type) && $oNewTimer->offset_matter_type = $oConfig->timer->offset_matter_type;
            isset($oConfig->timer->offset_matter_id) && $oNewTimer->offset_matter_id = $oConfig->timer->offset_matter_id;
            $oNewTimer->offset_mode = isset($oConfig->timer->offset_mode) ? $oConfig->timer->offset_mode : 'AS';
            $oNewTimer->offset_hour = isset($oConfig->timer->offset_hour) ? $oConfig->timer->offset_hour : 0;
            $oNewTimer->offset_min = isset($oConfig->timer->offset_min) ? $oConfig->timer->offset_min : 0;
            isset($oConfig->timer->min) && $oNewTimer->min = $oConfig->timer->min;
            isset($oConfig->timer->hour) && $oNewTimer->hour = $oConfig->timer->hour;
            isset($oConfig->timer->mday) && $oNewTimer->mday = $oConfig->timer->mday;
            isset($oConfig->timer->mon) && $oNewTimer->mon = $oConfig->timer->mon;
            isset($oConfig->timer->wday) && $oNewTimer->wday = $oConfig->timer->wday;
            $oNewTimer->enabled = isset($oConfig->timer->enabled) ? $oConfig->timer->enabled : 'N';
        } else {
            $oNewTimer->pattern = 'W';
            $oNewTimer->mday = $oNewTimer->mon = $oNewTimer->wday = -1;
            $oNewTimer->min = 0;
            $oNewTimer->hour = 8;
            $oNewTimer->offset_matter_type = 'N'; // 固定时间，没有参照对象
            $oNewTimer->offset_mode = 'AS';
            $oNewTimer->offset_hour = 0;
            $oNewTimer->offset_min = 0;
        }
        $oNewTimer->notweekend = 'Y';

        switch ($oNewTimer->offset_matter_type) {
        case 'RC':
            foreach ($oMatter->roundCron as $oRule) {
                if ($oRule->id === $oNewTimer->offset_matter_id) {
                    $oReferCron = $oRule;
                    break;
                }
            }
            if (!isset($oReferCron)) {
                return [false, '定时任务的相对时间参照的【填写轮次生成规则】不存在'];
            }
            $oResult = $this->setTimeByRoundCron($oNewTimer, $oReferCron, false);
            if (false === $oResult[0]) {
                return $oResult;
            }
            tms_object_merge($oNewTimer, $oResult[1]);
            break;
        }

        $oNewTimer->id = $this->insert($this->table(), $oNewTimer, true);
        if (!empty($oNewTimer->task_arguments)) {
            $oNewTimer->task_arguments = $oConfig->task->arguments;
        }

        $oNewTimer->name = $this->readableTaskName($oNewTimer);

        return [true, $oNewTimer];
    }
    /**
     * 给指定活动复制任务
     */
    public function copy($oMatter, $oTimer) {
        $oNewTimer = new \stdClass;
        $oNewTimer->siteid = $oMatter->siteid;
        $oNewTimer->matter_id = $oMatter->id;
        $oNewTimer->matter_type = $oMatter->type;
        $oNewTimer->task_model = $oTimer->task_model;
        $oNewTimer->enabled = "N";

        $oNewTimer->hour = $oTimer->hour;
        $oNewTimer->mday = $oTimer->mday;
        $oNewTimer->min = $oTimer->min;
        $oNewTimer->mon = $oTimer->mon;
        $oNewTimer->notweekend = $oTimer->notweekend;

        $oNewTimer->offset_hour = $oTimer->offset_hour;
        $oNewTimer->offset_matter_id = $oTimer->offset_matter_id;
        $oNewTimer->offset_matter_type = $oTimer->offset_matter_type;
        $oNewTimer->offset_min = $oTimer->offset_min;
        $oNewTimer->offset_mode = $oTimer->offset_mode;
        $oNewTimer->pattern = $oTimer->pattern;

        $oNewTimer->task_arguments = $this->escape($this->toJson($oTimer->task_arguments));
        $oNewTimer->task_expire_at = $oTimer->task_expire_at;
        $oNewTimer->wday = $oTimer->wday;
        $oNewTimer->id = $this->insert($this->table(), $oNewTimer, true);

        $oNewTimer->task_arguments = json_decode($oNewTimer->task_arguments);
        $oNewTimer->name = $this->readableTaskName($oNewTimer);

        return [true, $oNewTimer];
    }
    /**
     * 获得素材下的定时任务
     */
    public function &byMatter($type, $id, $aOptions = []) {
        $q = [
            '*',
            'xxt_timer_task',
            ['matter_type' => $type, 'matter_id' => $id],
        ];
        if (!empty($aOptions['model'])) {
            $q[2]['task_model'] = $aOptions['model'];
        }
        if (isset($aOptions['taskArguments'])) {
            $taskArguments = $aOptions['taskArguments'];
            if (is_object($taskArguments) || is_array($taskArguments)) {
                $likeCauses = [];
                foreach ($taskArguments as $prop => $val) {
                    $likeCauses[] = 'task_arguments like \'%"' . $this->escape($prop) . '":' . $this->toJson($val) . '%\'';
                }
                $q[2]['task_arguments'] = (object) ['op' => 'and', 'pat' => $likeCauses];
            }
        }
        $tasks = $this->query_objs_ss($q);
        foreach ($tasks as $oTask) {
            if (property_exists($oTask, 'task_arguments')) {
                $oTask->task_arguments = empty($oTask->task_arguments) ? new \stdClass : json_decode($oTask->task_arguments);
            }
            $oTask->name = $this->readableTaskName($oTask);
        }

        return $tasks;
    }
    /**
     *
     */
    public function &bySite($site, $enabled = null) {
        $q = array(
            '*',
            'xxt_timer_task',
            ['siteid' => $site],
        );
        $enabled !== null && $q[2]['enabled'] = $enabled;

        !($timers = $this->query_objs_ss($q)) && $timers = array();

        return $timers;
    }
    /**
     * 获得当前时间段要执行的任务
     */
    public function tasksByTime() {
        $now = time();
        $min = (int) date('i'); // 0-59
        $hour = date('G'); // 0-23
        $mday = date('j'); // 1-31
        $wday = date('w'); // 0-6（周日到周一）
        $mon = date('n'); // 1-12

        $q = [
            '*',
            'xxt_timer_task',
            "enabled='Y' and (task_expire_at>={$now} or task_expire_at=0)",
        ];
        $q[2] .= " and (min=-1 or min=$min)";
        $q[2] .= " and (hour=-1 or hour=$hour)";
        $q[2] .= " and (mday=-1 or mday=$mday)";
        $q[2] .= " and (wday=-1 or wday=$wday)";
        $q[2] .= " and (mon=-1 or mon=$mon)";

        $schedules = $this->query_objs_ss($q);

        $tasks = [];
        foreach ($schedules as $oSchedule) {
            if (empty($oSchedule->task_model) || empty($oSchedule->matter_id) || empty($oSchedule->matter_type)) {
                continue;
            }
            if ($oSchedule->notweekend === 'Y') {
                if ($oSchedule->wday === '-1' && ($wday === '0' || $wday === '6')) {
                    continue;
                }
            }

            $oTask = $this->_scheduleToTask($oSchedule);

            $tasks[] = $oTask;
        }

        return $tasks;
    }
    /**
     * 生成执行任务
     */
    private function &_scheduleToTask($oSchedule) {
        $oTask = new \stdClass;
        $oTask->id = $oSchedule->id;
        $oTask->siteid = $oSchedule->siteid;
        $oTask->task_expire_at = $oSchedule->task_expire_at;

        $oTask->model = $this->model('matter\task\\' . $oSchedule->task_model);

        $oMatter = new \stdClass;
        $oMatter->id = $oSchedule->matter_id;
        $oMatter->type = $oSchedule->matter_type;
        $oTask->matter = $oMatter;

        if (!empty($oSchedule->task_arguments)) {
            $oTask->arguments = json_decode($oSchedule->task_arguments);
        }

        return $oTask;
    }
    /**
     * 根据素材的轮次定时生成规则，更新关联的定时任务
     */
    public function updateByRoundCron($oMatter) {
        if (empty($oMatter->roundCron)) {
            /* 更新所有和轮次规则关联的定时任务 */
            $this->update(
                $this->table(),
                ['enabled' => 'N', 'offset_matter_id' => ''],
                ['matter_type' => $oMatter->type, 'matter_id' => $oMatter->id, 'offset_matter_type' => 'RC']
            );
            return true;
        }
        /* 所有设置了和参考轮次规则的相对时间的任务 */
        $q = [
            '*',
            $this->table(),
            ['matter_type' => $oMatter->type, 'matter_id' => $oMatter->id, 'offset_matter_type' => 'RC'],
        ];
        $tasks = $this->query_objs_ss($q);
        if (empty($tasks)) {return true;}

        $oCronById = new \stdClass;
        foreach ($oMatter->roundCron as $oCron) {
            $oCronById->{$oCron->id} = $oCron;
        }

        foreach ($tasks as $oTask) {
            if (!empty($oTask->offset_matter_id) && !isset($oCronById->{$oTask->offset_matter_id})) {
                /* 参照的对象不存在 */
                $this->update(
                    $this->table(),
                    ['enabled' => 'N', 'offset_matter_id' => ''],
                    ['id' => $oTask->id]
                );
            } else {
                $oResult = $this->setTimeByRoundCron($oTask, $oCron);
                if (false === $oResult[0]) {
                    /* 无法设置有效的时间 */
                    $this->update(
                        $this->table(),
                        ['enabled' => 'N', 'offset_matter_id' => ''],
                        ['id' => $oTask->id]
                    );
                }
            }
        }

        return true;
    }
    /**
     * 根据轮次的定时规则设置任务的执行时间
     */
    public function setTimeByRoundCron($oTask, $oCron, $bPersit = true) {
        if (isset($oTask->offset_hour)) {
            $offset_days = floor($oTask->offset_hour / 24);
            $offset_hours = $oTask->offset_hour - ($offset_days * 24);
        } else {
            $offset_days = $offset_hours = 0;
        }

        $oNewUpdate = new \stdClass;
        switch ($oCron->period) {
        case 'D':
            $oNewUpdate->pattern = 'W';
            $oNewUpdate->wday = '-1';
            break;
        case 'W':
            $oNewUpdate->pattern = 'W';
            $oNewUpdate->wday = ($oCron->wday + $offset_days) % 7;
            break;
        case 'M':
            $oNewUpdate->pattern = 'M';
            $oNewUpdate->mday = $oCron->mday;
            break;
        }
        switch ($oTask->offset_mode) {
        case 'AS':
            $oNewUpdate->hour = (int) $oCron->hour + $offset_hours;
            if ($oNewUpdate->hour > 23) {
                return [false, '定时任务的相对时间【' . $oNewUpdate->hour . '】设置超出范围'];
            }
            $oNewUpdate->min = 0;
            break;
        case 'BE':
            if (!isset($oCron->end_hour) || strlen($oCron->end_hour) === 0) {
                return [false, '轮次生成规则没有指定结束时间，无法生成任务执行时间'];
            }
            $oNewUpdate->hour = (int) $oCron->end_hour - $offset_hours;
            if ($oNewUpdate->hour < 0) {
                return [false, '定时任务的相对时间【' . $oNewUpdate->hour . '】设置超出范围'];
            }
            $oNewUpdate->min = 0;
            break;
        }
        if ($bPersit) {
            $this->update($this->table(), $oNewUpdate, ['id' => $oTask->id]);
        }

        return [true, $oNewUpdate];
    }
    /**
     * 定时任务名称
     */
    public function readableTaskName($oTask) {
        $names = [];
        switch ($oTask->pattern) {
        case 'Y':
            if ($this->getDeepValue($oTask, 'mon', -1) >= 1) {
                $names[] = '每年' . $oTask->mon . '月';
            } else {
                $names[] = '每年';
                $names[] = '每月';
            }
            $names[] = $this->getDeepValue($oTask, 'mday', -1) >= 1 ? ('每月' . $oTask->mday) . '日' : '每日';
            break;
        case 'M':
            $names[] = $this->getDeepValue($oTask, 'mday', -1) >= 1 ? ('每月' . $oTask->mday) . '日' : '每日';
            break;
        case 'W':
            if (isset($oTask->wday) && $oTask->wday >= 0) {
                $names[] = '每周' . ['日', '一', '二', '三', '四', '五', '六'][$oTask->wday];
            } else {
                $names[] = '每天';
            }
            break;
        }
        // 小时
        $names[] = $this->getDeepValue($oTask, 'hour', -1) >= 0 ? ($oTask->hour . '点') : '每小时';
        // 分钟
        $names[] = $this->getDeepValue($oTask, 'min', -1) >= 0 ? ($oTask->min . '分') : '每分钟';

        return implode(',', $names);
    }
}
