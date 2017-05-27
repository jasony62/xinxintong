define(['require', 'angular'], function(require, angular) {
    'use strict';
    var site = location.search.match('site=(.*)')[1];
    var ngApp = angular.module('app', ['ui.bootstrap']);
    ngApp.service('hisService', ['$http', '$q', function($http, $q) {
        var _baseUrl = '/rest/site/fe/user/history';
        return {
            myApps: function() {
                var deferred = $q.defer();
                $http.get(_baseUrl + '/appList?site=' + site).success(function(rsp) {
                    deferred.resolve(rsp.data.apps);
                });
                return deferred.promise;
            },
            myMissions: function() {
                var deferred = $q.defer();
                $http.get(_baseUrl + '/missionList?site=' + site).success(function(rsp) {
                    deferred.resolve(rsp.data.missions);
                });
                return deferred.promise;
            },
        }
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$http', 'hisService', function($scope, $http, hisService) {
        $scope.openApp = function(app) {
            location.href = '/rest/site/fe/matter/' + app.matter_type + '?site=' + site + '&app=' + app.matter_id;
        };
        $scope.openMission = function(mission) {
            location.href = '/rest/site/fe/matter/mission?site=' + site + '&mission=' + mission.mission_id;
        };
        $http.get('/rest/site/fe/get?site=' + site).success(function(rsp) {
            window.loading.finish();
            hisService.myApps().then(function(apps) {
                $scope.myApps = apps;
            });
            hisService.myMissions().then(function(missions) {
                $scope.myMissions = missions;
            });
            $scope.site = rsp.data;
        });
    }]);
    /* bootstrap angular app */
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});
