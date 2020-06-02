<?php
namespace pl\fe\matter\signin;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 签到活动轮次控制器
 */
class round extends \pl\fe\matter\base {
    /**
     * 批量添加轮次
     *
     * @param string $app
     */
    public function batch_action($site, $app) {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelRnd = $this->model('matter\signin\round');
        $posted = $this->getPostJson();

        if ($posted->overwrite === 'Y') {
            /*删除已有的轮次*/
            $rst = $modelRnd->delete(
                'xxt_signin_round',
                ["aid" => $app]
            );
        }
        /*计算创建的数量*/
        $startAt = getdate($posted->start_at);
        $startDay = mktime(0, 0, 0, $startAt['mon'], $startAt['mday'], $startAt['year']);
        $first = [3600 * 7, 3600 * 5, 3600 * 2]; //第一个时间段，7：00-12：00，迟到：9：00
        $second = [3600 * 12 + 1800, 3600 * 4 + 1800, 5400]; //第二个时间段，12：30-17：00，迟到：14
        $roundStartAt = $startDay;

        /*创建轮次*/
        $i = 1; //轮次的编号
        $d = 0; //日期的编号
        $rounds = [];
        while ($roundStartAt < $posted->end_at) {
            $roundStartAt += $first[0];
            if ($roundStartAt >= $posted->start_at && $roundStartAt < $posted->end_at) {
                /* 创建新轮次 */
                $roundId = uniqid();
                $round = [
                    'siteid' => $site,
                    'aid' => $app,
                    'rid' => $roundId,
                    'creater' => $user->id,
                    'create_at' => time(),
                    'title' => isset($posted->title) ? $posted->title : "轮次{$i}",
                    'start_at' => $roundStartAt,
                    'end_at' => $roundStartAt + $first[1],
                    'late_at' => $roundStartAt + $first[2],
                ];
                $modelRnd->insert('xxt_signin_round', $round, false);

                $newRnd = $modelRnd->byId($roundId);

                $rounds[] = $newRnd;

                $i++;
            }
            if ($posted->timesOfDay == 2) {
                $roundStartAt = $roundStartAt - $first[0] + $second[0];
                if ($roundStartAt >= $posted->start_at && $roundStartAt < $posted->end_at) {
                    /* 创建新轮次 */
                    $roundId = uniqid();
                    $round = [
                        'siteid' => $site,
                        'aid' => $app,
                        'rid' => $roundId,
                        'creater' => $user->id,
                        'create_at' => time(),
                        'title' => isset($posted->title) ? $posted->title : "轮次{$i}",
                        'start_at' => $roundStartAt,
                        'end_at' => $roundStartAt + $second[1],
                        'late_at' => $roundStartAt + $second[2],
                    ];
                    $modelRnd->insert('xxt_signin_round', $round, false);

                    $newRnd = $modelRnd->byId($roundId);

                    $rounds[] = $newRnd;

                    $i++;
                }
            }
            $d++;
            $roundStartAt = $startDay + (86400 * $d);
        }

        return new \ResponseData($rounds);
    }
    /**
     * 添加轮次
     *
     * @param string $app
     */
    public function add_action($site, $app) {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $posted = $this->getPostJson();
        $modelRnd = $this->model('matter\signin\round');
        /* 创建新轮次 */
        $roundId = uniqid();
        $round = [
            'siteid' => $site,
            'aid' => $app,
            'rid' => $roundId,
            'creater' => $user->id,
            'create_at' => time(),
            'title' => isset($posted->title) ? $posted->title : '新轮次',
            'start_at' => isset($posted->start_at) ? $posted->start_at : 0,
            'end_at' => isset($posted->end_at) ? $posted->end_at : 0,
        ];
        $modelRnd->insert('xxt_signin_round', $round, false);

        $newRnd = $modelRnd->byId($roundId);

        return new \ResponseData($newRnd);
    }
    /**
     * 更新轮次
     *
     * @param string $app
     * @param string $rid
     */
    public function update_action($site, $app, $rid) {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelRnd = $this->model('matter\signin\round');
        $posted = $this->getPostJson();

        $rst = $modelRnd->update(
            'xxt_signin_round',
            $posted,
            ["aid" => $app, "rid" => $rid]
        );

        $newRnd = $modelRnd->byId($rid);

        return new \ResponseData($newRnd);
    }
    /**
     * 删除签到轮次
     * 如果轮次下已经有签到数据，不允许删除
     *
     * @param string $app
     * @param string $rid
     */
    public function remove_action($site, $app, $rid) {
        if (false === ($user = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $app = $this->model('matter\signin')->byId($app);
        if ($app === false) {
            return new \ResponseError('指定的签到活动不存在');
        }
        $modelRnd = $this->model('matter\signin\round');
        if (false === ($modelRnd->byId($rid))) {
            return new \ResponseError('指定的签到轮次不存在');
        }

        $modelRec = $this->model('matter\signin\record');
        $records = $modelRec->byApp($app, ['rid' => $rid]);
        if ($records->total > 0) {
            return new \ResponseError('已经有签到数据，不允许删除');
        }

        /* 删除轮次 */
        $rst = $modelRnd->delete(
            'xxt_signin_round',
            ["aid" => $app->id, "rid" => $rid]
        );

        return new \ResponseData($rst);
    }
}