define(['require', 'angular'], function(require, angular) {
    'use strict';
    var siteId, ngApp;
    siteId = location.search.match('site=(.*)')[1];
    ngApp = angular.module('app', ['ngSanitize', 'ui.bootstrap']);
    ngApp.service('srvNotice', ['$http', '$q', function($http, $q) {
        var _baseUrl = '/rest/site/fe/user/notice';
        return {
            list: function(type, oPage) {
                var deferred = $q.defer(),
                    portName = type == 'all' ? 'list' : 'uncloseList';
                $http.get(_baseUrl + '/' + portName + '?site=' + siteId + '&' + oPage.j()).success(function(rsp) {
                    if (rsp.data !== null) {
                        rsp.data.logs.forEach(function(log) {
                            log._noticeStatus = log.status.split(':');
                            log._noticeStatus[0] = log._noticeStatus[0] === 'success' ? '成功' : '失败';
                        });
                        deferred.resolve(rsp.data);
                    }

                });
                return deferred.promise;
            }
        }
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$http', 'srvNotice', function($scope, $http, srvNotice) {
        var oPage, oFilter;
        $scope.oPage = oPage = {
            at: 1,
            size: 10,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.oFilter = oFilter = {
            type: 'part'
        };
        $scope.closeNotice = function(log) {
            var url = '/rest/site/fe/user/notice/close?site=' + siteId + '&id=' + log.id;
            $http.get(url).success(function(rsp) {
                var index = $scope.logs.indexOf(log);
                $scope.logs.splice(index, 1);
                $scope.oPage.total--;
            });
        };

        function searchNotices(append) {
            srvNotice.list(oFilter.type, oPage).then(function(result) {
                if (append) {
                    $scope.logs = $scope.logs.concat(result.logs);
                } else {
                    $scope.logs = result.logs;
                }
                oPage.total = result.total;
            });
        }
        $scope.more = function() {
            $scope.oPage.at++;
            searchNotices(true);
        }
        $http.get('/rest/site/fe/get?site=' + siteId).success(function(rsp) {
            $scope.site = rsp.data;
            window.loading.finish();
        });
        $scope.$watch('oFilter.type', function(nv) {
            if (!nv) return;
            oPage.at = 1;
            oPage.total = 0;
            searchNotices(false);
        });
    }]);
    /* bootstrap angular app */
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});
