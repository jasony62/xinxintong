define(['frame', 'schema'], function(ngApp, schemaLib) {
    'use strict';
    ngApp.provider.controller('ctrlTask', [function() {}]);
    ngApp.provider.controller('ctrlTaskAnswer', ['$scope', '$parse', 'http2', 'noticebox', 'srvEnrollApp', function($scope, $parse, http2, noticebox, srvEnlApp) {
        function fnWatchConfig(oConfig) {
            var $configScope;
            $configScope = $scope.$new(true);
            $configScope.config = oConfig;
            if (oConfig.id)
                _oConfigsModified[oConfig.id] = false;
            $configScope.$watch('config', function(nv, ov) {
                if (nv && nv !== ov && nv.id) {
                    _oConfigsModified[nv.id] = true;
                }
            }, true);
        }
        var _aConfigs, _oConfigsModified;
        $scope.configs = _aConfigs = [];
        $scope.configsModified = _oConfigsModified = {};
        $scope.addConfig = function() {
            _aConfigs.push({});
        };
        $scope.delConfig = function(oConfig) {
            noticebox.confirm('删除回答环节，确定？').then(function() {
                if (oConfig.id) {
                    http2.post('/rest/pl/fe/matter/enroll/updateAnswerConfig?app=' + $scope.app.id, { method: 'delete', data: oConfig }).then(function() {
                        _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                        delete _oConfigsModified[oConfig.id];
                    });
                } else {
                    _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                }
            });
        };
        $scope.addAnswerGroup = function(oConfig) {
            if (!$parse('role.groups')(oConfig)) {
                $parse('role.groups').assign(oConfig, [{}]);
            } else {
                $parse('role.groups')(oConfig).push({});
            }
        };
        $scope.delAnswerGroup = function(oConfig, oAnswerGroup) {
            oConfig.role.groups.splice(oConfig.role.groups.indexOf(oAnswerGroup), 1);
        };
        $scope.save = function(oConfig) {
            http2.post('/rest/pl/fe/matter/enroll/updateAnswerConfig?app=' + $scope.app.id, { method: 'save', data: oConfig }).then(function(rsp) {
                http2.merge(oConfig, rsp.data);
                fnWatchConfig(oConfig);
                noticebox.success('保存成功！');
            });
        };
        srvEnlApp.get().then(function(oApp) {
            $scope.answerSchemas = oApp.dataSchemas.filter(function(oSchema) { return oSchema.type === 'multitext' && oSchema.cowork === 'Y'; });
            if (oApp.answerConfig && oApp.answerConfig.length) {
                oApp.answerConfig.forEach(function(oConfig, index) {
                    var oCopied;
                    oCopied = angular.copy(oConfig);
                    _aConfigs.push(oCopied);
                    fnWatchConfig(oCopied);
                });
            }
        });
    }]);
    ngApp.provider.controller('ctrlTaskVote', ['$scope', '$parse', 'http2', 'noticebox', 'srvEnrollApp', function($scope, $parse, http2, noticebox, srvEnlApp) {
        function fnWatchConfig(oConfig) {
            var $configScope;
            $configScope = $scope.$new(true);
            $configScope.config = oConfig;
            if (oConfig.id)
                _oConfigsModified[oConfig.id] = false;
            $configScope.$watch('config', function(nv, ov) {
                if (nv && nv !== ov && nv.id) {
                    _oConfigsModified[nv.id] = true;
                }
            }, true);
        }
        var _aConfigs, _oConfigsModified;
        $scope.configs = _aConfigs = [];
        $scope.configsModified = _oConfigsModified = {};
        $scope.addConfig = function() {
            _aConfigs.push({});
        };
        $scope.delConfig = function(oConfig) {
            noticebox.confirm('删除投票环节，确定？').then(function() {
                if (oConfig.id) {
                    http2.post('/rest/pl/fe/matter/enroll/updateVoteConfig?app=' + $scope.app.id, { method: 'delete', data: oConfig }).then(function() {
                        _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                        delete _oConfigsModified[oConfig.id];
                    });
                } else {
                    _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                }
            });
        };
        $scope.addVoteGroup = function(oConfig) {
            if (!$parse('role.groups')(oConfig)) {
                $parse('role.groups').assign(oConfig, [{}]);
            } else {
                $parse('role.groups')(oConfig).push({});
            }
        };
        $scope.delVoteGroup = function(oConfig, oVoteGroup) {
            oConfig.role.groups.splice(oConfig.role.groups.indexOf(oVoteGroup), 1);
        };
        $scope.save = function(oConfig) {
            http2.post('/rest/pl/fe/matter/enroll/updateVoteConfig?app=' + $scope.app.id, { method: 'save', data: oConfig }).then(function(rsp) {
                http2.merge(oConfig, rsp.data);
                fnWatchConfig(oConfig);
                noticebox.success('保存成功！');
            });
        };
        srvEnlApp.get().then(function(oApp) {
            $scope.votingSchemas = [];
            oApp.dataSchemas.forEach(function(oSchema) {
                if (!/html|single|multiplue|score/.test(oSchema.type)) {
                    $scope.votingSchemas.push(oSchema);
                }
            });
            if (oApp.voteConfig && oApp.voteConfig.length) {
                oApp.voteConfig.forEach(function(oConfig, index) {
                    var oCopied;
                    oCopied = angular.copy(oConfig);
                    _aConfigs.push(oCopied);
                    fnWatchConfig(oCopied);
                });
            }
        });
    }]);
    ngApp.provider.controller('ctrlTaskScore', ['$scope', '$parse', '$uibModal', 'http2', 'noticebox', 'srvEnrollApp', 'srvEnrollSchema', function($scope, $parse, $uibModal, http2, noticebox, srvEnlApp, srvEnlSch) {
        function fnWatchConfig(oConfig) {
            var $configScope;
            $configScope = $scope.$new(true);
            $configScope.config = oConfig;
            if (oConfig.id)
                _oConfigsModified[oConfig.id] = false;
            $configScope.$watch('config', function(nv, ov) {
                if (nv && nv !== ov && nv.id) {
                    _oConfigsModified[nv.id] = true;
                }
            }, true);
        }

        function fnPostScoreConfig(method, oConfig) {
            http2.post('/rest/pl/fe/matter/enroll/updateScoreConfig?app=' + $scope.app.id, { method: method, data: oConfig }).then(function(rsp) {
                if (rsp.data.config) {
                    switch (method) {
                        case 'save':
                            http2.merge(oConfig, rsp.data.config);
                            fnWatchConfig(oConfig);
                            break;
                        case 'delete':
                            _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                            delete _oConfigsModified[oConfig.id];
                            break;
                    }
                }
                if (rsp.data.updatedSchemas && Object.keys(rsp.data.updatedSchemas).length) {
                    $scope.app.dataSchemas.forEach(function(oSchema) {
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
        $scope.addConfig = function() {
            _aConfigs.push({});
        };
        $scope.delConfig = function(oConfig) {
            noticebox.confirm('删除投票环节，确定？').then(function() {
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
        $scope.setScoreApp = function(oConfig) {
            var _oSourceApp, _aSourceSchemas;
            if (!oConfig || !oConfig.schemas || oConfig.schemas.length === 0) {
                return;
            }
            _oSourceApp = $scope.app;
            _aSourceSchemas = _oSourceApp.dataSchemas.filter(function(oSourceSchema) { return oConfig.schemas.indexOf(oSourceSchema.id) !== -1; });
            if (_aSourceSchemas.length === 0) {
                return;
            }
            http2.post('/rest/script/time', { html: { 'score': '/views/default/pl/fe/matter/enroll/component/schema/setScoreApp' } }).then(function(rsp) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/schema/setScoreApp.html?_=' + rsp.data.html.score.time,
                    controller: ['$scope', '$uibModalInstance', 'noticebox', 'tkEnrollApp', 'tkDataSchema', function($scope2, $mi, noticebox, tkEnlApp, tkSchema) {
                        function fnNewScoreSchema(oSourceSchema) {
                            var oNewScoreSchema = {
                                title: oSourceSchema.title,
                                dsSchema: { schema: { id: oSourceSchema.id } }
                            };
                            $scope2.addOption(oNewScoreSchema);
                            $scope2.addOption(oNewScoreSchema);
                            return oNewScoreSchema;
                        }

                        var _oResult, _oScoreApp, _aAddedScoreSchemas;

                        $scope2.disabled = false;
                        $scope2.result = _oResult = {};
                        $scope2.$on('xxt.editable.remove', function(e, oOp) {
                            _oResult.schema.ops.splice(_oResult.schema.ops.indexOf(oOp), 1);
                        });
                        $scope2.cancel = function() { $mi.dismiss(); };
                        $scope2.addOption = function(oSchema) {
                            var oNewOp;
                            oNewOp = schemaLib.addOption(oSchema);
                            oNewOp.l = '打分项';
                        };
                        $scope2.ok = function() {
                            if (_oScoreApp) {
                                if (_aAddedScoreSchemas) {
                                    http2.post('/rest/pl/fe/matter/enroll/schema/scoreBySchema?sourceApp=' + _oSourceApp.id, _aAddedScoreSchemas).then(function(rsp) {
                                        var newScoreSchemas;
                                        if (rsp.data) {
                                            angular.forEach(rsp.data, function(oScoreSchema, sourceSchemaId) {
                                                _oScoreApp.dataSchemas.push(oScoreSchema);
                                                for (var i = _aSourceSchemas.length - 1; i >= 0; i--) {
                                                    if (_aSourceSchemas[i].id === sourceSchemaId) {
                                                        _aSourceSchemas[i].scoreApp = { id: _oScoreApp.id, schema: { id: oScoreSchema.id } };
                                                        break;
                                                    }
                                                }
                                            });
                                            /* 更新打分活动 */
                                            tkEnlApp.update(_oScoreApp, { title: _oResult.title, dataSchemas: _oScoreApp.dataSchemas }).then(function() {
                                                tkEnlApp.update(_oSourceApp, { dataSchemas: _oSourceApp.dataSchemas }).then(function() {
                                                    $mi.close();
                                                });
                                            });
                                        }
                                    });
                                } else {
                                    /* 更新活动 */
                                    tkEnlApp.update(_oScoreApp, { title: _oResult.title, dataSchemas: _oScoreApp.dataSchemas }).then(function() {
                                        $mi.close();
                                    });
                                }
                            } else {
                                /* 新建活动 */
                                http2.post('/rest/pl/fe/matter/enroll/create/asScoreBySchema?app=' + _oSourceApp.id, _oResult).then(function(rsp) {
                                    var oNewSchemas;
                                    _oScoreApp = rsp.data.app;
                                    oConfig.scoreApp = { id: _oScoreApp.id };
                                    oNewSchemas = rsp.data.schemas;
                                    _aSourceSchemas.forEach(function(oSourceSchema) {
                                        if (oNewSchemas[oSourceSchema.id]) {
                                            http2.merge(oSourceSchema, oNewSchemas[oSourceSchema.id]);
                                        }
                                    });
                                    $mi.close(oConfig);
                                });
                            }
                        };
                        if (oConfig.scoreApp && oConfig.scoreApp.id) {
                            tkEnlApp.get(oConfig.scoreApp.id).then(function(oScoreApp) {
                                var scoreSchemas, linkedSourceSchemaIds, newSourceSchemaIds;
                                _oScoreApp = oScoreApp;
                                _oResult.title = oScoreApp.title;
                                scoreSchemas = oScoreApp.dataSchemas.filter(function(oScoreSchema) { return oScoreSchema.dsSchema && oScoreSchema.dsSchema.app && oScoreSchema.dsSchema.app.id === _oSourceApp.id && oScoreSchema.dsSchema.schema; });
                                if (_aSourceSchemas.length) {
                                    linkedSourceSchemaIds = scoreSchemas.map(function(oScoreSchema) { return oScoreSchema.dsSchema.schema.id; });
                                    _aSourceSchemas.forEach(function(oSourceSchema) {
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
                            _aSourceSchemas.forEach(function(oSourceSchema) {
                                _oResult.schemas.push(fnNewScoreSchema(oSourceSchema));
                            });
                        }
                    }],
                    backdrop: 'static',
                }).result.then(function() {
                    $scope.save(oConfig);
                });
            });
        };
        $scope.unlinkScoreApp = function(oConfig) {
            noticebox.confirm('解除和打分活动的关联，确定？').then(function() {
                var _oSourceApp;
                if (oConfig && oConfig.schemas && oConfig.schemas.length) {
                    _oSourceApp = $scope.app;
                    _oSourceApp.dataSchemas.forEach(function(oSourceSchema) {
                        if (oConfig.schemas.indexOf(oSourceSchema.id) !== -1) {
                            delete oSourceSchema.scoreApp;
                            srvEnlSch.update(oSourceSchema, null, 'scoreApp');
                        }
                    });
                }
                srvEnlSch.submitChange().then(function() {
                    delete oConfig.scoreApp;
                    $scope.save(oConfig);
                });
            });
        };
        $scope.addScoreGroup = function(oConfig) {
            if (!$parse('role.groups')(oConfig)) {
                $parse('role.groups').assign(oConfig, [{}]);
            } else {
                $parse('role.groups')(oConfig).push({});
            }
        };
        $scope.delScoreGroup = function(oConfig, oVoteGroup) {
            oConfig.role.groups.splice(oConfig.role.groups.indexOf(oVoteGroup), 1);
        };
        $scope.save = function(oConfig) {
            fnPostScoreConfig('save', oConfig);
        };
        srvEnlApp.get().then(function(oApp) {
            $scope.scoreSchemas = [];
            oApp.dataSchemas.forEach(function(oSchema) {
                if (!/html|single|multiplue|score/.test(oSchema.type)) {
                    $scope.scoreSchemas.push(oSchema);
                }
            });
            if (oApp.scoreConfig && oApp.scoreConfig.length) {
                oApp.scoreConfig.forEach(function(oConfig, index) {
                    var oCopied;
                    oCopied = angular.copy(oConfig);
                    _aConfigs.push(oCopied);
                    fnWatchConfig(oCopied);
                });
            }
        });
    }]);
});