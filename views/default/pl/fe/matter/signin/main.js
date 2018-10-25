define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', 'srvSigninApp', '$uibModal', 'srvTag', function($scope, http2, srvSigninApp, $uibModal, srvTag) {
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            $scope.app[data.state] = data.value;
            srvSigninApp.update(data.state);
        });
        $scope.assignMission = function() {
            srvSigninApp.assignMission();
        };
        $scope.quitMission = function() {
            srvSigninApp.quitMission();
        };
        $scope.remove = function() {
            if (window.confirm('确定删除？')) {
                http2.get('/rest/pl/fe/matter/signin/remove?site=' + $scope.app.siteid + '&app=' + $scope.app.id).then(function(rsp) {
                    if ($scope.app.mission) {
                        location = "/rest/pl/fe/matter/mission?site=" + $scope.app.siteid + "&id=" + $scope.app.mission.id;
                    } else {
                        location = '/rest/pl/fe/site/console?site=' + $scope.app.siteid;
                    }
                });
            }
        };
        $scope.tagMatter = function(subType) {
            var oTags;
            oTags = $scope.oTag;
            srvTag._tagMatter($scope.app, oTags, subType);
        };
        srvSigninApp.get().then(function(oApp) {
            $scope.defaultTime = {
                start_at: oApp.start_at > 0 ? oApp.start_at : (function() {
                    var t;
                    t = new Date;
                    t.setHours(8);
                    t.setMinutes(0);
                    t.setMilliseconds(0);
                    t.setSeconds(0);
                    t = parseInt(t / 1000);
                    return t;
                })()
            };
        });
    }]);
    ngApp.provider.controller('ctrlAccess', ['$scope', '$uibModal', 'srvSite', 'srvSigninApp', 'srvEnrollSchema', function($scope, $uibModal, srvSite, srvSigninApp, srvEnrollSchema) {
        var oEntryRule;
        $scope.rule = {};
        $scope.changeUserScope = function(scopeProp) {
            switch (scopeProp) {
                case 'sns':
                    if ($scope.rule.scope[scopeProp] === 'Y') {
                        if (!$scope.rule.sns) {
                            $scope.rule.sns = {};
                        }
                        if ($scope.snsCount === 1) {
                            $scope.rule.sns[Object.keys($scope.sns)[0]] = { 'entry': 'Y' };
                        }
                    }
                    break;
                case 'group':
                    if ($scope.rule.scope.group !== 'Y') {
                        delete $scope.rule.group;
                    }
                    break;
                case 'enroll':
                    if ($scope.rule.scope.enroll !== 'Y') {
                        delete $scope.rule.enroll;
                    }
                    break;
            }
            srvSigninApp.changeUserScope($scope.rule.scope, $scope.sns);
        };
        $scope.chooseMschema = function() {
            srvSite.chooseMschema($scope.app).then(function(oResult) {
                var ruleMember, rule = {};
                if (!(ruleMember = oEntryRule.member)) oEntryRule.member = ruleMember = {};
                if (!ruleMember[oResult.chosen.id]) {
                    rule.entry = 'Y';
                    ruleMember[oResult.chosen.id] = rule;
                    $scope.update('entryRule');
                }
            });
        };
        $scope.editMschema = function(oMschema) {
            if (oMschema.matter_id) {
                if (oMschema.matter_type === 'mission') {
                    location.href = '/rest/pl/fe/matter/mission/mschema?id=' + oMschema.matter_id + '&site=' + $scope.app.siteid + '#' + oMschema.id;
                } else {
                    location.href = '/rest/pl/fe/site/mschema?site=' + $scope.app.siteid + '#' + oMschema.id;
                }
            } else {
                location.href = '/rest/pl/fe?view=main&scope=user&sid=' + $scope.app.siteid + '#' + oMschema.id;
            }
        };
        $scope.removeMschema = function(mschemaId) {
            var bSchemaChanged;
            if (oEntryRule.member[mschemaId]) {
                /* 取消题目和通信录的关联 */
                $scope.app.dataSchemas.forEach(function(oSchema) {
                    var _oBeforeState;
                    if (oSchema.type === 'member') {
                        _oBeforeState = angular.copy(oSchema);
                        oSchema.type = 'shorttext';
                        delete oSchema.schema_id;
                        srvEnrollSchema.update(oSchema, _oBeforeState);
                        bSchemaChanged = true;
                    }
                });
                if (bSchemaChanged) {
                    srvEnrollSchema.submitChange($scope.app.pages);
                }
                delete oEntryRule.member[mschemaId];
                $scope.update('entryRule');
            }
        };
        $scope.chooseEnrollApp = function() {
            var _oApp;
            _oApp = $scope.app
            $uibModal.open({
                templateUrl: 'assignEnrollApp.html',
                controller: ['$scope', '$uibModalInstance', 'http2', function($scope2, $mi, http2) {
                    $scope2.app = _oApp;
                    $scope2.data = {};
                    _oApp.mission && ($scope2.data.sameMission = 'Y');
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                    var url = '/rest/pl/fe/matter/enroll/list?site=' + _oApp.siteid + '&size=999';
                    _oApp.mission && (url += '&mission=' + _oApp.mission.id);
                    http2.get(url).then(function(rsp) {
                        $scope2.apps = rsp.data.apps;
                    });
                }],
                backdrop: 'static'
            }).result.then(function(oResult) {
                oEntryRule.enroll = { id: oResult.app.id, title: oResult.app.title };
                $scope.update('entryRule');
            });
        }
        $scope.chooseGroupApp = function() {
            var _oApp;
            _oApp = $scope.app
            $uibModal.open({
                templateUrl: 'assignGroupApp.html',
                controller: ['$scope', '$uibModalInstance', 'http2', function($scope2, $mi, http2) {
                    $scope2.app = _oApp;
                    $scope2.data = {};
                    _oApp.mission && ($scope2.data.sameMission = 'Y');
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                    var url = '/rest/pl/fe/matter/group/list?site=' + _oApp.siteid + '&size=999';
                    _oApp.mission && (url += '&mission=' + _oApp.mission.id);
                    http2.get(url).then(function(rsp) {
                        $scope2.apps = rsp.data.apps;
                    });
                }],
                backdrop: 'static'
            }).result.then(function(oResult) {
                oEntryRule.group = { id: oResult.app.id, title: oResult.app.title };
                $scope.update('entryRule');
            });
        };
        srvSigninApp.get().then(function(app) {
            $scope.jumpPages = srvSigninApp.jumpPages();
            $scope.rule = oEntryRule = app.entryRule;
        }, true);
    }]);
});