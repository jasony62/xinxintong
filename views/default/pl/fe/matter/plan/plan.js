define(['require'], function(require) {
    'use strict';
    var ngApp, ls, _siteId, _missionId;

    ls = location.search;
    _siteId = ls.match(/[\?&]site=([^&]*)/)[1];
    _missionId = ls.match(/[\?&]mission=([^&]*)/) ? ls.match(/[\?&]mission=([^&]*)/)[1] : '';
    ngApp = angular.module('app', ['ngRoute', 'service.matter']);
    ngApp.config(['$locationProvider', 'srvSiteProvider', function($locationProvider, srvSiteProvider) {
        $locationProvider.html5Mode(true);
        srvSiteProvider.config(_siteId);
    }]);
    ngApp.controller('ctrlPlan', ['$scope', '$uibModal', 'http2', 'srvSite', function($scope, $uibModal, http2, srvSite) {
        var _oProto, _oEntryRule;
        $scope.proto = _oProto = {
            entryRule: {
                scope: {},
                mschemas: [],
                sns: {}
            }
        };
        $scope.entryRule = _oEntryRule = _oProto.entryRule;
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
            $scope.snsNames = Object.keys(oSns);
            $scope.$watch('entryRule.scope.sns', function(valid) {
                if ($scope.snsNames.length === 1) {
                    if (valid === 'Y') {
                        _oEntryRule.sns[$scope.snsNames[0]] = true;
                    } else {
                        _oEntryRule.sns[$scope.snsNames[0]] = false;
                    }
                }
            });
        });
        if (_missionId) {
            http2.get('/rest/pl/fe/matter/mission/get?site=' + _siteId + '&id=' + _missionId, function(rsp) {
                var oMission;
                $scope.mission = oMission = rsp.data;
                _oProto.mission = { id: oMission.id, title: oMission.title };
                if ('member' === oMission.entry_rule.scope) {
                    srvSite.memberSchemaList(oMission).then(function(aMemberSchemas) {
                        var oMschemasById = {};
                        aMemberSchemas.forEach(function(mschema) {
                            oMschemasById[mschema.id] = mschema;
                        });
                        Object.keys(oMission.entry_rule.member).forEach(function(mschemaId) {
                            if (oMschemasById[mschemaId]) {
                                _oEntryRule.mschemas.push({ id: mschemaId, title: oMschemasById[mschemaId].title });
                            }
                        });
                    });
                } else if ('sns' === oMission.entry_rule.scope) {
                    $scope.proto.sns = oMission.entry_rule.sns;
                }
                _oProto.title = oMission.title + '-计划任务';
            });
        }
        $scope.$watch('entryRule.scope.member', function(valid) {
            if (valid === 'Y') {
                if (_oEntryRule.mschemas.length === 0) {
                    $scope.chooseMschema();
                }
            }
        });
        $scope.chooseMschema = function() {
            srvSite.chooseMschema({ id: '_pending', title: _oProto.title }).then(function(result) {
                var oChosen = result.chosen;
                _oEntryRule.mschemas.push({ id: oChosen.id, title: oChosen.title });
            });
        };
        $scope.removeMschema = function(oMschema) {
            var mschemas = _oEntryRule.mschemas;
            mschemas.splice(mschemas.indexOf(oMschema), 1);
        };
        $scope.$watch('entryRule.scope.group', function(valid) {
            if (valid === 'Y') {
                if (!_oEntryRule.group) {
                    $scope.chooseGroupApp();
                }
            }
        });
        $scope.chooseGroupApp = function() {
            $uibModal.open({
                templateUrl: 'chooseGroupApp.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.data = {
                        app: null,
                        round: null
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                    var url = '/rest/pl/fe/matter/group/list?site=' + _siteId + '&size=999&cascaded=Y';
                    url += '&mission=' + _missionId;
                    http2.get(url, function(rsp) {
                        $scope2.apps = rsp.data.apps;
                    });
                }],
                backdrop: 'static'
            }).result.then(function(result) {
                if (result.app) {
                    _oEntryRule.group = { id: result.app.id, title: result.app.title };
                    if (result.round) {
                        _oEntryRule.group.round = { id: result.round.round_id, title: result.round.title };
                    }
                }
            });
        };
        $scope.doCreate = function() {
            var url, data;
            var oConfig;
            url = '/rest/pl/fe/matter/plan/create?site=' + _siteId;
            if (_missionId) {
                url += '&mission=' + _missionId;
            }
            oConfig = {
                proto: $scope.proto
            };
            http2.post(url, oConfig, function(rsp) {
                location.href = '/rest/pl/fe/matter/plan/schemaTask?site=' + _siteId + '&id=' + rsp.data.id;
            });
        };
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});