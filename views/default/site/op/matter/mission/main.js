define(['angular', 'xxt-page'], function(angular, uiPage) {
    'use strict';
    var _siteId, _missionId, ngApp;
    _siteId = location.search.match('site=([^&]*)')[1];
    _missionId = location.search.match('mission=([^&]*)')[1];
    ngApp = angular.module('mission', ['ui.bootstrap', 'service.mission']);
    ngApp.config(['$controllerProvider', 'srvSiteProvider', 'srvOpMissionProvider', function($cp, srvSiteProvider, srvOpMissionProvider) {
        ngApp.provider = {
            controller: $cp.register
        };
        srvSiteProvider.config(_siteId);
        srvOpMissionProvider.config(_siteId, _missionId);
    }]);
    ngApp.controller('ctrlMission', ['$scope', 'srvSite', 'srvOpMission', function($scope, srvSite, srvOpMission) {
        var _oUserSet;
        $scope.userSet = _oUserSet = {};
        $scope.tmsTableWrapReady = 'N';
        $scope.doUserSearch = function() {
            srvOpMission.userList(_oUserSet).then(function(result) {
                $scope.tmsTableWrapReady = 'Y';
            });
        };
        $scope.openMatter = function(matter) {
            if (/article|custom|news|channel|link/.test(matter.type)) {
                location.href = '/rest/site/fe/matter?site=' + LS.p.site + '&id=' + matter.id + '&type=' + matter.type;
            } else if (/enroll|signin|group/.test(matter.type) && matter.op_short_url_code) {
                location.href = 'http://' + location.host + '/q/' + matter.op_short_url_code;
            }
        };
        $scope.openReport = function(matter) {
            if (/enroll/.test(matter.type) && matter.rp_short_url_code) {
                location.href = 'http://' + location.host + '/q/' + matter.rp_short_url_code;
            }
        };
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvOpMission.get().then(function(result) {
            var page;
            $scope.mission = result.mission;
            page = result.page;
            uiPage.loadCode(ngApp, page).then(function() {
                $scope.page = page;
                window.loading.finish();
            });
            srvOpMission.matterList().then(function(matters) {
                $scope.matters = matters;
            });
            $scope.doUserSearch();
        });
    }]);
    /***/
    angular._lazyLoadModule('mission');
    /***/
    return ngApp;
});
