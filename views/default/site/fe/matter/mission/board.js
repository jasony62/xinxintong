require(['matterService'], function() {
    'use strict';
    var siteId, missionId, ngApp;
    siteId = location.search.match('site=([^&]*)')[1];
    missionId = location.search.match('mission=([^&]*)')[1];
    ngApp = angular.module('app', ['ui.tms', 'service.matter']);
    ngApp.controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {
        var _oMission;
        $scope.siteid = siteId;
        /* end app loading */
        http2.get('/rest/site/fe/matter/mission/get?site=' + siteId + '&mission=' + missionId, function(rsp) {
            var groupUsers;
            $scope.mission = _oMission = rsp.data;
        });
        window.loading.finish();
    }]);
    ngApp.controller('ctrlDoc', ['$scope', 'http2', function($scope, http2) {
        http2.get('/rest/site/fe/matter/mission/matter/docList?site=' + siteId + '&mission=' + missionId, function(rsp) {
            $scope.docs = rsp.data;
        });
    }]);
    ngApp.controller('ctrlRecommend', ['$scope', 'http2', function($scope, http2) {
        $scope.gotoDetail = function(oRecommend) {
            var url;
            url = '/rest/site/fe/matter/enroll?site=' + siteId;
            url += '&app=' + oRecommend.app.id;
            url += '&ek=' + oRecommend.enroll_key;
            url += '&schema=' + oRecommend.schema.id;
            url += '&page=remark';
            location.href = url;
        };
        http2.get('/rest/site/fe/matter/mission/matter/agreedList?site=' + siteId + '&mission=' + missionId, function(rsp) {
            $scope.recommends = rsp.data.agreed;
        });
    }]);
    ngApp.controller('ctrlRank', ['$scope', 'http2', function($scope, http2) {
        http2.get('/rest/site/fe/matter/mission/user/rank?site=' + siteId + '&mission=' + missionId, function(rsp) {
            $scope.users = rsp.data.users;
        });
    }]);
    /* bootstrap angular app */
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});