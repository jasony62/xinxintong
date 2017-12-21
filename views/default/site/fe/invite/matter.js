'use strict';
var ngApp = angular.module('app', ['ui.bootstrap', 'ui.tms']);
ngApp.controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {
    http2.get('/rest/site/fe/user/get', function(rsp) {
        $scope.loginUser = rsp.data;
        if ($scope.loginUser.unionid) {
            http2.get('/rest/site/fe/invite/create' + location.search, function(rsp) {
                var oInvite = rsp.data;
                $scope.invite = oInvite;
            });
        }
    });
}]);