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
        $scope.proto = _oProto = {};
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        if (missionId) {
            http2.get('/rest/pl/fe/matter/mission/get?site=' + siteId + '&id=' + missionId, function(rsp) {
                var oMission;
                $scope.mission = oMission = rsp.data;
                _oProto.mission = { id: oMission.id, title: oMission.title };
                _oProto.title = oMission.title + '-分组';
            });
        }
        $scope.doCreate = function() {
            var url, data;
            var oConfig;
            url = '/rest/pl/fe/matter/group/create?site=' + siteId;
            if (missionId) {
                url += '&mission=' + missionId;
            }
            oConfig = {
                proto: $scope.proto
            };
            http2.post(url, oConfig, function(rsp) {
                location.href = '/rest/pl/fe/matter/group?site=' + siteId + '&id=' + rsp.data.id;
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