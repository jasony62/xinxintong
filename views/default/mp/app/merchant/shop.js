xxtApp.config(['$routeProvider', function($rp) {
    $rp.when('/rest/mp/app/merchant/setting', {
        templateUrl: '/views/default/mp/app/merchant/setting.html',
        controller: 'settingCtrl'
    }).when('/rest/mp/app/merchant/catelog', {
        templateUrl: '/views/default/mp/app/merchant/catelog.html',
        controller: 'catelogCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/merchant/catelog.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/merchant/product', {
        templateUrl: '/views/default/mp/app/merchant/product.html',
        controller: 'productCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/app/merchant/product.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/app/merchant/group', {
        templateUrl: '/views/default/mp/app/merchant/group.html',
        controller: 'groupCtrl'
    }).when('/rest/mp/app/merchant/order', {
        templateUrl: '/views/default/mp/app/merchant/order.html',
        controller: 'orderCtrl'
    }).otherwise({
        templateUrl: '/views/default/mp/app/merchant/setting.html',
        controller: 'settingCtrl'
    });
}]);
xxtApp.controller('shopCtrl', ['$scope', 'http2', '$location', function($scope, http2, $location) {
    $scope.shopId = $location.search().shopId;
    $scope.subView = '';
}]);
xxtApp.controller('settingCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'setting';
    $scope.update = function(name) {
        var nv = {};
        nv[name] = $scope.editing[name];
        http2.post('/rest/mp/app/merchant/shop/update?id=' + $scope.shopId, nv, function(rsp) {});
    };
    http2.get('/rest/mp/app/merchant/shop/get?id=' + $scope.shopId, function(rsp) {
        $scope.editing = rsp.data;
    });
}]);
xxtApp.controller('groupCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'group';
}]);
xxtApp.controller('orderCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'order';
}]);