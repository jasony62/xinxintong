define(['frame', 'schema'], function (ngApp, schemaLib) {
    'use strict';

    function fnWatchConfig($scope, oConfig, oConfigsModified) {
        var $configScope;
        $configScope = $scope.$new(true);
        $configScope.config = oConfig;
        if (oConfig.id)
            oConfigsModified[oConfig.id] = false;
        /* 参照轮次规则 */
        if (oConfig.time && $scope.app.roundCron) {
            if (oConfig.time.offset.matter.type === 'RC' && oConfig.time.offset.matter.id && $scope.app.roundCron && $scope.app.roundCron.length) {
                var rules = $scope.app.roundCron;
                for (var i = 0, ii = rules.length; i < ii; i++) {
                    if (rules[i].id === oConfig.time.offset.matter.id) {
                        var $parse = angular.injector(['ng']).get('$parse');
                        $parse('time.surface.offset.matter').assign(oConfig, {
                            name: rules[i].name
                        });
                        break;
                    }
                }
            }
        }
        $configScope.$watch('config', function (nv, ov) {
            if (nv && nv !== ov && nv.id) {
                oConfigsModified[nv.id] = true;
            }
        }, true);
    }
    /**
     * 任务配置
     */
    var TaskConfig = function ($scope, oApp, taskType) {
        var http2 = angular.injector(['http.ui.xxt']).get('http2');
        var noticebox = angular.injector(['notice.ui.xxt']).get('noticebox');
        var _aConfigs, _oConfigsModified, _oTaskTimer;
        var ucTaskType = taskType.replace(/^(.)/, function (c) {
            return c.toUpperCase();
        });

        $scope.configs = _aConfigs = [];
        $scope.configsModified = _oConfigsModified = {};
        $scope.taskTimer = _oTaskTimer = new TaskTimer(oApp, taskType);
        this.add = function () {
            _aConfigs.push({
                enabled: 'N'
            });
        };
        this.del = function (oConfig) {
            noticebox.confirm('删除设置目标环节，确定？').then(function () {
                if (oConfig.id) {
                    function fnAfterDoDelTimers() {
                        http2.post('/rest/pl/fe/matter/enroll/update' + ucTaskType + 'Config?app=' + oApp.id, {
                            method: 'delete',
                            data: oConfig
                        }).then(function () {
                            $scope.$apply(function () {
                                _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                                delete _oConfigsModified[oConfig.id];
                            });
                        });
                    }
                    _oTaskTimer.delAll(oConfig, fnAfterDoDelTimers);
                } else {
                    $scope.$apply(function () {
                        _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                    });
                }
            });
        };
        this.save = function (oConfig) {
            http2.post('/rest/pl/fe/matter/enroll/update' + ucTaskType + 'Config?app=' + oApp.id, {
                method: 'save',
                data: oConfig
            }).then(function (rsp) {
                http2.merge(oConfig, rsp.data, ['surface']);
                fnWatchConfig($scope, oConfig, _oConfigsModified);
                /*更新定时任务*/
                _oTaskTimer.upd(oConfig);
                noticebox.success('保存成功！');
            });
        };


        if (oApp[taskType + 'Config'] && oApp[taskType + 'Config'].length) {
            oApp[taskType + 'Config'].forEach(function (oConfig, index) {
                var oCopied;
                oCopied = angular.copy(oConfig);
                _aConfigs.push(oCopied);
                fnWatchConfig($scope, oCopied, _oConfigsModified);
                /* 获得任务提醒 */
                _oTaskTimer.getTimers(oCopied);
            });
        }
    };
    var _srvTimer; // 定时提醒服务实例
    /**
     * 任务定时提醒
     */
    var TaskTimer = function (oApp, taskType, oCachedTimers) {
        /* 更新参数设置 */
        function fnSetTimerTaskArgs(oConfig, taskEventName) {
            var oTaskArgs, oReceiver, oTeamsById;
            oTaskArgs = {
                taskConfig: {
                    id: oConfig.id,
                    type: taskType,
                    'event': taskEventName
                }
            };
            if (oConfig.role && oConfig.role.groups) {
                oTaskArgs.receiver = oReceiver = {
                    scope: 'group',
                    app: {
                        id: oApp.groupApp.id,
                        title: oApp.groupApp.title
                    }
                };
                if (oConfig.role.groups.length) {
                    oTeamsById = {};
                    oApp.groupApp.teams.forEach(function (oTeam) {
                        oTeamsById[oTeam.team_id] = oTeam;
                    });
                    oReceiver.app.teams = {
                        id: [],
                        title: []
                    };
                    oConfig.role.groups.forEach(function (id) {
                        oReceiver.app.teams.id.push(id);
                        oReceiver.app.teams.title.push(oTeamsById[id].title);
                    });
                }
            }
            return oTaskArgs;
        }

        this.app = oApp;
        this.taskType = taskType;
        this.timers = oCachedTimers ? oCachedTimers : {};
        this.getTimers = function (oConfig) {
            var _this = this;
            /* 获得任务提醒 */
            _srvTimer.list(oApp, 'remind', {
                id: oConfig.id,
                type: taskType
            }).then(function (timers) {
                _this.timers[oConfig.id] = {};
                timers.forEach(function (oTimer) {
                    var oTaskConfig;
                    if (oTaskConfig = oTimer.task.task_arguments.taskConfig) {
                        if (oTaskConfig.type === taskType) {
                            _this.timers[oConfig.id][oTaskConfig.event] = oTimer;
                        }
                    }
                });
            });
        };
        this.add = function (oConfig, taskEventName) {
            var _this, oTaskArgs, oProto;

            _this = this;
            oTaskArgs = fnSetTimerTaskArgs(oConfig, taskEventName);
            oProto = {
                enabled: 'Y',
                offset_matter_type: 'RC',
                offset_matter_id: oConfig.time.offset.matter.id
            };
            oProto.offset_hour = oConfig[taskEventName].time.value;
            _srvTimer.add(oApp, null, 'remind', oTaskArgs, oProto).then(function (oNewTimer) {
                !_this.timers[oConfig.id] && (_this.timers[oConfig.id] = {});
                _this.timers[oConfig.id][taskEventName] = oNewTimer;
            });
        };
        this.del = function (oConfig, taskEventName) {
            var _this, oTimer;
            _this = this;
            if (oTimer = this.timers[oConfig.id][taskEventName]) {
                _srvTimer.remove(oTimer).then(function () {
                    delete _this.timers[oConfig.id][taskEventName];
                });
            }
        };
        /*删除定时任务*/
        this.delAll = function (oConfig, fnAfterDo) {
            if (this.timers[oConfig.id]) {
                (function (oTimers) {
                    var timerNames = Object.keys(oTimers[oConfig.id]);
                    var i = 0;

                    function fnDoOnce() {
                        var oTimer;
                        oTimer = oTimers[oConfig.id][timerNames[i]];
                        _srvTimer.remove(oTimer, true).then(function () {
                            delete oTimers[oConfig.id][timerNames[i]];
                            if (i + 1 < timerNames.length) {
                                i++;
                                fnDoOnce();
                            } else {
                                fnAfterDo();
                            }
                        });
                    }
                    timerNames.length ? fnDoOnce() : fnAfterDo();
                })(this.timers);
            } else {
                fnAfterDo();
            }
        };
        /*更新定时任务*/
        this.upd = function (oConfig) {
            var _this = this;
            if (_this.timers[oConfig.id]) {
                ['start', 'end'].forEach(function (taskEventName) {
                    var oTimer;
                    if (oTimer = _this.timers[oConfig.id][taskEventName]) {
                        oTimer.task.task_arguments = fnSetTimerTaskArgs(oConfig, taskEventName);
                        oTimer.task.offset_hour = oConfig[taskEventName].time.value;
                        _srvTimer.update(oTimer);
                    }

                });
            }
        }
    };

    ngApp.provider.controller('ctrlTask', ['$scope', '$parse', 'srvTimerNotice', 'tkRoundCron', function ($scope, $parse, srvTimerNotice, tkRndCron) {
        /* 设置偏移的素材 */
        $scope.setTimeOffsetRoundCron = function (oConfig) {
            tkRndCron.choose($scope.app).then(function (oRule) {
                $parse('time.offset.matter').assign(oConfig, {
                    id: oRule.id,
                    type: 'RC'
                });
                $parse('time.surface.offset.matter').assign(oConfig, {
                    name: oRule.name
                });
            });
        };
        /* 定时任务服务 */
        _srvTimer = srvTimerNotice;
    }]);
    ngApp.provider.controller('ctrlTaskBaseline', ['$scope', function ($scope) {
        $scope.$watch('app', function (oApp) {
            if (!oApp) return;
            $scope.taskConfig = new TaskConfig($scope, oApp, 'baseline');
        });
    }]);
    ngApp.provider.controller('ctrlTaskQuestion', ['$scope', function ($scope) {
        $scope.$watch('app', function (oApp) {
            if (!oApp) return;
            $scope.taskConfig = new TaskConfig($scope, oApp, 'question');
        });
    }]);
    ngApp.provider.controller('ctrlTaskAnswer', ['$scope', function ($scope) {
        $scope.$watch('app', function (oApp) {
            if (!oApp) return;
            $scope.answerSchemas = [];
            oApp.dataSchemas.forEach(function (oSchema) {
                if ('multitext' === oSchema.type) {
                    $scope.answerSchemas.push(oSchema);
                }
            });
            $scope.taskConfig = new TaskConfig($scope, oApp, 'answer');
        });
    }]);
    ngApp.provider.controller('ctrlTaskVote', ['$scope', 'http2', 'noticebox', 'srvEnrollApp', function ($scope, http2, noticebox, srvEnlApp) {
        $scope.$watch('app', function (oApp) {
            if (!oApp) return;
            $scope.votingSchemas = [];
            oApp.dataSchemas.forEach(function (oSchema) {
                if (!/html|single|multiplue|score/.test(oSchema.type)) {
                    $scope.votingSchemas.push(oSchema);
                }
            });
            $scope.taskConfig = new TaskConfig($scope, oApp, 'vote');
        });
    }]);
    ngApp.provider.controller('ctrlTaskScore', ['$scope', '$uibModal', 'http2', 'noticebox', 'srvEnrollApp', 'srvEnrollSchema', function ($scope, $uibModal, http2, noticebox, srvEnlApp, srvEnlSch) {
        function fnPostScoreConfig(method, oConfig) {
            http2.post('/rest/pl/fe/matter/enroll/updateScoreConfig?app=' + $scope.app.id, {
                method: method,
                data: oConfig
            }).then(function (rsp) {
                if (rsp.data.config) {
                    switch (method) {
                        case 'save':
                            http2.merge(oConfig, rsp.data.config);
                            fnWatchConfig($scope, oConfig, _oConfigsModified);
                            break;
                        case 'delete':
                            _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                            delete _oConfigsModified[oConfig.id];
                            break;
                    }
                }
                if (rsp.data.updatedSchemas && Object.keys(rsp.data.updatedSchemas).length) {
                    $scope.app.dataSchemas.forEach(function (oSchema) {
                        if (rsp.data.updatedSchemas[oSchema.id])
                            http2.merge(oSchema, rsp.data.updatedSchemas[oSchema.id]);
                    });
                }
                noticebox.success('已保存修改！');
            });
        }

        var _aConfigs, _oConfigsModified;
        $scope.configs = _aConfigs = [];
        $scope.configsModified = _oConfigsModified = {};
        $scope.addConfig = function () {
            _aConfigs.push({});
        };
        $scope.delConfig = function (oConfig) {
            noticebox.confirm('删除投票环节，确定？').then(function () {
                if (oConfig.id) {
                    fnPostScoreConfig('delete', oConfig);
                } else {
                    _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                }
            });
        };
        /**
         * 设置题目的打分活动
         */
        $scope.setScoreApp = function (oConfig) {
            var _oSourceApp, _aSourceSchemas;
            if (!oConfig || !oConfig.schemas || oConfig.schemas.length === 0) {
                return;
            }
            _oSourceApp = $scope.app;
            _aSourceSchemas = _oSourceApp.dataSchemas.filter(function (oSourceSchema) {
                return oConfig.schemas.indexOf(oSourceSchema.id) !== -1;
            });
            if (_aSourceSchemas.length === 0) {
                return;
            }
            http2.post('/rest/script/time', {
                html: {
                    'score': '/views/default/pl/fe/matter/enroll/component/schema/setScoreApp'
                }
            }).then(function (rsp) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/schema/setScoreApp.html?_=' + rsp.data.html.score.time,
                    controller: ['$scope', '$uibModalInstance', 'noticebox', 'tkEnrollApp', 'tkDataSchema', function ($scope2, $mi, noticebox, tkEnlApp, tkSchema) {
                        function fnNewScoreSchema(oSourceSchema) {
                            var oNewScoreSchema = {
                                title: oSourceSchema.title,
                                dsSchema: {
                                    schema: {
                                        id: oSourceSchema.id
                                    }
                                }
                            };
                            $scope2.addOption(oNewScoreSchema);
                            $scope2.addOption(oNewScoreSchema);
                            return oNewScoreSchema;
                        }

                        var _oResult, _oScoreApp, _aAddedScoreSchemas;

                        $scope2.disabled = false;
                        $scope2.result = _oResult = {};
                        $scope2.$on('xxt.editable.remove', function (e, oOp) {
                            _oResult.schema.ops.splice(_oResult.schema.ops.indexOf(oOp), 1);
                        });
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                        $scope2.addOption = function (oSchema) {
                            var oNewOp;
                            oNewOp = schemaLib.addOption(oSchema);
                            oNewOp.l = '打分项';
                        };
                        $scope2.ok = function () {
                            if (_oScoreApp) {
                                if (_aAddedScoreSchemas) {
                                    http2.post('/rest/pl/fe/matter/enroll/schema/scoreBySchema?sourceApp=' + _oSourceApp.id, _aAddedScoreSchemas).then(function (rsp) {
                                        var newScoreSchemas;
                                        if (rsp.data) {
                                            angular.forEach(rsp.data, function (oScoreSchema, sourceSchemaId) {
                                                _oScoreApp.dataSchemas.push(oScoreSchema);
                                                for (var i = _aSourceSchemas.length - 1; i >= 0; i--) {
                                                    if (_aSourceSchemas[i].id === sourceSchemaId) {
                                                        _aSourceSchemas[i].scoreApp = {
                                                            id: _oScoreApp.id,
                                                            schema: {
                                                                id: oScoreSchema.id
                                                            }
                                                        };
                                                        break;
                                                    }
                                                }
                                            });
                                            /* 更新打分活动 */
                                            tkEnlApp.update(_oScoreApp, {
                                                title: _oResult.title,
                                                dataSchemas: _oScoreApp.dataSchemas
                                            }).then(function () {
                                                tkEnlApp.update(_oSourceApp, {
                                                    dataSchemas: _oSourceApp.dataSchemas
                                                }).then(function () {
                                                    $mi.close();
                                                });
                                            });
                                        }
                                    });
                                } else {
                                    /* 更新活动 */
                                    tkEnlApp.update(_oScoreApp, {
                                        title: _oResult.title,
                                        dataSchemas: _oScoreApp.dataSchemas
                                    }).then(function () {
                                        $mi.close();
                                    });
                                }
                            } else {
                                /* 新建活动 */
                                http2.post('/rest/pl/fe/matter/enroll/create/asScoreBySchema?app=' + _oSourceApp.id, _oResult).then(function (rsp) {
                                    var oNewSchemas;
                                    _oScoreApp = rsp.data.app;
                                    oConfig.scoreApp = {
                                        id: _oScoreApp.id
                                    };
                                    oNewSchemas = rsp.data.schemas;
                                    _aSourceSchemas.forEach(function (oSourceSchema) {
                                        if (oNewSchemas[oSourceSchema.id]) {
                                            http2.merge(oSourceSchema, oNewSchemas[oSourceSchema.id]);
                                        }
                                    });
                                    $mi.close(oConfig);
                                });
                            }
                        };
                        if (oConfig.scoreApp && oConfig.scoreApp.id) {
                            tkEnlApp.get(oConfig.scoreApp.id).then(function (oScoreApp) {
                                var scoreSchemas, linkedSourceSchemaIds, newSourceSchemaIds;
                                _oScoreApp = oScoreApp;
                                _oResult.title = oScoreApp.title;
                                scoreSchemas = oScoreApp.dataSchemas.filter(function (oScoreSchema) {
                                    return oScoreSchema.dsSchema && oScoreSchema.dsSchema.app && oScoreSchema.dsSchema.app.id === _oSourceApp.id && oScoreSchema.dsSchema.schema;
                                });
                                if (_aSourceSchemas.length) {
                                    linkedSourceSchemaIds = scoreSchemas.map(function (oScoreSchema) {
                                        return oScoreSchema.dsSchema.schema.id;
                                    });
                                    _aSourceSchemas.forEach(function (oSourceSchema) {
                                        var oNewScoreSchema;
                                        if (linkedSourceSchemaIds.indexOf(oSourceSchema.id) === -1) {
                                            oNewScoreSchema = fnNewScoreSchema(oSourceSchema);
                                            scoreSchemas.push(oNewScoreSchema);
                                            if (!_aAddedScoreSchemas) _aAddedScoreSchemas = [];
                                            _aAddedScoreSchemas.push(oNewScoreSchema);
                                        }
                                    });
                                }
                                _oResult.schemas = scoreSchemas
                            });
                        } else {
                            _oResult.title = _oSourceApp.title + '（打分）';
                            _oResult.schemas = [];
                            _aSourceSchemas.forEach(function (oSourceSchema) {
                                _oResult.schemas.push(fnNewScoreSchema(oSourceSchema));
                            });
                        }
                    }],
                    backdrop: 'static',
                }).result.then(function () {
                    $scope.save(oConfig);
                });
            });
        };
        $scope.unlinkScoreApp = function (oConfig) {
            noticebox.confirm('解除和打分活动的关联，确定？').then(function () {
                var _oSourceApp;
                if (oConfig && oConfig.schemas && oConfig.schemas.length) {
                    _oSourceApp = $scope.app;
                    _oSourceApp.dataSchemas.forEach(function (oSourceSchema) {
                        if (oConfig.schemas.indexOf(oSourceSchema.id) !== -1) {
                            delete oSourceSchema.scoreApp;
                            srvEnlSch.update(oSourceSchema, null, 'scoreApp');
                        }
                    });
                }
                srvEnlSch.submitChange().then(function () {
                    delete oConfig.scoreApp;
                    $scope.save(oConfig);
                });
            });
        };
        $scope.save = function (oConfig) {
            fnPostScoreConfig('save', oConfig);
        };
        $scope.$watch('app', function (oApp) {
            if (!oApp) return;
            $scope.scoreSchemas = [];
            oApp.dataSchemas.forEach(function (oSchema) {
                if (!/html|single|multiplue|score/.test(oSchema.type)) {
                    $scope.scoreSchemas.push(oSchema);
                }
            });
            if (oApp.scoreConfig && oApp.scoreConfig.length) {
                oApp.scoreConfig.forEach(function (oConfig, index) {
                    var oCopied;
                    oCopied = angular.copy(oConfig);
                    _aConfigs.push(oCopied);
                    fnWatchConfig($scope, oCopied, _oConfigsModified);
                });
            }
        });
    }]);
});