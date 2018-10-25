define(['require'], function(require) {
    'use strict';
    var ngApp, ls, siteId, missionId;

    ls = location.search;
    siteId = ls.match(/[\?&]site=([^&]*)/)[1];
    missionId = ls.match(/[\?&]mission=([^&]*)/) ? ls.match(/[\?&]mission=([^&]*)/)[1] : '';
    ngApp = angular.module('app', ['ngRoute', 'http.ui.xxt', 'notice.ui.xxt', 'service.matter']);
    ngApp.config(['$locationProvider', 'srvSiteProvider', function($locationProvider, srvSiteProvider) {
        $locationProvider.html5Mode(true);
        srvSiteProvider.config(siteId);
    }]);
    ngApp.controller('ctrlPlan', ['$scope', '$uibModal', 'http2', 'srvSite', function($scope, $uibModal, http2, srvSite) {
        var _oProto, _oEntryRule;
        $scope.proto = _oProto = {
            entryRule: {
                scope: {},
                mschemas: [],
            }
        };
        $scope.entryRule = _oEntryRule = _oProto.entryRule;
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            _oProto[data.state] = data.value;
        });
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
            $scope.snsNames = Object.keys(oSns);
        });
        if (missionId) {
            http2.get('/rest/pl/fe/matter/mission/get?site=' + siteId + '&id=' + missionId).then(function(rsp) {
                var oMission;
                $scope.mission = oMission = rsp.data;
                _oProto.mission = { id: oMission.id, title: oMission.title };
                _oEntryRule.scope = {};
                if ('member' === oMission.entry_rule.scope) {
                    _oEntryRule.scope.member = 'Y';
                    srvSite.memberSchemaList(oMission).then(function(aMemberSchemas) {
                        var oMschemasById = {};
                        aMemberSchemas.forEach(function(mschema) {
                            oMschemasById[mschema.id] = mschema;
                        });
                        Object.keys(oMission.entry_rule.member).forEach(function(mschemaId) {
                            _oEntryRule.mschemas.push({ id: mschemaId, title: oMschemasById[mschemaId].title });
                        });
                    });
                } else if ('sns' === oMission.entry_rule.scope) {
                    _oEntryRule.scope.sns = 'Y';
                    _oResult.proto.sns = oMission.entry_rule.sns;
                }
                _oProto.title = oMission.title + '-签到';
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
        $scope.changeUserScope = function() {
            switch (_oEntryRule.scope) {
                case 'group':
                    if (!_oEntryRule.group) {
                        $scope.chooseGroupApp();
                    }
                    break;
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
                    var url = '/rest/pl/fe/matter/group/list?site=' + siteId + '&size=999&cascaded=Y';
                    url += '&mission=' + missionId;
                    http2.get(url).then(function(rsp) {
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
            url = '/rest/pl/fe/matter/signin/create?site=' + siteId;
            if (missionId) {
                url += '&mission=' + missionId;
            }
            oConfig = {
                proto: $scope.proto
            };
            http2.post(url, oConfig).then(function(rsp) {
                location.href = '/rest/pl/fe/matter/signin/schema?site=' + siteId + '&id=' + rsp.data.id;
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