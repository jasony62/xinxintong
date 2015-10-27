xxtApp.config(['$routeProvider', function($rp) {
    $rp.when('/rest/mp/app/merchant/product/sku', {
        templateUrl: '/views/default/mp/app/merchant/product/sku.html',
        controller: 'skuCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/merchant/product/sku.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/merchant/product/order', {
        templateUrl: '/views/default/mp/app/merchant/product/order.html',
        controller: 'orderCtrl'
    }).otherwise({
        templateUrl: '/views/default/mp/app/merchant/product/setting.html',
        controller: 'settingCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/merchant/product/setting.js?_=1', function() {
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
    $scope.productId = $location.search().product;
    $scope.subView = '';
    $scope.gotoCatelog = function() {
        location.href = '/rest/mp/app/merchant/catelog?shop=' + $scope.shopId + '&catelog=' + $scope.editing.catelog.id;
    };
    $scope.back = function(event) {
        event.preventDefault();
        history.back();
    };
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