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
        templateUrl: '/views/default/mp/app/merchant/shop/order.html',
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
xxtApp.controller('settingCtrl', ['$scope', 'http2', 'Authapi', function($scope, http2, Authapi) {
    $scope.$parent.subView = 'setting';
    $scope.authapis = [];
    (new Authapi()).get('N').then(function(data) {
        var i, l, authapi;
        for (i = 0, l = data.length; i < l; i++) {
            authapi = data[i];
            authapi.valid === 'Y' && $scope.authapis.push(authapi);
        }
    });
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
xxtApp.controller('orderCtrl', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
    var OrderStatus;
    OrderStatus = {
        '1': '未付款',
        '2': '已付款',
        '3': '已确认',
        '5': '已完成',
        '-1': '已取消',
        '-2': '已取消',
    };
    $scope.$parent.subView = 'order';
    $scope.open = function(order) {
        $modal.open({
            templateUrl: 'orderDetail.html',
            backdrop: 'static',
            controller: ['$modalInstance', '$scope', function($mi, $scope2) {
                http2.get('/rest/mp/app/merchant/order/get?order=' + order.id, function(rsp) {
                    $scope2.order = rsp.data.order;
                    $scope2.order._order_status = OrderStatus[$scope2.order.order_status];
                    $scope2.catelogs = rsp.data.catelogs;
                });
                $scope2.sendFeedback = function() {
                    var url, feedback;
                    url = '/rest/mp/app/merchant/order/feedback';
                    url += '?order=' + $scope2.order.id;
                    http2.post(url, $scope2.order.feedback, function(rsp) {
                        alert('ok');
                    });
                };
                $scope2.summarySku = function(catelog, product, sku) {
                    if (sku.summary && sku.summary.length) {
                        return sku.summary;
                    }
                    if (catelog.pattern === 'place' && sku.cateSku.has_validity === 'Y') {
                        var begin, end, hour, min;
                        begin = new Date();
                        begin.setTime(sku.validity_begin_at * 1000);
                        hour = ((begin.getHours() + 100) + '').substr(1);
                        min = ((begin.getMinutes() + 100) + '').substr(1);
                        begin = hour + ':' + min;
                        end = new Date();
                        end.setTime(sku.validity_end_at * 1000);
                        hour = ((end.getHours() + 100) + '').substr(1);
                        min = ((end.getMinutes() + 100) + '').substr(1);
                        end = hour + ':' + min;

                        return begin + '-' + end;
                    }
                    return '';
                };
                $scope2.close = function() {
                    $mi.dismiss();
                };
                $scope2.ok = function() {
                    $mi.close($scope2.page);
                };
            }]
        }).result.then(function() {});
    };
    http2.get('/rest/mp/app/merchant/order/list?shop=' + $scope.shopId, function(rsp) {
        $scope.orders = rsp.data.orders;
        angular.forEach($scope.orders, function(ord) {
            ord._order_status = OrderStatus[ord.order_status];
        });
    });
}]);