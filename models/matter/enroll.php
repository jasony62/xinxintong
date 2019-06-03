<?php
namespace matter;

require_once dirname(__FILE__) . '/enroll_base.php';
/**
 *
 */
class enroll_model extends enroll_base {
    /**
     * 记录日志时需要的列
     */
    const LOG_FIELDS = 'siteid,id,title,summary,pic,mission_id';
    /**
     *
     */
    protected function table() {
        return 'xxt_enroll';
    }
    /**
     * 活动进入链接
     */
    public function getEntryUrl($siteId, $id, $oParams = null) {
        if ($siteId === 'platform') {
            $oApp = $this->byId($id, ['cascaded' => 'N', 'notDecode' => true]);
            if (false === $oApp) {
                return APP_PROTOCOL . APP_HTTP_HOST . '/404.html';
            } else {
                $siteId = $oApp->siteid;
            }
        }

        $url = APP_PROTOCOL . APP_HTTP_HOST;
        $url .= "/rest/site/fe/matter/enroll";
        $url .= "?site={$siteId}&app=" . $id;

        if (isset($oParams) && is_object($oParams)) {
            foreach ($oParams as $k => $v) {
                if (is_string($v)) {
                    $url .= '&' . $k . '=' . $v;
                }
            }
        }

        return $url;
    }
    /**
     * 新建记录活动
     */
    public function create($oUser, $oNewApp) {
        /* 指定活动默认开始时间 */
        if (empty($oNewApp->start_at)) {
            $oNewApp->start_at = $this->getDefaultStartAt();
        }

        $oNewApp = parent::create($oUser, $oNewApp);

        /* 创建活动默认填写轮次 */
        $modelRnd = $this->model('matter\enroll\round');
        if (!empty($oNewApp->sync_mission_round) && $oNewApp->sync_mission_round === 'Y') {
            $oAppRnd = $modelRnd->getActive($oNewApp);
        }
        if (empty($oAppRnd)) {
            $oRoundProto = new \stdClass;
            $oRoundProto->title = '填写轮次';
            $oRoundProto->state = 1;
            $oRoundProto->start_at = $oNewApp->start_at;
            $aResult = $modelRnd->create($oNewApp, $oRoundProto, $oUser);
            if (true === $aResult[0]) {
                $oNewApp->appRound = $aResult[1];
            }
        } else {
            $oNewApp->appRound = $oAppRnd;
        }

        return $oNewApp;
    }
    /**
     * 返回指定活动的数据
     *
     * @param string $aid
     * @param array $options
     *
     */
    public function &byId($appId, $aOptions = []) {
        $fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
        $cascaded = isset($aOptions['cascaded']) ? $aOptions['cascaded'] : 'Y';
        $appRid = isset($aOptions['appRid']) ? $aOptions['appRid'] : '';

        $q = [
            $fields,
            'xxt_enroll',
            ["id" => $appId],
        ];

        if (($oApp = $this->query_obj_ss($q)) && empty($aOptions['notDecode'])) {
            $oApp->type = 'enroll';
            /* 自动补充信息 */
            if (!property_exists($oApp, 'id')) {
                $oApp->id = $appId;
            }
            /* 活动轮次 */
            $modelRnd = $this->model('matter\enroll\round');
            if (empty($appRid)) {
                $oAppRnd = $modelRnd->getActive($oApp, ['fields' => 'id,rid,title,purpose,start_at,end_at,mission_rid']);
            } else {
                $oAppRnd = $modelRnd->byId($appRid, ['fields' => 'id,rid,title,purpose,start_at,end_at,mission_rid']);
            }
            $oApp->appRound = $oAppRnd;

            if (isset($oApp->siteid) && isset($oApp->id)) {
                $oApp->entryUrl = $this->getEntryUrl($oApp->siteid, $oApp->id);
            }

            /* 数组类型 */
            foreach (['vote_config', 'score_config', 'question_config', 'answer_config', 'baseline_config', 'transmit_config', 'recycle_schemas'] as $uscProp) {
                if (property_exists($oApp, $uscProp)) {
                    $ccProp = preg_replace_callback('/(_(\w))/', function ($matches) {return strtoupper($matches[2]);}, $uscProp);
                    $oApp->{$ccProp} = empty($oApp->{$uscProp}) ? [] : json_decode($oApp->{$uscProp});
                    unset($oApp->{$uscProp});
                }
            }
            /* 对象类型 */
            foreach (['entry_rule', 'action_rule', 'scenario_config', 'notify_config', 'rp_config', 'repos_config', 'rank_config', 'absent_cause', 'assigned_nickname'] as $uscProp) {
                if (property_exists($oApp, $uscProp)) {
                    $ccProp = preg_replace_callback('/(_(\w))/', function ($matches) {return strtoupper($matches[2]);}, $uscProp);
                    $oApp->{$ccProp} = empty($oApp->{$uscProp}) ? new \stdClass : json_decode($oApp->{$uscProp});
                    unset($oApp->{$uscProp});
                }
            }

            if (property_exists($oApp, 'data_schemas')) {
                if (!empty($oApp->data_schemas)) {
                    $oApp->dataSchemas = json_decode($oApp->data_schemas);
                    if ($oApp->dataSchemas === null) {
                        /* 解析失败 */
                        $oApp->dataSchemas = [];
                    } else {
                        /* 应用的动态题目 */
                        $oApp2 = (object) ['id' => $oApp->id, 'appRound' => $oApp->appRound, 'dataSchemas' => json_decode($oApp->data_schemas), 'mission_id' => $oApp->mission_id];
                        $modelSch = $this->model('matter\enroll\schema');
                        $modelSch->setDynaSchemas($oApp2, isset($aOptions['task']) ? $aOptions['task'] : null);
                        $oApp->dynaDataSchemas = $oApp2->dataSchemas;
                        /* 设置活动的动态选项 */
                        $modelSch->setDynaOptions($oApp, $oAppRnd);
                    }
                } else {
                    $oApp->dataSchemas = $oApp->dynaDataSchemas = [];
                }
                /* 清除数据 */
                unset($oApp->data_schemas);
            }
            /* 轮次生成规则 */
            if (property_exists($oApp, 'round_cron')) {
                if ($this->getDeepValue($oApp, 'sync_mission_round') === 'Y') {
                    if (!empty($oApp->mission_id)) {
                        /* 使用项目的轮次生成规则 */
                        $oMission = $this->model('matter\mission')->byId($oApp->mission_id, ['fields' => 'round_cron']);
                        $oApp->roundCron = $oMission->roundCron;
                    } else {
                        $oApp->roundCron = [];
                    }
                } else if (!empty($oApp->round_cron)) {
                    $oApp->roundCron = json_decode($oApp->round_cron);
                    $modelRnd = $this->model('matter\enroll\round');
                    foreach ($oApp->roundCron as $rc) {
                        $rules[0] = $rc;
                        $rc->case = $modelRnd->sampleByCron($rules);
                    }
                } else {
                    $oApp->roundCron = [];
                }
                unset($oApp->round_cron);
            }

            if (!empty($oApp->matter_mg_tag)) {
                $oApp->matter_mg_tag = json_decode($oApp->matter_mg_tag);
            }

            $modelPage = $this->model('matter\enroll\page');
            if (!empty($oApp->id)) {
                if ($cascaded === 'Y') {
                    $oApp->pages = $modelPage->byApp($oApp->id);
                } else {
                    $oApp->pages = $modelPage->byApp($oApp->id, ['cascaded' => 'N', 'fields' => 'id,name,type,title']);
                }
            }
        }

        return $oApp;
    }
    /**
     * 返回记录活动列表
     */
    public function &bySite($site, $page = 1, $size = 30, $mission = null, $scenario = null) {
        $result = array();

        $q = array(
            '*',
            'xxt_enroll a',
            "siteid='$site' and state<>0",
        );
        if (!empty($scenario)) {
            $q[2] .= " and scenario='$scenario'";
        }
        if (!empty($mission)) {
            $q[2] .= " and exists(select 1 from xxt_mission_matter where mission_id='$mission' and matter_type='enroll' and matter_id=a.id)";
        }
        $q2['o'] = 'a.modify_at desc';
        $q2['r']['o'] = ($page - 1) * $size;
        $q2['r']['l'] = $size;
        if ($a = $this->query_objs_ss($q, $q2)) {
            $result['apps'] = $a;
            $q[0] = 'count(*)';
            $total = (int) $this->query_val_ss($q);
            $result['total'] = $total;
        }

        return $result;
    }
    /**
     * 返回记录活动列表
     */
    public function &byMission($mission, $scenario = null, $page = 1, $size = 30) {
        $result = new \stdClass;

        $q = [
            '*',
            'xxt_enroll',
            "state<>0 and mission_id='$mission'",
        ];
        if (!empty($scenario)) {
            $q[2] .= " and scenario='$scenario'";
        }
        $q2['o'] = 'modify_at desc';
        if ($page) {
            $q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
        }
        $result->apps = $this->query_objs_ss($q, $q2);
        if ($page && $size) {
            $q[0] = 'count(*)';
            $total = (int) $this->query_val_ss($q);
            $result->total = $total;
        } else {
            $result->total = count($result->apps);
        }

        return $result;
    }
    /**
     * 更新记录活动标签
     */
    public function updateTags($aid, $tags) {
        if (empty($tags)) {
            return false;
        }
        if (is_array($tags)) {
            $tags = implode(',', $tags);
        }

        $aOptions = ['fields' => 'id,tags', 'cascaded' => 'N'];
        $oApp = $this->byId($aid, $aOptions);
        if (empty($oApp->tags)) {
            $this->update('xxt_enroll', ['tags' => $tags], ["id" => $aid]);
        } else {
            $existent = explode(',', $oApp->tags);
            $checked = explode(',', $tags);
            $updated = array();
            foreach ($checked as $c) {
                if (!in_array($c, $existent)) {
                    $updated[] = $c;
                }
            }
            if (count($updated)) {
                $updated = array_merge($existent, $updated);
                $updated = implode(',', $updated);
                $this->update('xxt_enroll', ['tags' => $updated], ["id" => $aid]);
            }
        }

        return true;
    }
    /**
     * 记录活动运行情况摘要
     *
     * @param object $oApp
     *
     * @return
     */
    public function &opData($oApp, $onlyActiveRound = false) {
        $modelUsr = $this->model('matter\enroll\user');
        $modelRnd = $this->model('matter\enroll\round');

        $mschemaIds = [];
        if (!empty($oApp->entryRule) && is_object($oApp->entryRule)) {
            if (!empty($oApp->entryRule->member) && is_object($oApp->entryRule->member)) {
                foreach ($oApp->entryRule->member as $mschemaId => $rule) {
                    if (!empty($rule->entry)) {
                        $mschemaIds[] = $mschemaId;
                    }
                }
            }
        }

        if ($onlyActiveRound) {
            if ($oActiveRound = $modelRnd->getActive($oApp)) {
                $recentRounds[] = $oActiveRound;
            }
        } else {
            $page = (object) ['at' => 1, 'size' => 3];
            $result = $modelRnd->byApp($oApp, ['fields' => 'rid,title', 'page' => $page]);
            $recentRounds = $result->rounds;
        }

        if (empty($recentRounds)) {
            $oRound = new \stdClass;
            /* total */
            $q = [
                'count(*)',
                'xxt_enroll_record',
                ['aid' => $oApp->id, 'state' => 1],
            ];
            $oRound->total = $this->query_val_ss($q);
            /* remark */
            $q = [
                'count(*)',
                'xxt_enroll_record_remark',
                ['aid' => $oApp->id, 'state' => 1],
            ];
            $oRound->remark_total = $this->query_val_ss($q);
            /* enrollee */
            $oEnrollees = $modelUsr->enrolleeByApp($oApp, '', '', ['cascaded' => 'N']);
            $oRound->enrollee_num = $oEnrollees->total;
            $oRound->enrollee_unsubmit_num = 0;
            if (!empty($oEnrollees->users)) {
                foreach ($oEnrollees->users as $oEnrollee) {
                    if ($oEnrollee->enroll_num == 0) {
                        $oRound->enrollee_unsubmit_num++;
                    }
                }
            }
            /* member */
            if (!empty($mschemaIds)) {
                $oRound->mschema = new \stdClass;
                foreach ($mschemaIds as $mschemaId) {
                    $oRound->mschema->{$mschemaId} = $this->_opByMschema($oApp->id, $mschemaId);
                }
            }
            $summary[] = $oRound;
        } else {
            $summary = [];
            $oActiveRound = $modelRnd->getActive($oApp);
            foreach ($recentRounds as $oRound) {
                if ($oActiveRound && $oRound->rid === $oActiveRound->rid) {
                    $oRound->active = 'Y';
                }
                /* total */
                $q = [
                    'count(*)',
                    'xxt_enroll_record',
                    ['aid' => $oApp->id, 'state' => 1, 'rid' => $oRound->rid],
                ];
                $oRound->total = $this->query_val_ss($q);
                /* remark */
                $q = [
                    'count(*)',
                    'xxt_enroll_record_remark',
                    ['aid' => $oApp->id, 'state' => 1, 'rid' => $oRound->rid],
                ];
                $oRound->remark_total = $this->query_val_ss($q);
                /* enrollee */
                $oEnrollees = $modelUsr->enrolleeByApp($oApp, '', '', ['rid' => $oRound->rid, 'cascaded' => 'N']);
                $oRound->enrollee_num = $oEnrollees->total;
                $oRound->enrollee_unsubmit_num = 0;
                if (!empty($oEnrollees->users)) {
                    foreach ($oEnrollees->users as $oEnrollee) {
                        if ($oEnrollee->enroll_num == 0) {
                            $oRound->enrollee_unsubmit_num++;
                        }
                    }
                }

                /* member */
                if (!empty($mschemaIds)) {
                    $oRound->mschema = new \stdClass;
                    foreach ($mschemaIds as $mschemaId) {
                        $oRound->mschema->{$mschemaId} = $this->_opByMschema($oApp->id, $mschemaId, $oRound->rid);
                    }
                }
                $summary[] = $oRound;
            }
        }

        return $summary;
    }
    /**
     * 通讯录联系人登记情况
     */
    private function _opByMschema($appId, $mschemaId, $rid = null) {
        $result = new \stdClass;
        $q = [
            'count(*)',
            'xxt_site_member',
            "verified='Y' and forbidden='N' and schema_id=$mschemaId and userid in (select r.userid from xxt_enroll_record r where r.aid='{$appId}' and r.state=1 ",
        ];
        !empty($rid) && $q[2] .= " and r.rid='{$rid}'";
        $q[2] .= ")";

        $result->enrolled = $this->query_val_ss($q);

        return $result;
    }
    /**
     * 获得参加记录活动的用户的昵称
     *
     * @param object $oApp
     * @param object $oUser [uid,nickname]
     */
    public function getUserNickname($oApp, $oUser) {
        if (empty($oUser->uid)) {
            return '';
        }
        $nickname = '';
        $oEntryRule = $oApp->entryRule;
        if ($this->getDeepValue($oEntryRule, 'anonymous') === 'Y') {
            /* 匿名访问 */
            $nickname = '';
        } else {
            if ($this->getDeepValue($oEntryRule, 'scope.member') === 'Y' && isset($oEntryRule->member)) {
                foreach ($oEntryRule->member as $schemaId => $rule) {
                    $modelMem = $this->model('site\user\member');
                    if (empty($oUser->unionid)) {
                        $aMembers = $modelMem->byUser($oUser->uid, ['schemas' => $schemaId]);
                        if (count($aMembers) === 1) {
                            $oMember = $aMembers[0];
                            if ($oMember->verified === 'Y') {
                                $nickname = empty($oMember->name) ? $oMember->identity : $oMember->name;
                                break;
                            }
                        }
                    } else {
                        $modelAcnt = $this->model('site\user\account');
                        $aUnionUsers = $modelAcnt->byUnionid($oUser->unionid, ['siteid' => $oApp->siteid, 'fields' => 'uid']);
                        foreach ($aUnionUsers as $oUnionUser) {
                            $aMembers = $modelMem->byUser($oUnionUser->uid, ['schemas' => $schemaId]);
                            if (count($aMembers) === 1) {
                                $oMember = $aMembers[0];
                                if ($oMember->verified === 'Y') {
                                    $nickname = empty($oMember->name) ? $oMember->identity : $oMember->name;
                                    break;
                                }
                            }
                        }
                        if (!empty($nickname)) {
                            break;
                        }
                    }
                }
            } else if ($this->getDeepValue($oEntryRule, 'scope.sns') === 'Y') {
                $modelAcnt = $this->model('site\user\account');
                if ($siteUser = $modelAcnt->byId($oUser->uid)) {
                    foreach ($oEntryRule->sns as $snsName => $rule) {
                        if ($snsName === 'wx') {
                            $modelWx = $this->model('sns\wx');
                            if (($wxConfig = $modelWx->bySite($oApp->siteid)) && $wxConfig->joined === 'Y') {
                                $snsSiteId = $oApp->siteid;
                            } else {
                                $snsSiteId = 'platform';
                            }
                        } else {
                            $snsSiteId = $oApp->siteid;
                        }
                        $modelSnsUser = $this->model('sns\\' . $snsName . '\fan');
                        if ($snsUser = $modelSnsUser->byOpenid($snsSiteId, $siteUser->{$snsName . '_openid'})) {
                            $nickname = $snsUser->nickname;
                            break;
                        }
                    }
                }
            } else {
                if (!empty($oApp->mission_id)) {
                    /* 从项目中获得用户昵称 */
                    $oMission = (object) ['id' => $oApp->mission_id];
                    $modelMisUsr = $this->model('matter\mission\user');
                    $oMisUsr = $modelMisUsr->byId($oMission, $oUser->uid, ['fields' => 'nickname']);
                    if ($oMisUsr) {
                        $nickname = $oMisUsr->nickname;
                    } else {
                        $nickname = empty($oUser->nickname) ? '' : $oUser->nickname;
                    }
                } else {
                    $nickname = empty($oUser->nickname) ? '' : $oUser->nickname;
                }
            }
        }

        return $nickname;
    }
    /**
     * 创建记录活动
     *
     * @param string $site site's id
     * @param string $mission mission's id
     * @param string $scenario scenario's name
     * @param string $template template's name
     *
     */
    public function createByTemplate($oUser, $oSite, $oCustomConfig, $oMission = null, $scenario = 'common', $template = 'simple') {
        $oTemplateConfig = $this->_getSysTemplate($scenario, $template);

        $oNewApp = new \stdClass;
        /* 从站点或项目获得的信息 */
        if (empty($oMission)) {
            $oNewApp->pic = $oSite->heading_pic;
            $oNewApp->summary = '';
            $oNewApp->use_mission_header = 'N';
            $oNewApp->use_mission_footer = 'N';
            $oMission = null;
        } else {
            $oNewApp->pic = $oMission->pic;
            $oNewApp->summary = $oMission->summary;
            $oNewApp->mission_id = $oMission->id;
            $oNewApp->use_mission_header = 'Y';
            $oNewApp->use_mission_footer = 'Y';
            $oMisEntryRule = $oMission->entryRule;
        }
        $appId = uniqid();
        $oProto = isset($oCustomConfig->proto) ? $oCustomConfig->proto : null;
        $title = empty($oProto->title) ? '新记录活动' : $this->escape($oProto->title);

        /* 进入规则 */
        $oEntryRule = $oTemplateConfig->entryRule;
        if (!empty($oCustomConfig->proto->entryRule->scope)) {
            /* 用户指定的规则 */
            $oApp = new \stdClass;
            $oApp->id = $appId;
            $oApp->title = $title;
            $oApp->type = 'enroll';
            $this->setEntryRuleByProto($oSite, $oEntryRule, $oCustomConfig->proto->entryRule, $oApp, $oUser);
        } else if (isset($oMisEntryRule)) {
            /* 项目的进入规则 */
            $this->setEntryRuleByMission($oEntryRule, $oMisEntryRule);
        }

        /* 活动题目 */
        if (empty($oProto->schema->default->empty)) {
            /* 关联了通讯录，替换匹配的题目 */
            if (!empty($oTemplateConfig->schema)) {
                /* 通讯录关联题目 */
                if (!empty($oEntryRule->scope) && $oEntryRule->scope === 'member') {
                    $mschemaIds = array_keys(get_object_vars($oEntryRule->member));
                    if (!empty($mschemaIds)) {
                        $this->setSchemaByMschema($mschemaIds[0], $oTemplateConfig);
                    }
                }
            }

            /* 关联了分组活动，添加分组名称，替换匹配的题目 */
            if (!empty($oEntryRule->group->id)) {
                $this->setSchemaByGroupApp($oEntryRule->group->id, $oTemplateConfig);
            }

            /* 作为昵称的题目 */
            $oNicknameSchema = $this->findAssignedNicknameSchema($oTemplateConfig->schema);
            if (!empty($oNicknameSchema)) {
                $oNewApp->assigned_nickname = json_encode(['valid' => 'Y', 'schema' => ['id' => $oNicknameSchema->id]]);
            }

            isset($oTemplateConfig->schema) && $oNewApp->data_schemas = $this->escape($this->toJson($oTemplateConfig->schema));
        } else {
            /* 不使用默认题目 */
            $oTemplateConfig->schema = [];
            $oNewApp->data_schemas = '[]';
        }

        /* 添加页面 */
        $this->addPageByTemplate($oUser, $oSite, $oMission, $appId, $oTemplateConfig, $oCustomConfig);

        /* 登记数量限制 */
        if (isset($oTemplateConfig->count_limit)) {
            $oNewApp->count_limit = $oTemplateConfig->count_limit;
        }
        if (isset($oTemplateConfig->enrolled_entry_page)) {
            $oNewApp->enrolled_entry_page = $oTemplateConfig->enrolled_entry_page;
        }
        /* 场景设置 */
        if (isset($oTemplateConfig->scenarioConfig)) {
            $oScenarioConfig = $oTemplateConfig->scenarioConfig;
            if (isset($oCustomConfig->scenarioConfig) && is_object($oCustomConfig->scenarioConfig)) {
                foreach ($oCustomConfig->scenarioConfig as $k => $v) {
                    $oScenarioConfig->{$k} = $v;
                }
            }
        } else {
            $oScenarioConfig = new \stdClass;
        }
        $oNewApp->scenario = $scenario;

        /* create app */
        $oNewApp->id = $appId;
        $oNewApp->siteid = $oSite->id;
        $oNewApp->title = $title;
        $oNewApp->summary = empty($oProto->summary) ? '' : $this->escape($oProto->summary);
        $oNewApp->sync_mission_round = empty($oProto->sync_mission_round) ? 'N' : (in_array($oProto->sync_mission_round, ['Y', 'N']) ? $oProto->sync_mission_round : 'N');
        $oNewApp->start_at = isset($oProto->start_at) ? $oProto->start_at : 0;
        $oNewApp->end_at = isset($oProto->end_at) ? $oProto->end_at : 0;
        $oNewApp->entry_rule = $this->escape($this->toJson($oEntryRule));
        /* 是否开放共享页 */
        if (isset($oProto->can_repos) && in_array($oProto->can_repos, ['Y', 'N'])) {
            $oScenarioConfig->can_repos = $oProto->can_repos;
        } else if (isset($oTemplateConfig->can_repos)) {
            $oScenarioConfig->can_repos = $oTemplateConfig->can_repos;
        } else {
            $oScenarioConfig->can_repos = 'N';
        }
        /* 是否开放排行榜 */
        if (isset($oProto->can_rank) && in_array($oProto->can_rank, ['Y', 'N'])) {
            $oScenarioConfig->can_rank = $oProto->can_rank;
        } else if (isset($oTemplateConfig->can_rank)) {
            $oScenarioConfig->can_rank = $oTemplateConfig->can_rank;
        } else {
            $oScenarioConfig->can_rank = 'N';
        }
        $oNewApp->scenario_config = json_encode($oScenarioConfig);

        $oNewApp = $this->create($oUser, $oNewApp);

        /* 记录操作日志 */
        $this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');

        return $oNewApp;
    }
    /**
     * 获得系统内置记录活动模板
     * 如果没有指定场景或模板，那么就使用系统的缺省模板
     *
     * @param string $scenario scenario's name
     * @param string $template template's name
     *
     */
    private function _getSysTemplate($scenario = null, $template = null) {
        if (empty($scenario) || empty($template)) {
            $scenario = 'common';
            $template = 'simple';
        }
        $templateDir = TMS_APP_TEMPLATE . '/pl/fe/matter/enroll/scenario/' . $scenario . '/templates/' . $template;
        $oConfig = file_get_contents($templateDir . '/config.json');
        $oConfig = preg_replace('/\t|\r|\n/', '', $oConfig);
        $oConfig = json_decode($oConfig);
        /**
         * 处理页面
         */
        if (!empty($oConfig->pages)) {
            foreach ($oConfig->pages as $oPage) {
                $templateFile = $templateDir . '/' . $oPage->name;
                /* 填充代码 */
                $code = [
                    'html' => file_exists($templateFile . '.html') ? file_get_contents($templateFile . '.html') : '',
                    'css' => file_exists($templateFile . '.css') ? file_get_contents($templateFile . '.css') : '',
                    'js' => file_exists($templateFile . '.js') ? file_get_contents($templateFile . '.js') : '',
                ];
                $oPage->code = $code;
            }
        }

        return $oConfig;
    }
    /**
     * 根据模板生成页面
     *
     * @param string $appId
     * @param string $scenario scenario's name
     * @param string $template template's name
     */
    public function addPageByTemplate(&$user, $oSite, $oMission, &$appId, &$oTemplateConfig) {
        $pages = $oTemplateConfig->pages;
        if (empty($pages)) {
            return false;
        }

        $modelPage = $this->model('matter\enroll\page');
        $modelCode = $this->model('code\page');
        /**
         * 处理页面
         */
        foreach ($pages as $oPage) {
            $ap = $modelPage->add($user, $oSite->id, $appId, (array) $oPage);
            /**
             * 处理页面数据定义
             */
            if (empty($oTemplateConfig->schema)) {
                $oPage->data_schemas = [];
            } else if (empty($oPage->data_schemas) && !empty($oPage->simpleConfig)) {
                /* 页面使用应用的所有数据定义 */
                foreach ($oTemplateConfig->schema as $oSchema) {
                    $oNewPageSchema = new \stdClass;
                    $oNewPageSchema->schema = $oSchema;
                    $oNewPageSchema->config = clone $oPage->simpleConfig;
                    if ($oPage->type === 'V') {
                        $oNewPageSchema->config->id = 'V_' . $oSchema->id;
                    }
                    $oPage->data_schemas[] = $oNewPageSchema;
                }
            }
            $pageSchemas = [];
            $pageSchemas['data_schemas'] = isset($oPage->data_schemas) ? $this->escape($this->toJson($oPage->data_schemas)) : '[]';
            $pageSchemas['act_schemas'] = isset($oPage->act_schemas) ? $this->escape($this->toJson($oPage->act_schemas)) : '[]';
            $rst = $modelPage->update(
                'xxt_enroll_page',
                $pageSchemas,
                "aid='$appId' and id={$ap->id}"
            );
            /* 填充页面 */
            if (!empty($oPage->code)) {
                $code = (array) $oPage->code;
                $code['html'] = $modelPage->compileHtml($oPage->type, $code['html'], $oPage->data_schemas);
                $modelCode->modify($ap->code_id, $code);
            }
        }

        return $oTemplateConfig;
    }
    /**
     * 清理属性noticeConfig
     */
    public function purifyNoticeConfig($oApp, $oNoticeConfig) {
        $oPurified = new \stdClass;
        if (!empty($oNoticeConfig)) {
            /* 提交记录提醒 */
            if (isset($oNoticeConfig->submit)) {
                $oSubmit = $oNoticeConfig->submit;
                $oPurified->submit = new \stdClass;
                if (empty($oSubmit->page) || !in_array($oSubmit->page, ['cowork'])) {
                    return [false, '没有指定可用的通知页面'];
                }
                if (empty($oSubmit->receiver->scope) || !is_array($oSubmit->receiver->scope)) {
                    return [false, '没有指定接收通知的用户范围'];
                }
                foreach ($oSubmit->receiver->scope as $scope) {
                    if (!in_array($scope, ['leader', 'group'])) {
                        return [false, '没有指定可用的接收通知用户范围'];
                    }
                }
                if (in_array('group', $oSubmit->receiver->scope)) {
                    if (empty($oSubmit->receiver->group)) {
                        return [false, '没有指定接收通知的分组活动'];
                    }
                    if (empty($oSubmit->receiver->group->id)) {
                        return [false, '没有指定接收通知的分组活动'];
                    }
                } else {
                    unset($oSubmit->receiver->group);
                }

                $oPurified->submit->valid = !empty($oNoticeConfig->submit->valid);
                $oPurified->submit->page = $oNoticeConfig->submit->page;
                $oPurified->submit->receiver = $oNoticeConfig->submit->receiver;
            }
            /* 提交协作填写 */
            if (isset($oNoticeConfig->cowork)) {
                $oSubmit = $oNoticeConfig->cowork;
                $oPurified->cowork = new \stdClass;
                if (empty($oSubmit->page) || !in_array($oSubmit->page, ['cowork'])) {
                    return [false, '没有指定可用的通知页面'];
                }
                if (empty($oSubmit->receiver->scope) || !is_array($oSubmit->receiver->scope)) {
                    return [false, '没有指定接收通知的用户范围'];
                }
                foreach ($oSubmit->receiver->scope as $scope) {
                    if (!in_array($scope, ['related', 'group'])) {
                        return [false, '没有指定可用的接收通知用户范围'];
                    }
                }
                if (in_array('group', $oSubmit->receiver->scope)) {
                    if (empty($oSubmit->receiver->group)) {
                        return [false, '没有指定接收通知的分组活动'];
                    }
                    if (empty($oSubmit->receiver->group->id)) {
                        return [false, '没有指定接收通知的分组活动'];
                    }
                } else {
                    unset($oSubmit->receiver->group);
                }

                $oPurified->cowork->valid = !empty($oNoticeConfig->cowork->valid);
                $oPurified->cowork->page = $oNoticeConfig->cowork->page;
                $oPurified->cowork->receiver = $oNoticeConfig->cowork->receiver;
            }
            /* 提交评论提醒 */
            if (isset($oNoticeConfig->remark)) {
                $oSubmit = $oNoticeConfig->remark;
                $oPurified->remark = new \stdClass;
                if (empty($oSubmit->page) || !in_array($oSubmit->page, ['cowork'])) {
                    return [false, '没有指定可用的通知页面'];
                }
                if (empty($oSubmit->receiver->scope) || !is_array($oSubmit->receiver->scope)) {
                    return [false, '没有指定接收通知的用户范围'];
                }
                foreach ($oSubmit->receiver->scope as $scope) {
                    if (!in_array($scope, ['related', 'group'])) {
                        return [false, '没有指定可用的接收通知用户范围'];
                    }
                }
                if (in_array('group', $oSubmit->receiver->scope)) {
                    if (empty($oSubmit->receiver->group)) {
                        return [false, '没有指定接收通知的分组活动'];
                    }
                    if (empty($oSubmit->receiver->group->id)) {
                        return [false, '没有指定接收通知的分组活动'];
                    }
                } else {
                    unset($oSubmit->receiver->group);
                }

                $oPurified->remark->valid = !empty($oNoticeConfig->remark->valid);
                $oPurified->remark->page = $oNoticeConfig->remark->page;
                $oPurified->remark->receiver = $oNoticeConfig->remark->receiver;
            }
        }

        return [true, $oPurified];
    }
}