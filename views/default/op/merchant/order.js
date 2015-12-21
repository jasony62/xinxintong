app.controller('ctrl', ['$scope', '$http', '$timeout', 'Order', function($scope, $http, $timeout, Order) {
    var ls, facOrder;
    ls = location.search;
    $scope.mpid = ls.match(/mpid=([^&]*)/)[1];
    $scope.shopId = ls.match(/shop=([^&]*)/)[1];
    $scope.orderId = ls.match(/order=([^&]*)/)[1];
    $scope.errmsg = '';
    facOrder = new Order($scope.mpid, $scope.shopId);
    $http.get('/rest/op/merchant/order/pageGet?mpid=' + $scope.mpid + '&shop=' + $scope.shopId).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        var params;
        params = rsp.data;
        $scope.User = params.user;
        //$scope.Page = params.page;
        window.setPage(params.page);
        $timeout(function() {
            $scope.$broadcast('xxt.app.merchant.ready');
        });
    });
    $scope.finish = function() {
        facOrder.finish($scope.orderId).then(function() {
            alert('ok');
        });
    };
    $scope.cancel = function() {
        facOrder.cancel($scope.orderId).then(function() {
            alert('ok');
        });
    };
}]);