define(['require', 'angular'], function(require, angular) {
    'use strict';
    var siteId, ngApp;
    siteId = location.search.match('site=(.*)')[1];
    ngApp = angular.module('app', ['ui.bootstrap']);
    ngApp.service('srvNotice', ['$http', '$q', function($http, $q) {
        var _baseUrl = '/rest/site/fe/user/notice';
        return {
            list: function() {
                var deferred = $q.defer();
                $http.get(_baseUrl + '/list?site=' + siteId).success(function(rsp) {
                    deferred.resolve(rsp.data.logs);
                });
                return deferred.promise;
            },
        }
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$http', 'srvNotice', function($scope, $http, hisService) {
        srvNotice.list();
        $http.get('/rest/site/fe/get?site=' + siteId).success(function(rsp) {
            $scope.site = rsp.data;
            window.loading.finish();
        });
    }]);
    /* bootstrap angular app */
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});
