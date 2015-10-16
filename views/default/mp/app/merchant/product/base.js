xxtApp.config(['$routeProvider', function($rp) {
    $rp.when('/rest/mp/app/merchant/product/sku', {
        templateUrl: '/views/default/mp/app/merchant/product/sku.html',
        controller: 'skuCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/merchant/product/sku.js?_=2', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/merchant/catelog/order', {
        templateUrl: '/views/default/mp/app/merchant/product/order.html',
        controller: 'orderCtrl'
    }).otherwise({
        templateUrl: '/views/default/mp/app/merchant/product/setting.html',
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
xxtApp.controller('productCtrl', ['$scope', 'http2', '$location', function($scope, http2, $location) {
    $scope.shopId = $location.search().shop;
    $scope.catelogId = $location.search().catelog;
    $scope.productId = $location.search().product;
    $scope.subView = '';
    $scope.get = function() {
        http2.get('/rest/mp/app/merchant/product/get?product=' + $scope.productId, function(rsp) {
            $scope.editing = rsp.data;
        });
    };
    $scope.get();
}]);
xxtApp.controller('orderCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'order';
}]);