'use strict';
var site = location.search.match('site=(.*)')[1];
var ngApp = angular.module('app', ['ui.bootstrap', 'http.ui.xxt']);
ngApp.service('hisService', ['http2', '$q', function(http2, $q) {
    var _baseUrl = '/rest/site/fe/user/history';
    return {
        myApps: function() {
            var deferred = $q.defer();
            http2.get(_baseUrl + '/appList?site=' + site).then(function(rsp) {
                deferred.resolve(rsp.data.apps);
            });
            return deferred.promise;
        },
        myMissions: function() {
            var deferred = $q.defer();
            http2.get(_baseUrl + '/missionList?site=' + site).then(function(rsp) {
                deferred.resolve(rsp.data.missions);
            });
            return deferred.promise;
        },
    }
}]);
ngApp.controller('ctrlMain', ['$scope', 'http2', 'hisService', function($scope, http2, hisService) {
    $scope.openApp = function(app) {
        location.href = '/rest/site/fe/matter/' + app.matter_type + '?site=' + site + '&app=' + app.matter_id;
    };
    $scope.openMission = function(mission) {
        location.href = '/rest/site/fe/matter/mission?site=' + site + '&mission=' + mission.mission_id;
    };
    http2.get('/rest/site/fe/get?site=' + site).then(function(rsp) {
        hisService.myApps().then(function(apps) {
            $scope.myApps = apps;
        });
        hisService.myMissions().then(function(missions) {
            $scope.myMissions = missions;
        });
        $scope.site = rsp.data;
        var eleLoading, eleStyle;
        eleLoading = document.querySelector('.loading');
        eleLoading.parentNode.removeChild(eleLoading);
    });
}]);
angular.bootstrap(document, ["app"]);