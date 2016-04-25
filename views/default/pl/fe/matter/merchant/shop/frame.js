ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', function($cp, $rp, $lp, $compileProvider) {
    $lp.html5Mode(true);
    ngApp.provider = {
        controller: $cp.register,
        directive: $compileProvider.directive
    };
    $rp.when('/rest/pl/fe/matter/merchant/shop/page', {
        templateUrl: '/views/default/pl/fe/matter/merchant/shop/page.html?_=1',
        controller: 'ctrlPage',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/merchant/shop/page.js?_=1', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/matter/merchant/shop/order', {
        templateUrl: '/views/default/pl/fe/matter/merchant/shop/order.html',
        controller: 'orderCtrl'
    }).otherwise({
        templateUrl: '/views/default/pl/fe/matter/merchant/shop/setting.html?_=1',
        controller: 'ctrlSetting'
    });
}]);
ngApp.controller('ctrlShop', ['$scope', 'http2', '$location', function($scope, http2, $location) {
    $scope.siteId = $location.search().site;
    $scope.shopId = $location.search().id;
    $scope.subView = '';
    $scope.back = function(event) {
        event.preventDefault();
        history.back();
    };
    http2.get('/rest/pl/fe/site/member/schema/list?site=' + $scope.siteId, function(rsp) {
        $scope.memberSchemas = rsp.data;
    });
}]);
ngApp.controller('ctrlSetting', ['$scope', 'http2', '$modal', function($scope, http2, $modal) {
    $scope.$parent.subView = 'setting';
    $scope.orderStatus = [{
        id: '1',
        name: '未付款',
        title: '未付款',
        desc: '用户提交订单'
    }, {
        id: '2',
        name: '已付款',
        title: '已付款',
        desc: '用户提交订单并完成付款'
    }, {
        id: '3',
        name: '已确认',
        title: '已确认',
        desc: ''
    }, {
        id: '5',
        name: '已完成',
        title: '已完成',
        desc: ''
    }, {
        id: '-1',
        name: '客服取消',
        title: '已取消',
        desc: '客户取消订单'
    }, {
        id: '-2',
        name: '用户取消',
        title: '已取消',
        desc: '用户提交订单后取消订单'
    }];
    /*支付渠道*/
    $scope.payby = {
        'coin': 'N',
        'wx': 'N',
        join: function() {
            var j = [];
            this.coin === 'Y' && j.push('coin');
            this.wx === 'Y' && j.push('wx');
            return j.join(',');
        }
    };
    $scope.update = function(name) {
        var nv = {};
        if (name === 'payby') {
            nv.payby = $scope.payby.join();
        } else {
            nv[name] = $scope.editing[name];
        }
        http2.post('/rest/pl/fe/matter/merchant/shop/update?site=' + $scope.siteId + '&shop=' + $scope.shopId, nv, function(rsp) {});
    };
    $scope.configOrderStatus = function(orderStatus) {
        $modal.open({
            templateUrl: 'orderStatusEditor.html',
            backdrop: 'static',
            controller: ['$modalInstance', '$scope', function($mi, $scope2) {
                $scope2.status = angular.copy(orderStatus);
                $scope2.close = function() {
                    $mi.dismiss();
                };
                $scope2.ok = function() {
                    $mi.close($scope2.status);
                };
            }]
        }).result.then(function(newStatus) {
            if (orderStatus.title !== newStatus.title) {
                orderStatus.title = newStatus.title;
                $scope.editing.order_status[newStatus.id] = newStatus.title;
                $scope.update('order_status');
            }
        });
    };
    http2.get('/rest/pl/fe/matter/merchant/shop/get?site=' + $scope.siteId + '&shop=' + $scope.shopId, function(rsp) {
        var shop = rsp.data;
        $scope.editing = shop;
        if (Object.keys(shop.order_status).length === 0) {
            shop.order_status = {};
            angular.forEach($scope.orderStatus, function(os) {
                shop.order_status[os.id] = os.title;
            });
            $scope.update('order_status');
        } else {
            angular.forEach($scope.orderStatus, function(os) {
                os.title = shop.order_status[os.id];
            });
        }
        if (shop.payby && shop.payby.length) {
            angular.forEach(shop.payby.split(','), function(name) {
                $scope.payby[name] = 'Y';
            });
        }
        shop.canSetSupporter = 'Y';
    });
}]);
ngApp.controller('orderCtrl', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
    var OrderStatus;
    $scope.$parent.subView = 'order';
    $scope.open = function(order) {
        $modal.open({
            templateUrl: 'orderDetail.html',
            backdrop: 'static',
            controller: ['$modalInstance', '$scope', function($mi, $scope2) {
                http2.get('/rest/pl/fe/matter/merchant/order/get?order=' + order.id, function(rsp) {
                    $scope2.order = rsp.data.order;
                    $scope2.order._order_status = OrderStatus[$scope2.order.order_status];
                    $scope2.catelogs = rsp.data.catelogs;
                });
                $scope2.sendFeedback = function() {
                    var url, feedback;
                    url = '/rest/pl/fe/matter/merchant/order/feedback';
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
    $scope.page = {
        at: 1,
        size: 30,
        joinParams: function() {
            var p;
            p = '&page=' + this.at + '&size=' + this.size;
            return p;
        }
    };
    $scope.doSearch = function() {
        http2.get('/rest/pl/fe/matter/merchant/order/list?shop=' + $scope.shopId + $scope.page.joinParams(), function(rsp) {
            $scope.orders = rsp.data.orders;
            $scope.page.total = rsp.data.total;
            angular.forEach($scope.orders, function(ord) {
                ord._order_status = OrderStatus[ord.order_status];
            });
        });
    };
    http2.get('/rest/pl/fe/matter/merchant/shop/get?shop=' + $scope.shopId, function(rsp) {
        OrderStatus = rsp.data.order_status;
        $scope.doSearch();
    });
}]);
ngApp.controller('ctrlCatelog', ['$scope', 'http2', function($scope, http2) {
    $scope.list = function() {
        http2.get('/rest/pl/fe/matter/merchant/catelog/list?site=' + $scope.siteId + '&shop=' + $scope.shopId, function(rsp) {
            $scope.catelogs = rsp.data;
        });
    };
    $scope.open = function(catelog) {
        location.href = "/rest/pl/fe/matter/merchant/catelog?site=" + $scope.siteId + "&shop=" + $scope.shopId + "&catelog=" + catelog.id;
    };
    $scope.create = function() {
        http2.get('/rest/pl/fe/matter/merchant/catelog/create?site=' + $scope.siteId + '&shop=' + $scope.shopId, function(rsp) {
            $scope.open(rsp.data);
        });
    };
    $scope.list();
}]);
ngApp.controller('ctrlProduct', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
    $scope.selectedCatelog = null;
    $scope.search = function() {
        var url;
        url = '/rest/pl/fe/matter/merchant/product/list';
        url += '?site=' + $scope.siteId;
        url += '&shop=' + $scope.shopId;
        url += '&catelog=' + $scope.selectedCatelog.id;
        http2.get(url, function(rsp) {
            $scope.products = rsp.data;
        });
    };
    $scope.open = function(product) {
        location.href = "/rest/pl/fe/matter/merchant/product?site=" + $scope.siteId + "&shop=" + $scope.shopId + "&product=" + product.id;
    };
    $scope.create = function() {
        $modal.open({
            templateUrl: 'catelogSelector.html',
            backdrop: 'static',
            controller: ['$modalInstance', '$scope', function($mi, $scope2) {
                $scope2.catelogs = $scope.catelogs;
                $scope2.data = {
                    selected: $scope.selectedCatelog
                };
                $scope2.close = function() {
                    $mi.dismiss();
                };
                $scope2.ok = function() {
                    $mi.close($scope2.data.selected);
                };
            }]
        }).result.then(function(catelog) {
            if (catelog !== null) {
                var url = '/rest/pl/fe/matter/merchant/product/create';
                url += '?catelog=' + catelog.id;
                http2.get(url, function(rsp) {
                    var prod = rsp.data;
                    $scope.open(prod);
                    prod.prop_value2 = rsp.data.propValue2;
                    $scope.products.push(prod);
                    $scope.open(prod);
                });
            }
        });
    };
    $scope.selectCatelog = function() {
        $scope.products = [];
        $scope.search();
    };
    http2.get('/rest/pl/fe/matter/merchant/catelog/list?site=' + $scope.siteId + '&shop=' + $scope.shopId, function(rsp) {
        $scope.catelogs = rsp.data;
        if (rsp.data.length) {
            $scope.selectedCatelog = rsp.data[0];
            $scope.selectCatelog();
        }
    });
}]);