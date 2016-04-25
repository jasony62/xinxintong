ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', function($cp, $rp, $lp, $compileProvider) {
    $lp.html5Mode(true);
    ngApp.provider = {
        controller: $cp.register,
        directive: $compileProvider.directive
    };
    $rp.when('/rest/pl/fe/matter/merchant/catelog/page', {
        templateUrl: '/views/default/pl/fe/matter/merchant/catelog/page.html?_=1',
        controller: 'ctrlPage',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/merchant/catelog/page.js?_=1', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/matter/merchant/catelog/tmplmsg', {
        templateUrl: '/views/default/pl/fe/matter/merchant/catelog/tmplmsg.html?_=1',
        controller: 'ctrlTmplmsg',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/merchant/catelog/tmplmsg.js?_=2', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).otherwise({
        templateUrl: '/views/default/pl/fe/matter/merchant/catelog/setting.html?_=1',
        controller: 'ctrlSetting',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/merchant/catelog/setting.js?_=1', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    });
}]);
ngApp.controller('ctrlCatelog', ['$scope', 'http2', '$location', function($scope, http2, $location) {
    $scope.siteId = $location.search().site;
    $scope.shopId = $location.search().shop;
    $scope.catelogId = $location.search().catelog;
    $scope.subView = '';
    $scope.back = function(event) {
        event.preventDefault();
        history.back();
    };
    $scope.gotoShop = function() {
        location.href = '/rest/pl/fe/matter/merchant/shop?site=' + $scope.siteId + '&id=' + $scope.editing.shop.id;
    };
    $scope.get = function() {
        http2.get('/rest/pl/fe/matter/merchant/catelog/get?catelog=' + $scope.catelogId, function(rsp) {
            $scope.editing = rsp.data;
        });
    };
    $scope.get();
}]);
ngApp.controller('orderCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'order';
}]);