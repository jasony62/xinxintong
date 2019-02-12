define(['require'], function(require) {
    'use strict';
    var ngApp, ls, siteId, missionId;

    ls = location.search;
    siteId = ls.match(/[\?&]site=([^&]*)/)[1];
    missionId = ls.match(/[\?&]mission=([^&]*)/) ? ls.match(/[\?&]mission=([^&]*)/)[1] : '';
    ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'notice.ui.xxt', 'service.matter', 'service.group']);
    ngApp.constant('cstApp', {
        importSource: [
            { v: 'mschema', l: '通讯录联系人' },
            { v: 'registration', l: '报名' },
            { v: 'signin', l: '签到' }
        ],
    });
    ngApp.config(['$locationProvider', 'srvSiteProvider', 'srvGroupAppProvider', function($locationProvider, srvSiteProvider, srvGroupAppProvider) {
        $locationProvider.html5Mode(true);
        srvSiteProvider.config(siteId);
        srvGroupAppProvider.config(siteId, null);
    }]);
    ngApp.controller('ctrlPlan', ['$scope', 'http2', 'srvSite', 'srvGroupApp', 'cstApp', function($scope, http2, srvSite, srvGroupApp, cstApp) {
        var _oProto, _oEntryRule;
        $scope.proto = _oProto = {};
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            _oProto[data.state] = data.value;
        });
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        if (missionId) {
            http2.get('/rest/pl/fe/matter/mission/get?site=' + siteId + '&id=' + missionId).then(function(rsp) {
                var oMission;
                $scope.mission = oMission = rsp.data;
                _oProto.mission = { id: oMission.id, title: oMission.title };
                _oProto.title = oMission.title + '-分组';
            });
        }
        $scope.assocWithApp = function() {
            srvGroupApp.assocWithApp(cstApp.importSource, $scope.mission ? $scope.mission : null, true).then(function(result) {
                _oProto.sourceApp = {
                    id: result.app,
                    type: result.appType,
                    title: result.appTitle
                };
                if (result.onlySpeaker) {
                    _oProto.sourceApp.onlySpeaker = result.onlySpeaker;
                }
            });
        };
        $scope.cancelSourceApp = function() {
            _oProto.sourceApp = null;
        };
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
            if (oConfig.proto.sourceApp) {
                delete oConfig.proto.sourceApp.title;
            }
            http2.post(url, oConfig).then(function(rsp) {
                location.href = '/rest/pl/fe/matter/group/main?site=' + siteId + '&id=' + rsp.data.id;
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