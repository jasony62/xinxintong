define(['require', 'angular'], function(require, angular) {
    'use strict';
    var siteId, ngApp;
    siteId = location.search.match('site=(.*)')[1];
    ngApp = angular.module('app', ['ngSanitize', 'ui.bootstrap']);
    ngApp.controller('ctrlMain', ['$scope', '$q', '$http', function($scope, $q, $http) {
        function listSubscription(page) {
            var defer = $q.defer(),
                url;
            if (page === undefined) {
                page = {
                    at: 1,
                    size: 10,
                    total: 0,
                    j: function() {
                        return 'page=' + this.at + '&size=' + this.size;
                    }
                };
            }
            url = '/rest/site/fe/user/subscribe/list?site=' + siteId;
            url += '&' + page.j();
            $http.get(url).success(function(rsp) {
                page.total = rsp.data.total;
                defer.resolve({ matters: rsp.data.matters, page: page });
            });
            return defer.promise;
        }
        $scope.openMatter = function(matter) {
            var url = location.protocol + '//' + location.host + '/rest/site/fe/matter';
            if (/article|custom|news|channel/.test(matter.matter_type)) {
                url += '?id=' + matter.matter_id + '&type=' + matter.matter_type + '&site=' + matter.siteid;
            } else {
                url += '/' + matter.matter_type + '?id=' + matter.matter_id + '&site=' + matter.siteid;
            }
            location.href = url;
        };
        $scope.moreMatters = function() {
            $scope.page.at++;
            listSubscription($scope.page).then(function(result) {
                if (result.matters && result.matters.length) {
                    result.matters.forEach(function(matter) {
                        $scope.matters.push(matter);
                    });
                }
            });
        };
        $http.get('/rest/site/fe/get?site=' + siteId).success(function(rsp) {
            $scope.site = rsp.data;
            listSubscription().then(function(result) {
                $scope.matters = result.matters;
                $scope.page = result.page;
            });
            window.loading.finish();
        });
    }]);
    /* bootstrap angular app */
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});