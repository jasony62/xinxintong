define(["angular", "xxt-page"], function(angular, codeAssembler) {
    'use strict';
    var ngApp = angular.module('home', ['ui.bootstrap', 'ui.tms']);
    ngApp.config(['$locationProvider', '$controllerProvider', function($lp, $cp) {
        $lp.html5Mode(true);
        ngApp.provider = {
            controller: $cp.register
        };
    }]);
    ngApp.controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {
        var platform, pages = {};
        $scope.subView = '';
        $scope.shiftPage = function(subView) {
            if ($scope.subView === subView) return;
            if (pages[subView] === undefined) {
                codeAssembler.loadCode(ngApp, platform[subView + '_page']).then(function() {
                    pages[subView] = platform[subView + '_page'];
                    $scope.page = pages[subView] || {
                        html: '<div></div>'
                    };
                    $scope.subView = subView;
                });
            } else {
                $scope.page = pages[subView] || {
                    html: '<div></div>'
                };
                $scope.subView = subView;
            }
        };
        $scope.openSite = function(site) {
            location.href = '/rest/site/home?site=' + site.siteid;
        };

        $scope.listApps = function() {
            http2.get('/rest/home/listApp', function(rsp) {
                $scope.apps = rsp.data.matters;
            });
        };
        $scope.listArticles = function() {
            http2.get('/rest/home/listArticle', function(rsp) {
                $scope.articles = rsp.data.matters;
            });
        };
        http2.get('/rest/home/get', function(rsp) {
            platform = rsp.data.platform;
            if (platform.home_page === false) {
                location.href = '/rest/pl/fe';
            } else {
                $scope.platform = platform;
                $scope.shiftPage('home');
            }
        });
    }]);
    /*bootstrap*/
    angular._lazyLoadModule('home');

    return ngApp;
});
