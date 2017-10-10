define(['require'], function(require) {
    'use strict';
    var ngApp, ls, siteId, missionId;

    ls = location.search;
    siteId = ls.match(/[\?&]site=([^&]*)/)[1];
    missionId = ls.match(/[\?&]mission=([^&]*)/) ? ls.match(/[\?&]mission=([^&]*)/)[1] : '';
    ngApp = angular.module('app', ['ngRoute', 'service.matter']);
    ngApp.config(['$locationProvider', 'srvSiteProvider', function($locationProvider, srvSiteProvider) {
        $locationProvider.html5Mode(true);
        srvSiteProvider.config(siteId);
    }]);
    ngApp.controller('ctrlPlan', ['$scope', 'http2', 'srvSite', function($scope, http2, srvSite) {
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
            $scope.snsCount = Object.keys(oSns).length;
            if (!missionId) {
                if ($scope.snsCount) {
                    _oEntryRule.scope = 'sns';
                } else {
                    _oEntryRule.scope = 'none';
                }
            }
        });
        if (missionId) {
            http2.get('/rest/pl/fe/matter/mission/get?site=' + siteId + '&id=' + missionId, function(rsp) {
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
                            _oEntryRule.mschemas.push({ id: mschemaId, title: oMschemasById[mschemaId].title });
                        });
                    });
                } else if ('sns' === oMission.entry_rule.scope) {
                    $scope.result.proto.sns = oMission.entry_rule.sns;
                }
                _oProto.title = oMission.title + '-签到';
            });
        }
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
            http2.post(url, oConfig, function(rsp) {
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