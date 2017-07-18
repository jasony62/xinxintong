define(['require', 'angular'], function(require, angular) {
    'use strict';
    var siteId, ngApp;
    siteId = location.search.match('site=(.*)')[1];
    ngApp = angular.module('app', ['ngSanitize', 'ui.bootstrap']);
    ngApp.controller('ctrlMain', ['$scope', '$http', '$q', function($scope, $http, $q) {
        var _logs, page, filter;
        _logs = [];
        $scope.filter = filter = {
            type: 'all'
        };
        $scope.page = page = {
            at: 1,
            size: 15,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        function list(url) {
            $http.get(url).success(function(rsp) {
                _logs.splice(0, _logs.length);
                rsp.data.logs.forEach(function(log) {
                    if (log.data) {
                        log._message = JSON.parse(log.data);
                        log._message = log._message.join('\n');
                    }
                    _logs.push(log);
                });
                $scope.logs = _logs;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.doSearch = function() {
            var url;
            if(filter.type=='all') {
                url = '/rest/site/fe/user/message/list?site=' + siteId + page.j();
            }else {
                url = '/rest/site/fe/user/message/uncloseList?site=' + siteId + page.j();
            }
            list(url);
        };
        $scope.closeNotice = function(log) {
            var url, index;
            url = '/rest/site/fe/user/message/close?site=' + siteId +'&id=' + log.id;
            index = $scope.logs.indexOf(log);
            $http.get(url).success(function(rsp) {
                if (filter.type=='part') {
                    $scope.logs.splice(index, 1);
                    $scope.page.total--;
                } else {
                    var currentLog = (document.querySelector(".list-group").children)[index];
                    angular.element(currentLog).find('.pull-right').html("<div class='badge'>已读</div>");
                }
            });
        };
        $http.get('/rest/site/fe/get?site=' + siteId).success(function(rsp) {
            $scope.site = rsp.data;
            window.loading.finish();
        });
        $scope.$watch('filter.type', function(nv) {
            if(!nv) return;
            $scope.doSearch();
        });
    }]);
    /* bootstrap angular app */
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});
