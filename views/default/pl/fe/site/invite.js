define(['require', 'ngSanitize', 'tmsUI'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'http.ui.xxt', 'notice.ui.xxt']);
    ngApp.config(['$locationProvider', function($locationProvider) {
        $locationProvider.html5Mode(true);
    }]);
    ngApp.controller('ctrlApp', ['$scope', '$location', '$q', 'http2', 'noticebox', function($scope, $location, $q, http2, noticebox) {
        var inviteCode = $location.search().code;
        //受邀请成为团队管理员，之后跳转到设置页面
        $scope.accept = function() {
            var url = '/rest/pl/fe/site/coworker/acceptInvite?code=' + inviteCode;
            http2.get(url).then(function(rsp) {
                var acl = rsp.data;
                location.href = '/rest/pl/fe/site/setting?site=' + acl.siteid
            });
        };
        //获取邀请信息
        http2.get('/rest/pl/fe/site/coworker/invite?code=' + inviteCode).then(function(rsp) {
            if (rsp.err_code != '0') {
                $scope.errmsg = rsp.err_msg;
            } else {
                $scope.task = rsp.data;
            }
        }, {
            autoBreak: false,
            autoNotice: false,
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});