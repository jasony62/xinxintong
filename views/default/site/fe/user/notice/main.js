define(['require', 'angular'], function(require, angular) {
    'use strict';
    var siteId, ngApp;
    siteId = location.search.match('site=(.*)')[1];
    ngApp = angular.module('app', ['ngSanitize', 'ui.bootstrap']);
    ngApp.service('srvNotice', ['$http', '$q', function($http, $q) {
        var _baseUrl = '/rest/site/fe/user/notice';
        return {
            list: function(oPage) {
                var deferred = $q.defer();
                $http.get(_baseUrl + '/list?site=' + siteId + '&' + oPage.j()).success(function(rsp) {
                    deferred.resolve(rsp.data);
                });
                return deferred.promise;
            },
            uncloseList: function(oPage) {
                var deferred = $q.defer();
                $http.get(_baseUrl + '/uncloseList?site=' + siteId + '&' + oPage.j()).success(function(rsp) {
                    deferred.resolve(rsp.data);
                });
                return deferred.promise;
            }
        }
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$http', 'srvNotice', function($scope, $http, srvNotice) {
        var oPage, aLogs, oFilter;
        $scope.oPage = oPage = {
            at: 0,
            size: 10,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.oFilter = oFilter = {
            type: 'part'
        }
        $scope.logs = aLogs = [];
        $scope.close = function(id) {
            var url = '/rest/site/fe/user/notice/close?site=' + siteId + '&id=' + id;

        }
        $scope.more = function() {
            oPage.at++;
            var data = oFilter.type == 'all' ? srvNotice.list(oPage) : srvNotice.uncloseList(oPage);
            data.then(function(result) {
                result.logs.forEach(function(log) {
                    log._noticeStatus = log.status.split(':');
                    log._noticeStatus[0] = log._noticeStatus[0] === 'success' ? '成功' : '失败';
                    aLogs.push(log);
                });
                oPage.total = result.total;
            });
        };
        $http.get('/rest/site/fe/get?site=' + siteId).success(function(rsp) {
            $scope.site = rsp.data;
            window.loading.finish();
        });
        $scope.$watch('oFilter', function(nv) {
            if(!nv) return;
            $scope.more();
        });
    }]);
    /* bootstrap angular app */
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});
