'use strict';
require('../../asset/js/xxt.ui.http.js');
require('../../asset/js/xxt.ui.page.js');
require('../../asset/js/xxt.ui.subscribe.js');
require('../../asset/js/xxt.ui.favor.js');
require('../../asset/js/xxt.ui.forward.js');


var ngApp = angular.module('app', ['ngSanitize', 'ui.bootstrap', 'ui.tms', 'http.ui.xxt', 'page.ui.xxt', 'subscribe.ui.xxt', 'favor.ui.xxt', 'forward.ui.xxt']);
ngApp.config(['$locationProvider', '$controllerProvider', '$uibTooltipProvider', function ($lp, $cp, $uibTooltipProvider) {
    $lp.html5Mode(true);
    ngApp.provider = {
        controller: $cp.register
    };
    $uibTooltipProvider.setTriggers({
        'show': 'hide'
    });
}]);
ngApp.provider('srvUser', function () {
    var _getSiteUserDeferred;
    this.$get = ['$q', 'http2', function ($q, http2) {
        return {
            getSiteUser: function (siteId) {
                if (_getSiteUserDeferred) {
                    return _getSiteUserDeferred.promise;
                }
                _getSiteUserDeferred = $q.defer();
                http2.get('/rest/site/fe/user/get?site=' + siteId).then(function (rsp) {
                    _getSiteUserDeferred.resolve(rsp.data);
                });
                return _getSiteUserDeferred.promise;
            }
        };
    }];
});
ngApp.directive('autoHeight', ['$window', function ($window) {
    return {
        restrict: 'A',
        scope: {},
        link: function ($scope, element, attrs) {
            var winowHeight = $window.innerHeight; //获取窗口高度
            var headerHeight = 52;
            var footerHeight = 50;
            element.css('min-height',
                (winowHeight - headerHeight - footerHeight) + 'px');
        }
    }
}]);
ngApp.directive('imageonload', function () {
    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            element.bind('load', function () {
                //call the function that was passed
                scope.$apply(attrs.imageonload);
            });
        }
    };
});
ngApp.controller('ctrlMain', ['$scope', 'http2', 'srvUser', 'tmsDynaPage', 'tmsSubscribe', function ($scope, http2, srvUser, tmsDynaPage, tmsSubscribe) {
    var platform, oUser, pages = {};

    $scope.subView = '';
    $scope.subscribeSite = function () {
        if (!$scope.user.loginExpire) {
            if (window.sessionStorage) {
                var method = JSON.stringify({
                    name: 'subscribeSite',
                });
                window.sessionStorage.setItem('xxt.home.auth.pending', method);
            }
            location.href = '/rest/site/fe/user/login?site=' + siteId;
        } else {
            tmsSubscribe.open(oUser, $scope.site);
        }
    };
    $scope.shiftPage = function (subView) {
        if ($scope.subView === subView) return;
        if (pages[subView] === undefined) {
            tmsDynaPage.loadCode(ngApp, platform[subView + '_page']).then(function () {
                pages[subView] = platform[subView + '_page'];
                $scope.page = pages[subView] || {
                    html: '<div></div>'
                };
                $scope.subView = subView;
                history.replaceState({}, '', '/rest/home/' + subView);
            });
        } else {
            $scope.page = pages[subView] || {
                html: '<div></div>'
            };
            $scope.subView = subView;
            history.replaceState({}, '', '/rest/home/' + subView);
        }
    };
    $scope.openSite = function (site) {
        location.href = '/rest/site/home?site=' + site.siteid;
    };
    $scope.openTemplate = function (template) {
        location.href = '/rest/site/fe/matter/template?template=' + template.id;
    };
    http2.get('/rest/home/get').then(function (rsp) {
        platform = rsp.data.platform;
        if (platform.home_page === false) {
            // 没有设置主页
            location.href = '/rest/pl/fe';
        } else {
            $scope.platform = platform;
            if (/\/site/.test(location.href)) {
                $scope.shiftPage('site');
            } else if (/\/template/.test(location.href)) {
                $scope.shiftPage('template');
            } else {
                $scope.shiftPage('home');
            }
        }
        srvUser.getSiteUser('platform').then(function (siteUser) {
            $scope.user = oUser = siteUser;
            if (window.sessionStorage) {
                var pendingMethod;
                if (pendingMethod = window.sessionStorage.getItem('xxt.home.auth.pending')) {
                    window.sessionStorage.removeItem('xxt.home.auth.pending');
                    if (user.isLogin) {
                        pendingMethod = JSON.parse(pendingMethod);
                        $scope[pendingMethod.name].apply($scope, pendingMethod.args || []);
                    }
                }
            }
        });
    });
}]);
module.exports = ngApp;