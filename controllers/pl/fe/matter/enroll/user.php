<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 记录活动用户
 */
class user extends main_base {
    /**
     * 返回提交过填写记录的用户列表
     */
    public function enrollee_action($app, $page = 1, $size = 30) {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $modelEnl = $this->model('matter\enroll');
        $oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }
        $modelUsr = $this->model('matter\enroll\user');
        $oPost = $this->getPostJson();

        $aOptions = [];
        !empty($oPost->orderby) && $aOptions['orderby'] = $oPost->orderby;
        !empty($oPost->byGroup) && $aOptions['byGroup'] = $oPost->byGroup;
        !empty($oPost->rids) && $aOptions['rid'] = $oPost->rids;
        !empty($oPost->onlyEnrolled) && $aOptions['onlyEnrolled'] = $oPost->onlyEnrolled;
        if (!empty($oPost->filter->by) && !empty($oPost->filter->keyword)) {
            $aOptions[$oPost->filter->by] = $oPost->filter->keyword;
        }

        $oResult = $modelUsr->enrolleeByApp($oApp, $page, $size, $aOptions);

        return new \ResponseData($oResult);
    }
    /**
     * 未完成任务用户列表
     */
    public function undone_action($app, $rid = '') {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $modelEnl = $this->model('matter\enroll');
        $oApp = $modelEnl->byId($app, ['cascaded' => 'N', 'fields' => 'siteid,id,state,mission_id,entry_rule,action_rule,absent_cause']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        $modelUsr = $this->model('matter\enroll\user');

        $oPosted = $this->getPostJson();
        if (empty($oPosted->rids)) {
            $oResult = $modelUsr->undoneByApp($oApp, 'ALL');
        } else {
            $aRounds = [];
            $aUsers = [];
            $modelRnd = $this->model('matter\enroll\round');
            foreach ($oPosted->rids as $rid) {
                $oRnd = $modelRnd->byId($rid, ['fields' => 'title,start_at']);
                if ($oRnd) {
                    $oRnd->rid = $rid;
                    $aRounds[] = $oRnd;
                    $oResult = $modelUsr->undoneByApp($oApp, $rid);
                    if (!empty($oResult->users)) {
                        foreach ($oResult->users as $oUser) {
                            if (!isset($aUsers[$oUser->userid])) {
                                /* 清除不必要的数据 */
                                unset($oUser->groupid);
                                unset($oUser->uid);
                                $aUsers[$oUser->userid] = $oUser;
                            }
                            $aUsers[$oUser->userid]->rounds[] = $rid;
                            $aUsers[$oUser->userid]->undones[] = $oUser->undoneTasks;
                            unset($oUser->undoneTasks);
                        }
                    }
                }
            }
            $oResult = new \stdClass;
            $oResult->users = array_values($aUsers);
            usort($aRounds, function ($a, $b) {
                return $a->start_at > $b->start_at ? 1 : -1;
            });
            $oResult->rounds = $aRounds;
        }

        return new \ResponseData($oResult);
    }
    /**
     * 根据通讯录返回用户完成情况
     */
    public function byMschema_action($app, $mschema, $rid = '', $page = 1, $size = 30) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelEnl = $this->model('matter\enroll');
        $oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        $modelMs = $this->model('site\user\memberschema');
        $oMschema = $modelMs->byId($mschema, ['cascaded' => 'N']);
        if (false === $oMschema) {
            return new \ObjectNotFoundError();
        }

        $modelUsr = $this->model('matter\enroll\user');
        $options = [];
        !empty($rid) && $options['rid'] = $rid;
        $oResult = $modelUsr->enrolleeByMschema($oApp, $oMschema, $page, $size, $options);
        /*查询有openid的用户发送消息的情况*/
        if (count($oResult->members)) {
            foreach ($oResult->members as $member) {
                $q = [
                    'd.tmplmsg_id,d.status,b.create_at',
                    'xxt_log_tmplmsg_detail d,xxt_log_tmplmsg_batch b',
                    "d.userid = '{$member->userid}' and d.batch_id = b.id and b.send_from = 'enroll:" . $oApp->id . "'",
                ];
                $q2 = [
                    'r' => ['o' => 0, 'l' => 1],
                    'o' => 'b.create_at desc',
                ];
                if ($tmplmsg = $modelUsr->query_objs_ss($q, $q2)) {
                    $member->tmplmsg = $tmplmsg[0];
                } else {
                    $member->tmplmsg = new \stdClass;
                }
            }
        }

        return new \ResponseData($oResult);
    }
    /**
     * 发表过留言的用户
     */
    public function remarker_action($app, $page = 1, $size = 30) {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        $modelEnl = $this->model('matter\enroll');
        $oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        $modelUsr = $this->model('matter\enroll\user');
        $oResult = $modelUsr->remarkerByApp($oApp, $page, $size);

        return new \ResponseData($oResult);
    }
    /**
     * 数据导出
     */
    public function export_action($app, $rids = '') {
        if (false === $this->accountUser()) {
            return new \ResponseTimeout();
        }

        // 记录活动
        if (false === ($oApp = $this->model('matter\enroll')->byId($app, ['fields' => 'siteid,id,title,entry_rule,data_schemas,absent_cause', 'cascaded' => 'N']))) {
            return new \ParameterError();
        }

        $modelUsr = $this->model('matter\enroll\user');

        $rids = empty($rids) ? [] : explode(',', $rids);

        $aOptions = [];
        $aOptions['rid'] = $rids;
        $oResult = $modelUsr->enrolleeByApp($oApp, $page = '', $size = '', $aOptions);

        // 判断关联公众号
        $road = ['wx', 'qy'];
        $sns = new \stdClass;
        foreach ($road as $v) {
            $arr = array();
            $config = $modelUsr->query_obj_ss(['joined', 'xxt_site_' . $v, ['siteid' => $oApp->siteid]]);
            if (!empty($config->joined)) {
                $arr['joined'] = $config->joined;
                $sns->{$v} = (object) $arr;
            }
        }

        require_once TMS_APP_DIR . '/lib/PHPExcel.php';
        // Create new PHPExcel object
        $objPHPExcel = new \PHPExcel();
        // Set properties
        $objPHPExcel->getProperties()->setCreator(APP_TITLE)
            ->setLastModifiedBy(APP_TITLE)
            ->setTitle($oApp->title)
            ->setSubject($oApp->title)
            ->setDescription($oApp->title);

        $objPHPExcel->setActiveSheetIndex(0);
        $objActiveSheet = $objPHPExcel->getActiveSheet();
        $objActiveSheet->setTitle('已参与');
        $columnNum1 = 0; //列号
        $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '序号');
        // 转换标题
        $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '用户');
        if (!empty($oApp->entryRule->group->id)) {
            $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '分组');
        }
        $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '总访问次数');
        $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '总访问时长');
        $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '记录');
        $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '留言');
        $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '点赞');
        $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '获得推荐');
        $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '积分');
        $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '得分');
        if (isset($sns->wx->joined) && $sns->wx->joined === 'Y') {
            $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '已关联微信');
        }
        if (isset($sns->qy->joined) && $sns->qy->joined === 'Y') {
            $objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '已关联微企');
        }

        // 转换数据
        for ($j = 0; $j < count($oResult->users); $j++) {
            $oUser = $oResult->users[$j];
            $rowIndex = $j + 2;
            $columnNum2 = 0; //列号

            $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $j + 1);
            $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->nickname);
            if (!empty($oApp->entryRule->group->id)) {
                $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, empty($oUser->group) ? '' : $oUser->group->title);
            }
            $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->entry_num);
            $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, date('y-m-j H:i', $oUser->last_entry_at));
            $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->enroll_num);
            $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->do_remark_num);
            $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->do_like_num);
            $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->agree_num);
            $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->user_total_coin);
            $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, $oUser->score);
            if (isset($sns->wx->joined) && $sns->wx->joined === 'Y') {
                $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, !empty($oUser->wx_openid) ? "是" : '');
            }
            if (isset($sns->qy->joined) && $sns->qy->joined === 'Y') {
                $objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $rowIndex, !empty($oUser->qy_openid) ? "是" : '');
            }
        }

        /* 未完成活动任务用户 */
        $aRounds = [];
        $aUsers = [];
        $modelRnd = $this->model('matter\enroll\round');
        foreach ($rids as $rid) {
            $oRnd = $modelRnd->byId($rid, ['fields' => 'title,start_at']);
            if ($oRnd) {
                $oRnd->rid = $rid;
                $aRounds[] = $oRnd;
                $oResult = $modelUsr->undoneByApp($oApp, $rid);
                if (!empty($oResult->users)) {
                    foreach ($oResult->users as $oUser) {
                        if (!isset($aUsers[$oUser->userid])) {
                            /* 清除不必要的数据 */
                            unset($oUser->groupid);
                            unset($oUser->uid);
                            $aUsers[$oUser->userid] = $oUser;
                        }
                        $aUsers[$oUser->userid]->rounds[] = $rid;
                        $aUsers[$oUser->userid]->undones[] = $oUser->undoneTasks;
                        unset($oUser->undoneTasks);
                    }
                }
            }
        }

        if (count($aUsers)) {
            $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex(1);
            $objActiveSheet2 = $objPHPExcel->getActiveSheet();
            $objActiveSheet2->setTitle('缺席');

            $colNumber = 0;
            $objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '序号');
            $objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '姓名');
            $objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '分组');
            foreach ($aRounds as $oRnd) {
                $objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, $oRnd->title);
            }
            $objActiveSheet2->setCellValueByColumnAndRow($colNumber++, 1, '备注');

            $rowNumber = 2;
            foreach ($aUsers as $oUndoneUser) {
                $colNumber = 0;
                $objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, $rowNumber - 1);
                $objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, $oUndoneUser->nickname);
                $objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($oUndoneUser->group->title) ? $oUndoneUser->group->title : '');
                foreach ($aRounds as $oRnd) {
                    $objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, in_array($oRnd->rid, $oUndoneUser->rounds) ? '是' : '');
                }
                $objActiveSheet2->setCellValueByColumnAndRow($colNumber++, $rowNumber, isset($oUndoneUser->absent_cause->cause) ? $oUndoneUser->absent_cause->cause : '');

                $rowNumber++;
            }
            $objPHPExcel->setActiveSheetIndex(0);
        }

        // 输出
        header('Content-Type: application/vnd.ms-excel');
        header('Cache-Control: max-age=0');

        $filename = $oApp->title . '.xlsx';
        $ua = $_SERVER["HTTP_USER_AGENT"];
        if (preg_match("/MSIE/", $ua) || preg_match("/Trident\/7.0/", $ua)) {
            $encoded_filename = urlencode($filename);
            $encoded_filename = str_replace("+", "%20", $encoded_filename);
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } else if (preg_match("/Firefox/", $ua)) {
            header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
    /**
     * 根据用户的填写记录更新用户数据
     */
    public function repair_action($app, $rid = '', $onlyCheck = 'Y') {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelEnl = $this->model('matter\enroll');
        $oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        $modelUsr = $this->model('matter\enroll\user');
        $aUpdatedResult = $modelUsr->renew($oApp, $rid, $onlyCheck);

        return new \ResponseData($aUpdatedResult);
    }
    /**
     * 更新用户对应的分组信息
     */
    public function repairGroup_action($app) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelEnl = $this->model('matter\enroll');
        $oApp = $modelEnl->byId($app, ['cascaded' => 'N']);
        if (false === $oApp) {
            return new \ObjectNotFoundError();
        }

        if (!isset($oApp->entryRule->group->id)) {
            return new \ResponseError('没有指定关联的分组活动');
        }

        $updatedCount = $this->model('matter\enroll\user')->repairGroup($oApp);

        return new \ResponseData($updatedCount);
    }
}