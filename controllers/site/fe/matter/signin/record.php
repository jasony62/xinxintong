<?php
namespace site\fe\matter\signin;

include_once dirname(__FILE__) . '/base.php';
/**
 * 签到活动记录
 */
class record extends base {
    /**
     * 提交登记数据并签到
     *
     * 执行签到，在每个轮次上只能进行一次签到，第一次签到后再提交也不会更改签到时间等信息
     *
     * @param string $site
     * @param string $app
     * @param string $ek enrollKey 如果要更新之前已经提交的数据，需要指定
     * @param string $submitkey 支持文件分段上传
     *
     */
    public function submit_action($site, $app, $submitkey = '') {
        if (empty($site)) {
            header('HTTP/1.0 500 parameter error:site is empty.');
            die('参数错误！');
        }
        if (empty($app)) {
            header('HTTP/1.0 500 parameter error:app is empty.');
            die('参数错误！');
        }

        $modelApp = $this->model('matter\signin');
        $mdoelSigRec = $this->model('matter\signin\record')->setOnlyWriteDbConn(true);
        if (false === ($oSigninApp = $modelApp->byId($app, ['cascaded' => 'N']))) {
            header('HTTP/1.0 500 parameter error:app dosen\'t exist.');
            die('签到活动不存在');
        }

        $oUser = clone $this->who;

        /**
         * 提交的数据
         */
        $oSigninData = $this->getPostJson();
        /**
         * 签到用户昵称
         */
        if ((isset($oSigninApp->assignedNickname->valid) && $oSigninApp->assignedNickname->valid === 'Y') && isset($oSigninApp->assignedNickname->schema->id)) {
            $oUser->nickname = $mdoelSigRec->getValueBySchema($oSigninApp->assignedNickname->schema, $oSigninData);
        } else {
            $userNickname = $modelApp->getUserNickname($oSigninApp, $oUser);
            $oUser->nickname = $userNickname;
        }
        /**
         * 检查是否存在匹配的分组记录
         */
        if (!empty($oSigninApp->entryRule->group->id)) {
            $modelGrpUsr = $this->model('matter\group\record');
            $aMatchResult = $modelGrpUsr->matchByData($oSigninApp->entryRule->group->id, $oSigninApp, $oSigninData, $oUser);
            if (false === $aMatchResult[0]) {
                return new \ParameterError($aMatchResult[1]);
            }
            $oGroupRecord = $aMatchResult[1];
            /* 将匹配的分组记录数据作为提交的登记数据的一部分 */
            $matchedData = $oGroupRecord->data;
            foreach ($matchedData as $n => $v) {
                !isset($oSigninData->{$n}) && $oSigninData->{$n} = $v;
            }
        }
        /**
         * 签到并保存登记的数据
         */
        $oSignState = $mdoelSigRec->signin($oUser, $oSigninApp, $oSigninData);
        // 保存签到登记数据
        empty($submitkey) && $submitkey = $oUser->uid;
        $rst = $mdoelSigRec->setData($site, $oSigninApp, $oSignState->ek, $oSigninData, $submitkey);
        if (false === $rst[0]) {
            return new \ResponseError($rst[1]);
        }
        /**
         * 检查签到数据是否在报名表中
         */
        if (!empty($oSigninApp->entryRule->enroll->id)) {
            $oEnrollApp = $this->model('matter\enroll')->byId($oSigninApp->entryRule->enroll->id, ['cascaded' => 'N']);
            if ($oEnrollApp) {
                /*获得要检查的数据*/
                $dataSchemas = $oSigninApp->dataSchemas;
                $requireCheckedData = new \stdClass;
                foreach ($dataSchemas as $oSchema) {
                    if (isset($oSchema->requireCheck) && $oSchema->requireCheck === 'Y') {
                        $requireCheckedData->{$oSchema->id} = $mdoelSigRec->getValueBySchema($oSchema, $oSigninData);
                    }
                }
                $modelEnrollRec = $this->model('matter\enroll\record');
                $enrollRecords = $modelEnrollRec->byData($oEnrollApp, $requireCheckedData);
                if (!empty($enrollRecords)) {
                    /**
                     * 找报名表中找到对应的记录，如果找到多条记录怎么办？
                     */
                    $oEnrollRecord = $enrollRecords[0];
                    if ($oEnrollRecord->verified === 'Y') {
                        $enrollData = $enrollRecords[0]->data;
                        foreach ($enrollData as $n => $v) {
                            !isset($oSigninData->{$n}) && $oSigninData->{$n} = $v;
                        }
                        // 记录报名数据
                        $mdoelSigRec->setData($site, $oSigninApp, $oSignState->ek, $oSigninData, $submitkey);
                        // 记录验证状态
                        $mdoelSigRec->update(
                            'xxt_signin_record',
                            ['verified' => 'Y', 'verified_enroll_key' => $oEnrollRecord->enroll_key],
                            ['enroll_key' => $oSignState->ek]
                        );
                        $oSignState->verified = 'Y';
                        // 返回指定的验证成功页
                        if (isset($oSigninApp->entryRule->success->entry)) {
                            $oSignState->forword = $oSigninApp->entryRule->success->entry;
                        }
                        /* 如果登记数据中未包含用户信息，更新用户信息 */
                        if (empty($oEnrollRecord->userid)) {
                            $oUserAcnt = $this->model('site\user\account')->byId($oUser->uid, ['fields' => 'wx_openid,qy_openid,headimgurl']);
                            if (false === $oUserAcnt) {
                                $oUserAcnt = new \stdClass;
                            }
                            $oUserAcnt->userid = $oUser->uid;
                            $oUserAcnt->nickname = $modelEnrollRec->escape($oUser->nickname);
                            $modelEnrollRec->update('xxt_enroll_record', $oUserAcnt, ['id' => $oEnrollRecord->id]);
                        }
                    }
                }
                if (!isset($oSignState->verified)) {
                    /**
                     * 没有在报名表中找到对应的记录
                     */
                    $mdoelSigRec->update(
                        'xxt_signin_record',
                        ['verified' => 'N', 'verified_enroll_key' => ''],
                        ['enroll_key' => $oSignState->ek]
                    );
                    $oSignState->verified = 'N';
                    if (isset($oSigninApp->entryRule->fail->entry)) {
                        $oSignState->forword = $oSigninApp->entryRule->fail->entry;
                    }
                }
            }
        }
        /* 记录操作日志 */
        $this->_logSubmit($oSigninApp, $oSignState->ek);

        /**
         * 当前轮次签到
         */
        if (false === $oSignState->signed) {
            /**
             * 发放签到行为分
             */
            $modelCoin = $this->model('matter\signin\coin')->setOnlyWriteDbConn(true);
            $modelClog = $this->model('site\coin\log')->setOnlyWriteDbConn(true);
            if ($oSignState->late) {
                $coinEvent = 'site.matter.signin.submit.late';
            } else {
                $coinEvent = 'site.matter.signin.submit.ontime';
            }
            $aCoinRules = $modelCoin->rulesByMatter($coinEvent, $oSigninApp);
            $modelClog->award($oSigninApp, $oUser, $coinEvent, $aCoinRules);
            /**
             * 更新项目用户信息
             */
            if (!empty($oSigninApp->mission_id)) {
                $modelMisUsr = $this->model('matter\mission\user');
                $modelMisUsr->setOnlyWriteDbConn(true);
                $oMission = $this->model('matter\mission')->byId($oSigninApp->mission_id, ['fields' => 'siteid,id,user_app_type,user_app_id']);
                if ($oMission->user_app_type === 'group') {
                    $oMisUsrGrpApp = (object) ['id' => $oMission->user_app_id];
                    $oMisGrpUser = $this->model('matter\group\record')->byUser($oMisUsrGrpApp, $oUser->uid, ['onlyOne' => true, 'team_id']);
                }
                $oMisUsr = $modelMisUsr->byIdInApp($oMission, $oUser->uid, ['fields' => 'id,nickname,group_id,last_signin_at,signin_num,user_total_coin']);
                if (false === $oMisUsr) {
                    $aNewMisUser = ['last_signin_at' => time(), 'signin_num' => 1];
                    if (!empty($oMisGrpUser->team_id)) {
                        $aNewMisUser['group_id'] = $oMisGrpUser->team_id;
                    }
                    if (!empty($aCoinRules)) {
                        $aNewMisUser['user_total_coin'] = 0;
                        foreach ($aCoinRules as $rule) {
                            $aNewMisUser['user_total_coin'] = $aNewMisUser['user_total_coin'] + (int) $rule->actor_delta;
                        }
                    }
                    $modelMisUsr->add($oMission, $oUser, $aNewMisUser);
                } else {
                    $aUpdMisUser = ['last_signin_at' => time(), 'signin_num' => $oMisUsr->signin_num + 1];
                    if ($oMisUsr->nickname !== $oUser->nickname) {
                        $aUpdMisUser['nickname'] = $oUser->nickname;
                    }
                    if (isset($oMisGrpUser->team_id)) {
                        if ($oMisUsr->group_id !== $oMisGrpUser->team_id) {
                            $aUpdMisUser['group_id'] = $oMisGrpUser->team_id;
                        }
                    }
                    if (!empty($aCoinRules)) {
                        $aUpdMisUser['user_total_coin'] = (int) $oMisUsr->user_total_coin;
                        foreach ($aCoinRules as $rule) {
                            $aUpdMisUser['user_total_coin'] = $aUpdMisUser['user_total_coin'] + (int) $rule->actor_delta;
                        }
                    }
                    $modelMisUsr->update(
                        'xxt_mission_user',
                        $aUpdMisUser,
                        ['id' => $oMisUsr->id]
                    );
                }
            }
        }

        return new \ResponseData($oSignState);
    }
    /**
     * 记录用户提交日志
     *
     * @param object $oApp
     *
     */
    private function _logSubmit($oApp, $ek) {
        $modelLog = $this->model('matter\log');

        $logUser = new \stdClass;
        $logUser->userid = $this->who->uid;
        $logUser->nickname = $this->who->nickname;

        $operation = new \stdClass;
        $operation->name = 'submit';
        $operation->data = $this->model('matter\signin\record')->byId($ek, ['fields' => 'enroll_key,signin_log,data']);

        $client = new \stdClass;
        $client->agent = tms_get_server('HTTP_USER_AGENT');
        $client->ip = $this->client_ip();

        $referer = tms_get_server('HTTP_REFERER') ? tms_get_server('HTTP_REFERER') : '';

        $logid = $modelLog->addUserMatterOp($oApp->siteid, $logUser, $oApp, $operation, $client, $referer);

        return $logid;
    }
    /**
     * 分段上传文件
     *
     * @param string $app
     * @param string $submitKey
     */
    public function uploadFile_action($app, $submitkey = '') {
        $modelApp = $this->model('matter\signin');
        $oApp = $modelApp->byId($app, ['cascaded' => 'N', 'fields' => 'id,siteid,state']);
        if (false === $oApp || $oApp->state !== '1') {
            return new \ObjectNotFoundError();
        }

        if (empty($submitkey)) {
            $submitkey = $this->who->uid;
        }
        /**
         * 分块上传文件
         */
        $dest = '/signin/' . $oApp->id . '/' . $submitkey . '_' . $_POST['resumableFilename'];
        $oResumable = $this->model('fs/resumable', $oApp->siteid, $dest, '_user');
        $oResumable->handleRequest($_POST);

        return new \ResponseData('ok');
    }
    /**
     * 返回指定记录或最后一条记录
     *
     * @param string $site
     * @param string $app
     */
    public function get_action($site, $app) {
        $modelApp = $this->model('matter\signin');
        $modelRec = $this->model('matter\signin\record');
        $options = ['cascade' => 'N'];

        $app = $modelApp->byId($app, $options);

        // 当前访问用户的基本信息
        $user = $this->who;

        // 登记数据
        $options = array(
            'fields' => '*',
        );

        $record = $modelRec->byUser($user, $app, $options);

        return new \ResponseData($record);
    }
    /**
     * 列出所有的登记记录
     *
     * $site
     * $app
     * $orderby time|remark|score|follower
     * $openid
     * $page
     * $size
     *
     * return
     * [0] 数据列表
     * [1] 数据总条数
     * [2] 数据项的定义
     *
     */
    public function list_action($site, $app, $owner = 'U', $rid = '', $orderby = 'time', $openid = null, $page = 1, $size = 30) {
        $user = $this->who;
        switch ($owner) {
        case 'A':
            $options = array();
            break;
        default:
            $options = array(
                'creater' => $user->uid,
            );
            break;
        }
        $options['rid'] = $rid;
        $options['page'] = $page;
        $options['size'] = $size;
        $options['orderby'] = $orderby;

        $modelRec = $this->model('matter\signin\record');

        $rst = $modelRec->byApp($app, $options);

        return new \ResponseData($rst);
    }
}