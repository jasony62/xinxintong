define(['require', 'angular'], function(require, angular) {
    'use strict';

    function loadCss(url) {
        var link, head;
        link = document.createElement('link');
        link.href = url + '?_=3';
        link.rel = 'stylesheet';
        head = document.querySelector('head');
        head.appendChild(link);
    };
    var siteId = location.search.match('site=(.*)')[1];
    var ngApp = angular.module('app', []);
    ngApp.controller('ctrlFav', ['$scope', '$http', function($scope, $http) {
        var page;
        $scope.page = page = {
            at: 1,
            size: 10,
            join: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.list = function() {
            var url = '/rest/site/fe/user/favor/list?site=' + siteId;
            url += '&' + page.join()
            $http.get(url).success(function(rsp) {
                if (rsp.err_code != 0) {
                    $scope.$root.errmsg = rsp.err_msg;
                    return;
                }
                $scope.matters = rsp.data.matters;
            });
        };
        $scope.openMatter = function(id, type) {
            if (/article|custom|news|channel|link/.test(type)) {
                location.href = '/rest/site/fe/matter?site=' + siteId + '&id=' + id + '&type=' + type;
            } else {
                location.href = '/rest/site/fe/matter/' + type + '?site=' + siteId + '&app=' + id;
            }
        };
        $scope.list();
        window.loading.finish();
    }]);
});
