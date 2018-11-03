define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEntry', ['$scope', 'srvSite', 'srvTimerNotice', function($scope, srvSite, srvTimerNotice) {
        /* 定时任务服务 */
        $scope.srvTimer = srvTimerNotice;
        /* 定时任务截止时间 */
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            var oTimer;
            if (oTimer = $scope.srvTimer.timerById(data.state)) {
                oTimer.task.task_expire_at = data.value;
            }
        });
        $scope.$watch('mission', function(oMission) {
            if (!oMission) return;
            /* 项目通讯录 */
            srvSite.memberSchemaList(oMission, true).then(function(aMemberSchemas) {
                $scope.missionMschemas = aMemberSchemas;
            });
        });
    }]);
    ngApp.provider.controller('ctrlAccess', ['$scope', '$uibModal', 'srvSite', function($scope, $uibModal, srvSite) {
        var _oEntryRule;
        $scope.changeUserScope = function(scopeProp) {
            switch (_oEntryRule.scope) {
                case 'sns':
                    if (_oEntryRule.scope[scopeProp] === 'Y') {
                        if (!_oEntryRule.sns) {
                            _oEntryRule.sns = {};
                        }
                        if ($scope.snsCount === 1) {
                            _oEntryRule.sns[Object.keys($scope.sns)[0]] = { 'entry': 'Y' };
                        }
                    }
                    break;
                default:
            }
            this.update('entryRule');
        };
        $scope.chooseMschema = function() {
            srvSite.chooseMschema($scope.mission).then(function(oResult) {
                var chosen;
                if (oResult && oResult.chosen) {
                    chosen = oResult.chosen;
                    $scope.mschemasById[chosen.id] = chosen;
                    _oEntryRule.member === undefined && (_oEntryRule.member = {});
                    if (!_oEntryRule.member[chosen.id]) {
                        _oEntryRule.member[chosen.id] = { entry: '' };
                        $scope.update('entryRule');
                    }
                }
            });
        };
        $scope.editMschema = function(oMschema) {
            if (oMschema.matter_id === $scope.mission.id) {
                location.href = '/rest/pl/fe/matter/mission/mschema?site=' + $scope.mission.siteid + '&id=' + $scope.mission.id + '#' + oMschema.id;
            } else {
                location.href = '/rest/pl/fe?view=main&scope=user&sid=' + $scope.mission.siteid + '&mschema=' + oMschema.id;
            }
        };
        $scope.removeMschema = function(mschemaId) {
            if (_oEntryRule.member[mschemaId]) {
                delete _oEntryRule.member[mschemaId];
                $scope.update('entryRule');
            }
        };
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
            $scope.snsNames = Object.keys(oSns);
            $scope.snsCount = Object.keys(oSns).length;
        });
        $scope.$watch('mission', function(oMission) {
            if (!oMission) return;
            $scope.rule = _oEntryRule = oMission.entryRule;
            srvSite.memberSchemaList(oMission).then(function(aMemberSchemas) {
                $scope.memberSchemas = aMemberSchemas;
                $scope.mschemasById = {};
                $scope.memberSchemas.forEach(function(mschema) {
                    $scope.mschemasById[mschema.id] = mschema;
                });
            });
        });
    }]);
    ngApp.provider.controller('ctrlRemind', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.$watch('mission', function(oMission) {
            if (!oMission) return;
            $scope.srvTimer.list(oMission, 'remind').then(function(timers) {
                $scope.timers = timers;
            });
        });
        $scope.assignUserApp = function() {
            var mission = $scope.mission;
            $uibModal.open({
                templateUrl: 'assignUserApp.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.data = {
                        appId: '',
                        appType: 'group'
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                    $scope2.$watch('data.appType', function(appType) {
                        if (appType) {
                            if (appType === 'mschema') {
                                srvSite.memberSchemaList(mission, true).then(function(aMemberSchemas) {
                                    $scope2.apps = aMemberSchemas;
                                });
                            } else {
                                var url = '/rest/pl/fe/matter/' + appType + '/list?mission=' + mission.id;
                                http2.get(url).then(function(rsp) {
                                    $scope2.apps = rsp.data.apps;
                                });
                            }
                        }
                    });
                }],
                backdrop: 'static'
            }).result.then(function(data) {
                mission.user_app_id = data.appId;
                mission.user_app_type = data.appType;
                $scope.update(['user_app_id', 'user_app_type']).then(function(rsp) {
                    if (data.appType === 'mschema') {
                        var url = '/rest/pl/fe/matter/mission/get?id=' + mission.id;
                        http2.get(url).then(function(rsp) {
                            mission.userApp = rsp.data.userApp;
                        });
                    } else {
                        var key = data.appType == 'enroll' ? 'app' : 'id';
                        var url = '/rest/pl/fe/matter/' + data.appType + '/get?site=' + mission.siteid + '&' + key + '=' + data.appId;
                        http2.get(url).then(function(rsp) {
                            mission.userApp = rsp.data;
                            if (mission.userApp.data_schemas && angular.isString(mission.userApp.data_schemas)) {
                                mission.userApp.data_schemas = JSON.parse(mission.userApp.data_schemas);
                            }
                        });
                    }
                });
            });
        };
        $scope.cancelUserApp = function() {
            var mission;
            if (window.confirm('确定删除项目用户名单活动？')) {
                mission = $scope.mission;
                mission.user_app_id = '';
                mission.user_app_type = '';
                $scope.update(['user_app_id', 'user_app_type']).then(function() {
                    delete mission.userApp;
                    http2.post('/rest/pl/fe/matter/mission/report/configUpdate?mission=' + mission.id, { apps: [] }).then(function(rsp) {
                        if (mission.reportConfig) {
                            mission.reportConfig.include_apps = [];
                        }
                    });
                });
            }
        };
    }]);
});