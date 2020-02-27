'use strict';
require('../../../asset/js/xxt.ui.http.js');
require('../../../asset/js/xxt.ui.page.js');
require('../../../asset/js/xxt.ui.subscribe.js');
require('../../../asset/js/xxt.ui.contribute.js');
require('../../../asset/js/xxt.ui.favor.js');
require('../../../asset/js/xxt.ui.forward.js');

var ngApp = angular.module('app', ['ui.bootstrap', 'ui.tms', 'http.ui.xxt', 'page.ui.xxt', 'subscribe.ui.xxt', 'contribute.ui.xxt', 'favor.ui.xxt', 'forward.ui.xxt']);
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
ngApp.config(['$controllerProvider', '$uibTooltipProvider', function ($cp, $uibTooltipProvider) {
    ngApp.provider = {
        controller: $cp.register
    };
    $uibTooltipProvider.setTriggers({
        'show': 'hide'
    });
}]);
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
                scope.$apply(attrs.imageonload);
            })
        }
    }
});
ngApp.controller('ctrlMain', ['$scope', 'http2', 'srvUser', 'tmsDynaPage', 'tmsSubscribe', 'tmsContribute', function ($scope, http2, srvUser, tmsDynaPage, tmsSubscribe, tmsContribute) {
    var oUser, ls = location.search,
        siteId = ls.match(/site=([^&]*)/)[1];

    $scope.contributeSite = function () {
        if (!$scope.user.loginExpire) {
            if (window.sessionStorage) {
                var method = JSON.stringify({
                    name: 'contributeSite',
                });
                window.sessionStorage.setItem('xxt.site.home.auth.pending', method);
            }
            location.href = '/rest/site/fe/user/access?site=platform#login';
        } else {
            tmsContribute.open(oUser, $scope.site);
        }
    };
    $scope.subscribeSite = function () {
        if (!$scope.user.loginExpire) {
            if (window.sessionStorage) {
                var method = JSON.stringify({
                    name: 'subscribeSite',
                });
                window.sessionStorage.setItem('xxt.site.home.auth.pending', method);
            }
            location.href = '/rest/site/fe/user/access?site=platform#login';
        } else {
            tmsSubscribe.open(oUser, $scope.site);
        }
    };
    http2.get('/rest/site/home/get?site=' + siteId).then(function (rsp) {
        srvUser.getSiteUser(siteId).then(function (siteUser) {
            $scope.user = oUser = siteUser;
            if (window.sessionStorage) {
                var pendingMethod;
                if (pendingMethod = window.sessionStorage.getItem('xxt.site.home.auth.pending')) {
                    window.sessionStorage.removeItem('xxt.site.home.auth.pending');
                    if (oUser.loginExpire) {
                        pendingMethod = JSON.parse(pendingMethod);
                        $scope[pendingMethod.name].apply($scope, pendingMethod.args || []);
                    }
                }
            }
        });
        tmsDynaPage.loadCode(ngApp, rsp.data.home_page).then(function () {
            if (!rsp.data.heading_pic) {
                rsp.data.heading_pic = '/static/img/avatar.png';
            }
            $scope.site = rsp.data;
            $scope.page = rsp.data.home_page;
        });
        if (rsp.data.icp_beian) {
            let elemIcpbeian = document.querySelector('#icpbeian')
            if (elemIcpbeian) {
                elemIcpbeian.innerHTML = rsp.data.icp_beian
            }
        }
    });
}]);

module.exports = ngApp;