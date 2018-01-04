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
                scope: '',
                mschemas: [],
            }
        };
        $scope.entryRule = _oEntryRule = _oProto.entryRule;
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
            $scope.snsNames = Object.keys(oSns);
        });
        if (_missionId) {
            http2.get('/rest/pl/fe/matter/mission/get?site=' + _siteId + '&id=' + _missionId, function(rsp) {
                var oMission;
                $scope.mission = oMission = rsp.data;
                _oProto.mission = { id: oMission.id, title: oMission.title };
                _oEntryRule.scope = oMission.entry_rule.scope || 'none';
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
        $scope.changeUserScope = function() {
            switch (_oEntryRule.scope) {
                case 'member':
                    if (!_oEntryRule.mschemas || _oEntryRule.mschemas.length === 0) {
                        $scope.chooseMschema();
                    }
                    break;
                case 'sns':
                    _oEntryRule.sns = {};
                    $scope.snsNames.forEach(function(snsName) {
                        _oEntryRule.sns[snsName] = true;
                    });
                    break;
            }
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
                location.href = '/rest/pl/fe/matter/plan?site=' + _siteId + '&id=' + rsp.data.id;
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