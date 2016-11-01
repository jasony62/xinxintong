define(["angular", "xxt-page"], function(angular, codeAssembler) {
    'use strict';
    var ngApp = angular.module('home', ['ui.bootstrap']);
    ngApp.config(['$locationProvider', '$controllerProvider', function($lp, $cp) {
        $lp.html5Mode(true);
        ngApp.provider = {
            controller: $cp.register
        };
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$http', '$q', function($scope, $http, $q) {
        var platform, pages = {};
        $scope.subView = '';
        $scope.shiftPage = function(subView) {
            if ($scope.subView === subView) return;
            if (pages[subView] === undefined) {
                codeAssembler.loadCode(ngApp, platform[subView + '_page']).then(function() {
                    pages[subView] = platform[subView + '_page'];
                    $scope.page = pages[subView];
                    $scope.subView = subView;
                });
            } else {
                $scope.page = pages[subView];
                $scope.subView = subView;
            }
        };
        $http.get('/rest/pl/fe/user/auth/isLogin').success(function(rsp) {
            $scope.isLogin = rsp.data;
        });
        $http.get('/rest/home/get').success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            platform = rsp.data.platform;
            $scope.platform = platform;
            $scope.shiftPage('home');
        }).error(function(content, httpCode) {
            $scope.errmsg = content;
        });
    }]);

    /*bootstrap*/
    angular._lazyLoadModule('home');

    return ngApp;
});