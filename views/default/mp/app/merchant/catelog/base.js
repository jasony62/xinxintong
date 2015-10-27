xxtApp.config(['$routeProvider', function($rp) {
    $rp.when('/rest/mp/app/merchant/catelog/sku', {
        templateUrl: '/views/default/mp/app/merchant/catelog/sku.html',
        controller: 'skuCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/merchant/catelog/sku.js?_=2', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/merchant/catelog/page', {
        templateUrl: '/views/default/mp/app/merchant/catelog/page.html',
        controller: 'pageCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/merchant/catelog/page.js?_=1', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/merchant/catelog/product', {
        templateUrl: '/views/default/mp/app/merchant/catelog/product.html',
        controller: 'productCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/merchant/catelog/product.js?_=2', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/merchant/catelog/tmplmsg', {
        templateUrl: '/views/default/mp/app/merchant/catelog/tmplmsg.html',
        controller: 'tmplmsgCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/merchant/catelog/tmplmsg.js?_=2', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/merchant/catelog/order', {
        templateUrl: '/views/default/mp/app/merchant/catelog/order.html',
        controller: 'orderCtrl'
    }).otherwise({
        templateUrl: '/views/default/mp/app/merchant/catelog/setting.html',
        controller: 'settingCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/merchant/catelog/setting.js?_=1', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    });
}]);
xxtApp.controller('catelogCtrl', ['$scope', 'http2', '$location', function($scope, http2, $location) {
    $scope.shopId = $location.search().shop;
    $scope.catelogId = $location.search().catelog;
    $scope.subView = '';
    $scope.back = function(event) {
        event.preventDefault();
        history.back();
    };
    $scope.gotoShop = function() {
        location.href = '/rest/mp/app/merchant/shop?shop=' + $scope.editing.shop.id;
    };
    $scope.get = function() {
        http2.get('/rest/mp/app/merchant/catelog/get?catelog=' + $scope.catelogId, function(rsp) {
            $scope.editing = rsp.data;
        });
    };
    $scope.get();
}]);
xxtApp.controller('orderCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'order';
}]);