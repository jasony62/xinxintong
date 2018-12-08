define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRule', ['$scope', 'http2', 'srvEnrollApp', function($scope, http2, srvEnlApp) {
        var _oApp, logs;
        $scope.page = {};
        $scope.fetchLogs = function() {
            var url;
            url = '/rest/pl/fe/matter/enroll/coin/logs?site=' + _oApp.siteid + '&app=' + _oApp.id;
            http2.get(url, { page: $scope.page }).then(function(rsp) {
                if (rsp.data.logs) {
                    $scope.logs = logs = rsp.data.logs;
                }
            });
        };
        srvEnlApp.get().then(function(oApp) {
            _oApp = oApp;
            $scope.fetchLogs();
        });
    }]);
    ngApp.provider.controller('ctrlActionRule', ['$scope', 'http2', 'srvEnrollApp', function($scope, http2, srvEnlApp) {
        var _oApp, _oRule;
        $scope.rulesModified = false;
        $scope.save = function() {
            _oApp.actionRule = _oRule;
            $scope.update('actionRule').then(function() {
                $scope.rulesModified = false;
            });
        };
        srvEnlApp.get().then(function(oApp) {
            _oApp = oApp;
            $scope.rule = _oRule = oApp.actionRule;
            $scope.$watch('rule', function(nv, ov) {
                if (nv !== ov) {
                    $scope.rulesModified = true;
                }
            }, true);
        });
    }]);
    ngApp.provider.controller('ctrlCoinRule', ['$scope', 'http2', 'srvEnrollApp', function($scope, http2, srvEnlApp) {

        function fetchAppRules() {
            var url;
            url = '/rest/pl/fe/matter/enroll/coin/rules?site=' + _oApp.siteid + '&app=' + _oApp.id;
            http2.get(url).then(function(rsp) {
                rsp.data.forEach(function(oRule) {
                    var oRuleData;
                    if ($scope.rules[oRule.act]) {
                        oRuleData = $scope.rules[oRule.act].data;
                        oRuleData.id = oRule.id;
                        oRuleData.actor_delta = oRule.actor_delta;
                        oRuleData.actor_overlap = oRule.actor_overlap;
                    }
                });
            });
        }

        function fetchMissionRules() {
            var url;
            url = '/rest/pl/fe/matter/mission/coin/rules?site=' + _oApp.siteid + '&mission=' + _oApp.mission.id;
            http2.get(url).then(function(rsp) {
                rsp.data.forEach(function(oRule) {
                    if ($scope.rules[oRule.act]) {
                        $scope.rules[oRule.act].mission = oRule;
                    }
                });
            });
        }

        var _oApp, _aDefaultRules;
        _aDefaultRules = [{
            data: { act: 'site.matter.enroll.submit' },
            desc: '用户A提交新填写记录',
        }, {
            data: { act: 'site.matter.enroll.cowork.get.submit' },
            desc: '用户A提交的填写记录获得新协作填写数据',
        }, {
            data: { act: 'site.matter.enroll.cowork.do.submit' },
            desc: '用户A提交新协作填写数据',
        }, {
            data: { act: 'site.matter.enroll.share.friend' },
            desc: '用户A分享活动给微信好友',
        }, {
            data: { act: 'site.matter.enroll.share.timeline' },
            desc: '用户A分享活动至朋友圈',
        }, {
            data: { act: 'site.matter.enroll.data.get.like' },
            desc: '用户A填写数据获得赞同',
        }, {
            data: { act: 'site.matter.enroll.data.get.dislike' },
            desc: '用户A填写数据被反对',
        }, {
            data: { act: 'site.matter.enroll.cowork.get.like' },
            desc: '用户A填写的协作数据获得赞同',
        }, {
            data: { act: 'site.matter.enroll.cowork.get.dislike' },
            desc: '用户A填写的协作数据被反对',
        }, {
            data: { act: 'site.matter.enroll.data.get.remark' },
            desc: '用户A填写数据获得留言',
        }, {
            data: { act: 'site.matter.enroll.cowork.get.remark' },
            desc: '用户A填写协作数据获得留言',
        }, {
            data: { act: 'site.matter.enroll.do.remark' },
            desc: '用户A发表留言',
        }, {
            data: { act: 'site.matter.enroll.remark.get.like' },
            desc: '用户A发表的留言获得赞同',
        }, {
            data: { act: 'site.matter.enroll.remark.get.dislike' },
            desc: '用户A发表的留言被反对',
        }, {
            data: { act: 'site.matter.enroll.data.get.agree' },
            desc: '用户A填写的记录获得推荐',
        }, {
            data: { act: 'site.matter.enroll.cowork.get.agree' },
            desc: '用户A发表的协作填写记录获得推荐',
        }, {
            data: { act: 'site.matter.enroll.remark.get.agree' },
            desc: '用户A发表的留言获得推荐',
        }];
        $scope.rules = {};
        _aDefaultRules.forEach(function(oRule) {
            oRule.data.actor_delta = 0;
            oRule.data.actor_overlap = 'A';
            $scope.rules[oRule.data.act] = oRule;
        });
        $scope.rulesModified = false;
        $scope.changeRules = function() {
            $scope.rulesModified = true;
        };
        $scope.save = function() {
            var posted = [],
                rule, url;

            for (var k in $scope.rules) {
                rule = $scope.rules[k];
                if (rule.data.id || rule.data.actor_delta != 0) {
                    posted.push(rule.data);
                }
            }
            url = '/rest/pl/fe/matter/enroll/coin/saveRules?site=' + _oApp.siteid + '&app=' + _oApp.id;
            http2.post(url, posted).then(function(rsp) {
                for (var k in rsp.data) {
                    $scope.rules[k].data.id = rsp.data[k];
                }
                $scope.rulesModified = false;
            });
        };
        srvEnlApp.get().then(function(oApp) {
            _oApp = oApp;
            fetchAppRules();
            if (_oApp.mission) {
                fetchMissionRules();
            }
        });
    }]);
    ngApp.provider.controller('ctrlExportRule', ['$scope', 'noticebox', 'http2', 'srvEnrollApp', 'tkEnrollApp', function($scope, noticebox, http2, srvEnlApp, tkEnlApp) {
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

        var _aConfigs, _oConfigsModified, _oAppDataSchemas;
        $scope.configs = _aConfigs = [];
        $scope.configsModified = _oConfigsModified = {};
        $scope.appDataSchemas = _oAppDataSchemas = {};
        $scope.addConfig = function() {
            _aConfigs.push({});
        };
        $scope.delConfig = function(oConfig) {
            noticebox.confirm('删除填写记录转发设置，确定？').then(function() {
                if (oConfig.id) {
                    http2.post('/rest/pl/fe/matter/enroll/updateTransmitConfig?app=' + $scope.app.id, { method: 'delete', data: oConfig }).then(function() {
                        _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                        delete _oConfigsModified[oConfig.id];
                    });
                } else {
                    _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                }
            });
        };
        $scope.save = function(oConfig) {
            http2.post('/rest/pl/fe/matter/enroll/updateTransmitConfig?app=' + $scope.app.id, { method: 'save', data: oConfig }).then(function(rsp) {
                http2.merge(oConfig, rsp.data);
                fnWatchConfig(oConfig);
                noticebox.success('保存成功！');
            });
        };
        $scope.pickApp = function(oConfig) {
            tkEnlApp.choose($scope.app).then(function(oResult) {
                var oTargetApp, dataSchemas;
                if (oTargetApp = oResult.app) {
                    oConfig.app = { id: oTargetApp.id, title: oTargetApp.title };
                    if (oTargetApp.data_schemas) {
                        dataSchemas = [];
                        oTargetApp.dataSchemas = JSON.parse(oTargetApp.data_schemas);
                        oTargetApp.dataSchemas.forEach(function(oSchema) {
                            if (!/html/.test(oSchema.type)) {
                                dataSchemas.push(oSchema);
                            }
                        });
                        _oAppDataSchemas[oTargetApp.id] = dataSchemas;
                    }
                }
            });
        };
        srvEnlApp.get().then(function(oApp) {
            if (oApp.transmitConfig && oApp.transmitConfig.length) {
                oApp.transmitConfig.forEach(function(oConfig, index) {
                    var oCopied;
                    oCopied = angular.copy(oConfig);
                    _aConfigs.push(oCopied);
                    fnWatchConfig(oCopied);
                    tkEnlApp.get(oCopied.app.id).then(function(oTargetApp) {
                        var dataSchemas;
                        dataSchemas = [];
                        oTargetApp.dataSchemas.forEach(function(oSchema) {
                            if (!/html/.test(oSchema.type)) {
                                dataSchemas.push(oSchema);
                            }
                        });
                        _oAppDataSchemas[oTargetApp.id] = dataSchemas;
                    });
                });
            }
        });
    }]);
});