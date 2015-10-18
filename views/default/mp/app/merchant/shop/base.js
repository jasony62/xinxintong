xxtApp.config(['$routeProvider', function($rp) {
    $rp.when('/rest/mp/app/merchant/shop/catelog', {
        templateUrl: '/views/default/mp/app/merchant/shop/catelog.html',
        controller: 'catelogCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/merchant/shop/catelog.js?_=1', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/merchant/shop/product', {
        templateUrl: '/views/default/mp/app/merchant/shop/product.html',
        controller: 'productCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/merchant/shop/product.js?_=1', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/merchant/shop/page', {
        templateUrl: '/views/default/mp/app/merchant/shop/page.html',
        controller: 'pageCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/merchant/shop/page.js?_=1', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/merchant/shop/order', {
        templateUrl: '/views/default/mp/app/merchant/order.html',
        controller: 'orderCtrl'
    }).otherwise({
        templateUrl: '/views/default/mp/app/merchant/shop/setting.html',
        controller: 'settingCtrl'
    });
}]);
xxtApp.controller('shopCtrl', ['$scope', 'http2', '$location', function($scope, http2, $location) {
    $scope.shopId = $location.search().shop;
    $scope.subView = '';
    $scope.back = function(event) {
        event.preventDefault();
        history.back();
    };
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpaccount = rsp.data;
    });
}]);
xxtApp.controller('settingCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'setting';
    $scope.update = function(name) {
        var nv = {};
        nv[name] = $scope.editing[name];
        http2.post('/rest/mp/app/merchant/shop/update?shop=' + $scope.shopId, nv, function(rsp) {});
    };
    http2.get('/rest/mp/app/merchant/shop/get?shop=' + $scope.shopId, function(rsp) {
        $scope.editing = rsp.data;
        $scope.editing.canSetSupporter = 'Y';
    });
}]);
xxtApp.controller('orderCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'order';
}]);