define(['frame', 'schema'], function(ngApp, schemaLib) {
    'use strict';
    ngApp.provider.controller('ctrlScore', ['$scope', '$parse', '$uibModal', 'http2', 'noticebox', 'srvEnrollApp', 'srvEnrollSchema', function($scope, $parse, $uibModal, http2, noticebox, srvEnlApp, srvEnlSch) {
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
                    http2.post('/rest/pl/fe/matter/enroll/updateScoreConfig?app=' + $scope.app.id, { method: 'delete', data: oConfig }).then(function() {
                        _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                        delete _oConfigsModified[oConfig.id];
                    });
                } else {
                    _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                }
            });
        };
        /**
         * 设置题目的打分活动
         */
        $scope.setScoreApp = function(oSchema) {
            var _oApp;
            _oApp = $scope.app;
            http2.post('/rest/script/time', { html: { 'score': '/views/default/pl/fe/matter/enroll/component/schema/setScoreApp' } }).then(function(rsp) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/schema/setScoreApp.html?_=' + rsp.data.html.score.time,
                    controller: ['$scope', '$uibModalInstance', 'noticebox', 'tkEnrollApp', 'tkDataSchema', function($scope2, $mi, noticebox, tkEnlApp, tkSchema) {
                        var _oResult, _oScoreApp;
                        $scope2.schema = angular.copy(oSchema);
                        $scope2.disabled = false;
                        $scope2.result = _oResult = {};
                        $scope2.$on('xxt.editable.remove', function(e, oOp) {
                            _oResult.schema.ops.splice(_oResult.schema.ops.indexOf(oOp), 1);
                        });
                        $scope2.cancel = function() { $mi.dismiss(); };
                        $scope2.addOption = function() {
                            var oNewOp;
                            oNewOp = schemaLib.addOption(_oResult.schema);
                            oNewOp.l = '打分项';
                        };
                        $scope2.ok = function() {
                            if (_oScoreApp) {
                                tkEnlApp.update(_oScoreApp, { title: _oResult.title, dataSchemas: _oScoreApp.dataSchemas }).then(function() {
                                    $mi.close();
                                });
                            } else {
                                http2.post('/rest/pl/fe/matter/enroll/create/scoreSchema?app=' + _oApp.id + '&schema=' + oSchema.id, _oResult).then(function(rsp) {
                                    var oNewSchema;
                                    oNewSchema = rsp.data;
                                    http2.merge(oSchema, oNewSchema);
                                    $mi.close();
                                });
                            }
                        };
                        if (oSchema.scoreApp && oSchema.scoreApp.id && oSchema.scoreApp.schema && oSchema.scoreApp.schema.id) {
                            tkEnlApp.get(oSchema.scoreApp.id).then(function(oScoreApp) {
                                _oScoreApp = oScoreApp;
                                _oResult.title = oScoreApp.title;
                                var oMap = tkSchema.toObject(oScoreApp.dataSchemas, function(oScoreSchema) { return oScoreSchema.id = oSchema.scoreApp.schema.id; }, true);
                                if (oMap && oMap.length === 1)
                                    _oResult.schema = oMap.array[0];
                            });
                        } else {
                            _oResult.title = _oApp.title + ' - ' + oSchema.title + '（打分）';
                            _oResult.schema = {};
                            $scope2.addOption(_oResult.schema);
                            $scope2.addOption(_oResult.schema);
                        }
                    }],
                    backdrop: 'static',
                }).result.then(function() {
                    noticebox.success('完成设置！');
                });
            });
        };
        $scope.unlinkScoreApp = function(oSchema) {
            noticebox.confirm('解除题目和打分活动的关联，确定？').then(function() {
                delete oSchema.scoreApp;
                srvEnlSch.update(oSchema, null, 'scoreApp');
                srvEnlSch.submitChange().then(function() {
                    noticebox.success('完成设置！');
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
            http2.post('/rest/pl/fe/matter/enroll/updateScoreConfig?app=' + $scope.app.id, { method: 'save', data: oConfig }).then(function(rsp) {
                http2.merge(oConfig, rsp.data);
                fnWatchConfig(oConfig);
                noticebox.success('保存成功！');
            });
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