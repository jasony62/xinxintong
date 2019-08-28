<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/**
 * 创建记录活动
 */
class create extends main_base {
    /**
     * 根据系统模板创建记录活动
     *
     * @param string $site site's id
     * @param string $mission mission's id
     * @param string $scenario scenario's name
     * @param string $template template's name
     *
     */
    public function bySysTemplate_action($site, $mission = null, $scenario = 'common', $template = 'simple') {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }
        $oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
        if (false === $oSite) {
            return new \ObjectNotFoundError();
        }
        if (empty($mission)) {
            $oMission = null;
        } else {
            $modelMis = $this->model('matter\mission');
            $oMission = $modelMis->byId($mission);
            if (false === $oMission) {
                return new \ObjectNotFoundError();
            }
        }
        $modelApp = $this->model('matter\enroll')->setOnlyWriteDbConn(true);

        $oCustomConfig = $this->getPostJson();

        $oNewApp = $modelApp->createByTemplate($oUser, $oSite, $oCustomConfig, $oMission, $scenario, $template);

        return new \ResponseData($oNewApp);
    }
    /**
     * 创建指定活动指定题目的打分活动
     * 例如：给答案打分
     */
    public function asScoreBySchema_action($app) {
        if (false === ($oCreator = $this->accountUser())) {
            return new \ResponseTimeout();
        }

        $modelApp = $this->model('matter\enroll');
        $oSourceApp = $modelApp->byId($app, 'id,siteid,title,summary,pic,scenario,start_at,end_at,mission_id,sync_mission_round,data_schemas');
        if (false === $oSourceApp || $oSourceApp->state !== '1') {
            return new \ObjectNotFoundError('指定的活动不存在');
        }

        $oProto = $this->getPostJson(false);
        if (empty($oProto->schemas)) {
            return new \ObjectNotFoundError('没有指定要打分的题目');
        }

        $protoSchemas = [];
        $sourceSchemaIds = [];
        foreach ($oProto->schemas as $oProtoSchema) {
            if (empty($oProtoSchema->dsSchema->schema->id)) {continue;}
            $sourceSchemaIds[] = $oProtoSchema->dsSchema->schema->id;
            $protoSchemas[] = $oProtoSchema;
        }

        $modelSch = $this->model('matter\enroll\schema');
        $aSourceSchemas = $modelSch->asAssoc($oSourceApp->dataSchemas, ['filter' => function ($oSchema) use ($sourceSchemaIds) {return in_array($oSchema->id, $sourceSchemaIds);}]);
        if (empty($aSourceSchemas)) {
            return new \ResponseError('指定的题目不存在');
        }

        $oSite = $this->model('site')->byId($oSourceApp->siteid, ['fields' => 'id,heading_pic']);
        if (false === $oSite) {
            return new \ObjectNotFoundError('活动所属团队不存在');
        }

        if (empty($oSourceApp->mission_id)) {
            $oMission = null;
        } else {
            $modelMis = $this->model('matter\mission');
            $oMission = $modelMis->byId($oSourceApp->mission_id);
            if (false === $oMission) {
                return new \ObjectNotFoundError();
            }
        }

        $oCustomConfig = new \stdClass;
        $this->setDeepValue($oCustomConfig, 'proto.title', $this->getDeepValue($oProto, 'title', $oSourceApp->title . '（打分）'));
        $this->setDeepValue($oCustomConfig, 'proto.sync_mission_round', $oSourceApp->sync_mission_round);
        // 不按照模板生成题目
        $this->setDeepValue($oCustomConfig, 'proto.schema.default.empty', true);

        $oNewApp = $modelApp->createByTemplate($oCreator, $oSite, $oCustomConfig, $oMission);

        $newSchemas = [];
        foreach ($protoSchemas as $oProtoSchema) {
            if (!isset($aSourceSchemas[$oProtoSchema->dsSchema->schema->id])) {continue;}

            $oSourceSchema = $aSourceSchemas[$oProtoSchema->dsSchema->schema->id];
            $oNewSchema = new \stdClass;

            $oNewSchema->dsSchema = (object) [
                'app' => (object) ['id' => $oSourceApp->id, 'title' => $oSourceApp->title],
                'schema' => (object) ['id' => $oSourceSchema->id, 'title' => $oSourceSchema->title, 'type' => $oSourceSchema->type],
            ];
            $oNewSchema->id = 's' . uniqid();
            $oNewSchema->required = "Y";
            $oNewSchema->type = "score";
            $oNewSchema->unique = "N";
            $oNewSchema->requireScore = "Y";
            $oNewSchema->scoreMode = "evaluation";

            $oNewSchema->title = $oSourceSchema->title;
            $oNewSchema->range = [1, 5];
            if (empty($oProtoSchema->ops)) {
                $oNewSchema->ops = [(object) ['l' => '打分项1', 'v' => 'v1'], (object) ['l' => '打分项2', 'v' => 'v2']];
            } else {
                foreach ($oProtoSchema->ops as $index => $oOp) {
                    $seq = ++$index;
                    $oNewSchema->ops[] = (object) ['l' => $this->getDeepValue($oOp, 'l', '打分项' . $seq), 'v' => 'v' . $seq];
                }
            }
            $newSchemas[] = $oNewSchema;
            $oSourceSchema->scoreApp = (object) ['id' => $oNewApp->id, 'schema' => (object) ['id' => $oNewSchema->id]];
        }

        $modelApp->modify($oCreator, $oNewApp, (object) ['data_schemas' => $this->escape($modelApp->toJson($newSchemas))]);

        $modelApp->modify($oCreator, $oSourceApp, (object) ['data_schemas' => $this->escape($modelApp->toJson($oSourceApp->dataSchemas))]);

        return new \ResponseData(['app' => (object) ['id' => $oNewApp->id], 'schemas' => $aSourceSchemas]);
    }
    /**
     * 创建一个活动，并给项目中的每一个用户生成1条空记录
     *
     * @param string $mission mission's id
     *
     */
    public function createByMissionUser_action($mission) {
        $modelMis = $this->model('matter\mission');
        $oMission = $modelMis->byId($mission);
        if (false === $oMission) {
            return new \ObjectNotFoundError();
        }
        if (empty($oMission->user_app_id) || empty($oMission->user_app_type)) {
            return new \ParameterError('项目没有指定用户名单，无法创建活动');
        }

        $oSite = $this->model('site')->byId($oMission->siteid, ['fields' => 'id,heading_pic']);
        if (false === $oSite) {
            return new \ObjectNotFoundError();
        }

        $modelApp = $this->model('matter\enroll')->setOnlyWriteDbConn(true);

        $oUser = $this->user;

        /* 生成活动的schema */
        $newSchemas = [];
        /* 使用缺省模板 */
        $oConfig = $this->_getSysTemplate('common', 'simple');
        /* 进入规则 */
        $entryRule = $oConfig->entryRule;
        if (empty($entryRule)) {
            return new \ResponseError('没有获得页面进入规则');
        }
        if (!isset($entryRule->scope)) {
            $entryRule->scope = new \stdClass;
        }

        /* 修改模板的配置 */
        $oConfig->schema = [];
        foreach ($oConfig->pages as $oPage) {
            if ($oPage->type === 'I') {
                $oPage->data_schemas = [];
            } else if ($oPage->type === 'V') {
                $oPage->data_schemas = [];
            }
        }

        $current = time();
        $appId = uniqid();
        $oNewApp = new \stdClass;
        /* 项目获得的信息 */
        $oNewApp->pic = $oMission->pic;
        $oNewApp->summary = $oMission->summary;
        $oNewApp->mission_id = $oMission->id;
        $oNewApp->sync_mission_round = 'Y';
        $oNewApp->use_mission_header = 'Y';
        $oNewApp->use_mission_footer = 'Y';
        $oNewApp->scenario = 'mis_user_score'; // 项目用户计分表

        /* 添加页面 */
        $modelApp->addPageByTemplate($oUser, $oSite, $oMission, $appId, $oConfig, null);
        /* 登记数量限制 */
        if (isset($oConfig->count_limit)) {
            $oNewApp->count_limit = $oConfig->count_limit;
        }
        if (isset($oConfig->enrolled_entry_page)) {
            $oNewApp->enrolled_entry_page = $oConfig->enrolled_entry_page;
        }
        /* 场景设置 */
        if (isset($oConfig->scenarioConfig)) {
            $scenarioConfig = $oConfig->scenarioConfig;
            $oNewApp->scenario_config = json_encode($scenarioConfig);
        }
        /* create app */
        $oNewApp->id = $appId;
        $oNewApp->siteid = $oSite->id;
        $oNewApp->title = $modelApp->escape($oMission->title) . '-计分活动';
        $oNewApp->start_at = $current;
        $oNewApp->entry_rule = json_encode($entryRule);
        $oNewApp->data_schemas = $modelApp->escape($modelApp->toJson($newSchemas));

        $oNewApp = $modelApp->create($oUser, $oNewApp);

        /* 记录操作日志 */
        $this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');

        /* 获得项目用户 */
        $oUserSource = new \stdClass;
        $oUserSource->id = $oMission->user_app_id;
        $oUserSource->type = $oMission->user_app_type;
        switch ($oUserSource->type) {
        case 'group':
            $oGrpApp = $this->model('matter\group')->byId($oUserSource->id, ['fields' => 'assigned_nickname', 'cascaded' => 'N']);
            $oResult = $this->model('matter\group\record')->byApp($oUserSource, (object) ['fields' => 'userid,nickname']);
            $misUsers = isset($oResult->records) ? $oResult->records : [];
            break;
        case 'enroll':
            $misUsers = $this->model('matter\enroll\user')->enrolleeByApp($oUserSource, '', '', ['fields' => 'userid,nickname', 'cascaded' => 'N']);
            break;
        case 'signin':
            $misUsers = $this->model('matter\signin\record')->enrolleeByApp($oUserSource, ['fields' => 'distinct userid,nickname']);
            break;
        case 'mschema':
            $misUsers = $this->model('site\user\member')->byMschema($oUserSource->id, ['fields' => 'userid,name nickname']);
            break;
        }
        /* 添加空记录 */
        if (count($misUsers)) {
            $modelRec = $this->model('matter\enroll\record');
            foreach ($misUsers as $oMisUser) {
                if (empty($oMisUser->userid)) {
                    continue;
                }
                $oMockUser = new \stdClass;
                $oMockUser->uid = $oMisUser->userid;
                $oMockUser->nickname = $oMisUser->nickname;
                $modelRec->enroll($oNewApp, $oMockUser, ['nickname' => $oMockUser->nickname]);
            }
        }

        return new \ResponseData($oNewApp);
    }
    /**
     * 从共享模板模板创建记录活动
     *
     * @param string $site
     * @param int $template
     * @param int $mission
     *
     * @return object ResponseData
     *
     */
    public function byOther_action($site, $template, $vid = null, $mission = null) {
        $oUser = $this->user;
        $oCustomConfig = $this->getPostJson();
        $modelApp = $this->model('matter\enroll')->setOnlyWriteDbConn(true);
        $modelPage = $this->model('matter\enroll\page');
        $modelCode = $this->model('code\page');

        $template = $this->model('matter\template')->byId($template, $vid);
        if (empty($template->pub_version)) {
            return new \ResponseError('模板已下架');
        }
        if ($template->pub_status === 'N') {
            return new \ResponseError('当前版本未发布，无法使用');
        }

        /* 检查用户行为分 */
        if ($template->coin) {
            $account = $this->model('account')->byId($oUser->id, ['fields' => 'uid,nickname,coin']);
            if ((int) $account->coin < (int) $template->coin) {
                return new \ResponseError('使用模板【' . $template->title . '】需要行为分（' . $template->coin . '），你的行为分（' . $account->coin . '）不足');
            }
        }

        /* 创建活动 */
        $current = time();
        $oNewApp = new \stdClass;
        if (empty($mission)) {
            $oNewApp->pic = $template->pic;
            $oNewApp->summary = $template->summary;
            $oNewApp->use_mission_header = 'N';
            $oNewApp->use_mission_footer = 'N';
        } else {
            $modelMis = $this->model('matter\mission');
            $mission = $modelMis->byId($mission);
            $oNewApp->pic = $mission->pic;
            $oNewApp->summary = $mission->summary;
            $oNewApp->mission_id = $mission->id;
            $oNewApp->use_mission_header = 'Y';
            $oNewApp->use_mission_footer = 'Y';
        }
        $oNewApp->title = empty($oCustomConfig->proto->title) ? $template->title : $oCustomConfig->proto->title;
        $oNewApp->siteid = $site;
        $oNewApp->start_at = $current;
        $oNewApp->scenario = $template->scenario;
        $oNewApp->scenario_config = $template->scenario_config;
        $oNewApp->vote_config = $template->vote_config;
        $oNewApp->data_schemas = $modelApp->escape($template->data_schemas);
        $oNewApp->open_lastroll = $template->open_lastroll;
        $oNewApp->enrolled_entry_page = $template->enrolled_entry_page;
        $oNewApp->template_id = $template->id;
        $oNewApp->template_version = $template->version;
        /* 进入规则 */
        $oEntryRule = new \stdClass;
        $oEntryRule->scope = new \stdClass;
        $oNewApp->entry_rule = json_encode($oEntryRule);

        $oNewApp = $modelApp->create($oUser, $oNewApp);
        $oNewApp->type = 'enroll';

        /* 复制自定义页面 */
        if ($template->pages) {
            foreach ($template->pages as $ep) {
                $newPage = $modelPage->add($oUser, $site, $oNewApp->id);
                $rst = $modelPage->update(
                    'xxt_enroll_page',
                    ['title' => $ep->title, 'name' => $ep->name, 'type' => $ep->type, 'data_schemas' => $modelApp->escape($ep->data_schemas), 'act_schemas' => $modelApp->escape($ep->act_schemas)],
                    ["aid" => $oNewApp->id, "id" => $newPage->id]
                );
                $data = [
                    'title' => $ep->title,
                    'html' => $ep->html,
                    'css' => $ep->css,
                    'js' => $ep->js,
                ];
                $modelCode->modify($newPage->code_id, $data);
            }
        }
        /* 记录操作日志 */
        $this->model('matter\log')->matterOp($site, $oUser, $oNewApp, 'C');

        /* 支付行为分 */
        if ($template->coin) {
            $modelCoin = $this->model('pl\coin\log');
            $creator = $this->model('account')->byId($template->creater, ['fields' => 'uid id,nickname name']);
            $modelCoin->transfer('pl.template.use', $oUser, $creator, (int) $template->coin);
        }
        /* 更新模板使用情况数据 */

        return new \ResponseData($oNewApp);
    }
    /**
     * 通过导入的Excel数据记录创建记录活动
     * 目前就是填空题
     */
    public function byExcel_action($site) {
        if (false === ($oUser = $this->accountUser())) {
            return new \ResponseTimeout();
        }
        $oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
        if (false === $oSite) {
            return new \ObjectNotFoundError();
        }

        $oExcelFile = $this->getPostJson();

        // 文件存储在本地
        $modelFs = $this->model('fs/local', $site, '_resumable');
        $fileUploaded = 'enroll_' . $site . '_' . $oExcelFile->name;
        $filename = $modelFs->rootDir . '/' . $fileUploaded;
        if (!file_exists($filename)) {
            return new \ResponseError('上传文件失败！');
        }

        require_once TMS_APP_DIR . '/lib/PHPExcel.php';
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        //xlsx 行号是数字
        $highestRow = $objWorksheet->getHighestRow();
        //xlsx 列的标识 eg：A,B,C,D,……,Z
        $highestColumn = $objWorksheet->getHighestColumn();
        //把最大的列换成数字
        $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);
        /**
         * 提取数据定义信息
         */
        $schemasByCol = [];
        $record = [];
        for ($col = 0; $col < $highestColumnIndex; $col++) {
            $colTitle = (string) $objWorksheet->getCellByColumnAndRow($col, 1)->getValue();
            $data = new \stdClass;
            if ($colTitle === '备注') {
                $schemasByCol[$col] = 'comment';
            } else if ($colTitle === '标签') {
                $schemasByCol[$col] = 'tags';
            } else if ($colTitle === '审核通过') {
                $schemasByCol[$col] = 'verified';
            } else if ($colTitle === '昵称') {
                $schemasByCol[$col] = false;
            } else if (preg_match("/.*时间/", $colTitle)) {
                $schemasByCol[$col] = 'submit_at';
            } else if (preg_match("/姓名.*/", $colTitle)) {
                $data->id = $this->getTopicId();
                $data->title = $colTitle;
                $data->type = 'shorttext';
                $data->required = 'Y';
                $data->format = 'name';
                $data->unique = 'N';
                $data->_ver = '1';
                $schemasByCol[$col]['id'] = $data->id;
            } else if (preg_match("/手机.*/", $colTitle)) {
                $data->id = $this->getTopicId();
                $data->title = $colTitle;
                $data->type = 'shorttext';
                $data->required = 'Y';
                $data->format = 'mobile';
                $data->unique = 'N';
                $data->_ver = '1';
                $schemasByCol[$col]['id'] = $data->id;
            } else if (preg_match("/邮箱.*/", $colTitle)) {
                $data->id = $this->getTopicId();
                $data->title = $colTitle;
                $data->type = 'shorttext';
                $data->required = 'Y';
                $data->format = 'email';
                $data->unique = 'N';
                $data->_ver = '1';
                $schemasByCol[$col]['id'] = $data->id;
            } else {
                $data->id = $this->getTopicId();
                $data->title = $colTitle;
                $data->type = 'shorttext';
                $data->required = 'Y';
                $data->format = '';
                $data->unique = 'N';
                $data->_ver = '1';
                $schemasByCol[$col]['id'] = $data->id;
            }
            if (!empty((array) $data)) {
                $record[] = $data;
            }
        }
        /* 使用缺省模板 */
        $oConfig = $this->_getSysTemplate('common', 'simple');

        /* 修改模板的配置 */
        $oConfig->schema = [];
        foreach ($oConfig->pages as &$page) {
            if ($page->type === 'I') {
                $page->data_schemas = [];
            } else if ($page->type === 'V') {
                $page->data_schemas = [];
            }
        }
        foreach ($record as $newSchema) {
            $oConfig->schema[] = $newSchema;
            foreach ($oConfig->pages as &$page) {
                if ($page->type === 'I') {
                    $newWrap = new \stdClass;
                    $newWrap->schema = $newSchema;
                    $wrapConfig = new \stdClass;
                    $newWrap->config = $wrapConfig;
                    $page->data_schemas[] = $newWrap;
                } else if ($page->type === 'V') {
                    $newWrap = new \stdClass;
                    $newWrap->schema = $newSchema;
                    $wrapConfig = new \stdClass;
                    $newWrap->config = $wrapConfig;
                    $wrapConfig->id = "V1";
                    $wrapConfig->pattern = "record";
                    $wrapConfig->inline = "N";
                    $wrapConfig->splitLine = "Y";
                    $page->data_schemas[] = $newWrap;
                }
            }
        }
        /* 进入规则 */
        $entryRule = $oConfig->entryRule;
        if (empty($entryRule)) {
            return new \ResponseError('没有获得页面进入规则');
        }
        if (!isset($entryRule->scope)) {
            $entryRule->scope = new \stdClass;
        }

        $modelApp = $this->model('matter\enroll')->setOnlyWriteDbConn(true);
        $appId = uniqid();
        $current = time();
        $oNewApp = new \stdClass;
        /*从站点或任务获得的信息*/
        if (empty($mission)) {
            $oNewApp->pic = $oSite->heading_pic;
            $oNewApp->summary = '';
            $oNewApp->use_mission_header = 'N';
            $oNewApp->use_mission_footer = 'N';
            $mission = null;
        } else {
            $modelMis = $this->model('matter\mission');
            $mission = $modelMis->byId($mission);
            $oNewApp->pic = $mission->pic;
            $oNewApp->summary = $mission->summary;
            $oNewApp->mission_id = $mission->id;
            $oNewApp->use_mission_header = 'Y';
            $oNewApp->use_mission_footer = 'Y';
        }
        /* 添加页面 */
        $modelApp->addPageByTemplate($oUser, $oSite, $mission, $appId, $oConfig, null);
        /* 登记数量限制 */
        if (isset($oConfig->count_limit)) {
            $oNewApp->count_limit = $oConfig->count_limit;
        }
        if (isset($oConfig->enrolled_entry_page)) {
            $oNewApp->enrolled_entry_page = $oConfig->enrolled_entry_page;
        }
        /* 场景设置 */
        if (isset($oConfig->scenarioConfig)) {
            $scenarioConfig = $oConfig->scenarioConfig;
            $oNewApp->scenario_config = json_encode($scenarioConfig);
        }
        /* 投票设置 */
        if (isset($oConfig->voteConfig)) {
            $voteConfig = $oConfig->voteConfig;
            $oNewApp->vote_config = json_encode($voteConfig);
        }
        $oNewApp->scenario = 'common';
        /* create app */
        $title = strtok($oExcelFile->name, '.');
        $oNewApp->id = $appId;
        $oNewApp->siteid = $oSite->id;
        $oNewApp->title = $modelApp->escape($title);
        $oNewApp->start_at = $current;
        $oNewApp->entry_rule = json_encode($entryRule);
        $oNewApp->data_schemas = \TMS_MODEL::toJson($record);

        $oNewApp = $modelApp->create($oUser, $oNewApp);

        /* 存放数据 */
        $records2 = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $record2 = new \stdClass;
            $data2 = new \stdClass;
            for ($col = 0; $col < $highestColumnIndex; $col++) {
                $schema = $schemasByCol[$col];
                if ($schema === false) {
                    continue;
                }
                $value = (string) $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                if ($schema === 'verified') {
                    if (in_array($value, ['Y', '是'])) {
                        $record2->verified = 'Y';
                    } else {
                        $record2->verified = 'N';
                    }
                } else if ($schema === 'comment') {
                    $record2->comment = $value;
                } else if ($schema === 'tags') {
                    $record2->tags = $value;
                } else if ($schema === 'submit_at') {
                    $record2->submit_at = $value;
                } else {
                    $data2->{$schema['id']} = $value;
                }
            }
            $record2->data = $data2;
            $records2[] = $record2;
        }
        /* 保存数据*/
        $this->_persist($oNewApp, $records2);
        /* 记录操作日志 */
        $this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');

        // 删除上传的文件
        $modelFs->delete($fileUploaded);

        return new \ResponseData($oNewApp);
    }
    /**
     * 为创建活动上传的xlsx
     */
    public function uploadExcel_action($site) {
        $dest = '/enroll_' . $site . '_' . $_POST['resumableFilename'];
        $resumable = $this->model('fs/resumable', $site, $dest);

        $resumable->handleRequest($_POST);

        exit;
    }
    /**
     * 根据活动定义文件创建记录活动
     *
     * @param string $site site's id
     * @param string $mission mission's id
     *
     */
    public function byConfig_action($site, $mission = null) {
        $oUser = $this->user;
        $oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
        if (false === $oSite) {
            return new \ObjectNotFoundError();
        }

        $modelApp = $this->model('matter\enroll')->setOnlyWriteDbConn(true);

        $config = $this->getPostJson(false);
        $current = time();
        $oNewApp = new \stdClass;

        /* 从站点或任务获得的信息 */
        if (empty($mission)) {
            $oNewApp->pic = $oSite->heading_pic;
            $oNewApp->summary = '';
            $oNewApp->use_mission_header = 'N';
            $oNewApp->use_mission_footer = 'N';
            $mission = null;
        } else {
            $modelMis = $this->model('matter\mission');
            $mission = $modelMis->byId($mission);
            $oNewApp->pic = $mission->pic;
            $oNewApp->summary = $mission->summary;
            $oNewApp->mission_id = $mission->id;
            $oNewApp->use_mission_header = 'Y';
            $oNewApp->use_mission_footer = 'Y';
        }
        $appId = uniqid();
        $oCustomConfig = isset($config->customConfig) ? $modelApp->escape($config->customConfig) : null;
        !empty($config->scenario) && $oNewApp->scenario = $modelApp->escape($config->scenario);
        /* 登记数量限制 */
        if (isset($config->count_limit)) {
            $oNewApp->count_limit = $modelApp->escape($config->count_limit);
        }

        if (!empty($config->pages) && !empty($config->entryRule)) {
            $modelApp->addPageByTemplate($oUser, $site, $mission, $appId, $config, $oCustomConfig);
            /*进入规则*/
            $entryRule = $config->entryRule;
            if (!empty($entryRule)) {
                if (!isset($entryRule->scope)) {
                    $entryRule->scope = new \stdClass;
                }
            }
            if (isset($config->enrolled_entry_page)) {
                $oNewApp->enrolled_entry_page = $modelApp->escape($config->enrolled_entry_page);
            }
            /*场景设置*/
            if (isset($config->scenarioConfig)) {
                $scenarioConfig = $config->scenarioConfig;
                $oNewApp->scenario_config = $modelApp->escape(\TMS_MODEL::toJson($scenarioConfig));
            }
            /*投票设置*/
            if (isset($config->voteConfig)) {
                $voteConfig = $config->voteConfig;
                $oNewApp->vote_config = $modelApp->escape(\TMS_MODEL::toJson($voteConfig));
            }
        } else {
            $entryRule = $this->_addBlankPage($oUser, $oSite->id, $appId);
            if (!empty($entryRule)) {
                if (!isset($entryRule['scope'])) {
                    $entryRule['scope'] = new \stdClass;
                }
            }
        }
        if (empty($entryRule)) {
            return new \ResponseError('没有获得页面进入规则');
        }

        /* create app */
        $oNewApp->id = $appId;
        $oNewApp->siteid = $oSite->id;
        $oNewApp->title = empty($oCustomConfig->proto->title) ? '新记录活动' : $modelApp->escape($oCustomConfig->proto->title);
        $oNewApp->start_at = $current;
        $oNewApp->entry_rule = json_encode($entryRule);
        isset($config) && $oNewApp->data_schemas = $modelApp->escape(\TMS_MODEL::toJson($config->schema));

        $oNewApp = $modelApp->create($oUser, $oNewApp);

        /* 保存数据 */
        $records = $config->records;
        $this->_persist($oNewApp, $records);

        /* 记录操作日志 */
        $this->model('matter\log')->matterOp($oSite->id, $user, $oNewApp, 'C');

        return new \ResponseData($oNewApp);
    }
    /**
     * 保存数据
     */
    private function _persist($oApp, &$records) {
        $current = time();
        $modelApp = $this->model('matter\enroll');
        $modelRec = $this->model('matter\enroll\record');
        $enrollKeys = [];

        foreach ($records as $oRecord) {
            $ek = $modelRec->genKey($oApp->siteid, $oApp->id);

            $aNewRec = array();
            $aNewRec['aid'] = $oApp->id;
            $aNewRec['siteid'] = $oApp->siteid;
            $aNewRec['rid'] = $oApp->appRound->rid;
            $aNewRec['enroll_key'] = $ek;
            $aNewRec['enroll_at'] = $current;
            $aNewRec['verified'] = isset($oRecord->verified) ? $modelApp->escape($oRecord->verified) : 'N';
            $aNewRec['comment'] = isset($oRecord->comment) ? $modelApp->escape($oRecord->comment) : '';
            if (isset($oRecord->tags)) {
                $aNewRec['tags'] = $modelApp->escape($oRecord->tags);
                $modelApp->updateTags($oApp->id, $oRecord->tags);
            }
            $id = $modelRec->insert('xxt_enroll_record', $aNewRec, true);
            $aNewRec['id'] = $id;
            /**
             * 登记数据
             */
            if (isset($oRecord->data)) {
                //
                $jsonData = $modelApp->escape($modelRec->toJson($oRecord->data));
                $modelRec->update('xxt_enroll_record', ['data' => $jsonData], "enroll_key='$ek'");
                $enrollKeys[] = $ek;
                //
                foreach ($oRecord->data as $n => $v) {
                    if (is_object($v) || is_array($v)) {
                        $v = $modelRec->toJson($v);
                    }
                    $cd = [
                        'aid' => $oApp->id,
                        'rid' => $oApp->appRound->rid,
                        'record_id' => $oRecord->id,
                        'enroll_key' => $ek,
                        'schema_id' => $n,
                        'value' => $modelApp->escape($v),
                    ];
                    $modelRec->insert('xxt_enroll_record_data', $cd, false);
                }
            }
        }

        return $enrollKeys;
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
            foreach ($oConfig->pages as &$oPage) {
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
     * 添加空页面
     */
    private function _addBlankPage($oUser, $siteId, $appid) {
        $current = time();
        $modelPage = $this->model('matter\enroll\page');
        /* form page */
        $page = [
            'title' => '填写信息页',
            'type' => 'I',
            'name' => 'z' . $current,
        ];
        $page = $modelPage->add($oUser, $siteId, $appid, $page);
        /*entry rules*/
        $entryRule = [
            'otherwise' => ['entry' => $page->name],
        ];
        /* result page */
        $page = [
            'title' => '查看结果页',
            'type' => 'V',
            'name' => 'z' . ($current + 1),
        ];
        $modelPage->add($oUser, $siteId, $appid, $page);

        return $entryRule;
    }
}