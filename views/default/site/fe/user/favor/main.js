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
        //判断客户端 区分手机 和pc
        if(/iphone/i.test(navigator.userAgent)||/android/i.test(navigator.userAgent)){
            $scope.state = 1 ;//手机端
        }else{
            $scope.state = 0 ;//其他
        }
        $scope.page = page = {
            at: 1,
            size: 10,
            join: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.list = function() {
            var url = '/rest/site/fe/user/favor/list?site=' + siteId;
            url += '&' + page.join();
            $http.get(url).success(function(rsp) {
                if (rsp.err_code != 0) {
                    $scope.$root.errmsg = rsp.err_msg;
                    return;
                }
                $scope.matters = rsp.data.matters;
                $scope.page.total = rsp.data.total ;
            });
        };
        $scope.openMatter = function(id, type) {
            if (/article|custom|news|channel|link/.test(type)) {
                location.href = '/rest/site/fe/matter?site=' + siteId + '&id=' + id + '&type=' + type;
            } else {
                location.href = '/rest/site/fe/matter/' + type + '?site=' + siteId + '&app=' + id;
            }
        };
        //移除收藏
        $scope.removeFavor = function(rid ,rtype, i){
            var url ='/rest/site/fe/user/favor/remove?site=' + siteId + '&id=' + rid + '&type=' + rtype;
            $http.get(url).success(function(rsp){
                //$scope.list();
                if (rsp.err_code != 0) {
                    $scope.$root.errmsg = rsp.err_msg;
                    return;
                }
                $scope.matters.splice(i,1);
                $scope.page.total--;
            })
        }
        $scope.list();
        window.loading.finish();
    }]);
});
