app.controller('ctrl', ['$scope', '$http', '$timeout', '$q', function($scope, $http, $timeout, $q) {
    var ls;
    ls = location.search;
    $scope.mpid = ls.match(/mpid=([^&]*)/)[1];
    $scope.shopId = ls.match(/shop=([^&]*)/)[1];
    $scope.orderId = ls.match(/order=([^&]*)/)[1];
    $scope.errmsg = '';
    $http.get('/rest/op/merchant/order/pageGet?mpid=' + $scope.mpid + '&shop=' + $scope.shopId).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        var params;
        params = rsp.data;
        $scope.User = params.user;
        $scope.Page = params.page;
        window.setPage(params.page);
        $timeout(function() {
            $scope.$broadcast('xxt.app.merchant.ready');
        });
    });
    $scope.orderExtPropValue = function(ope) {
        var val = '';
        if ($scope.order.extPropValues[ope.id]) {
            val = $scope.order.extPropValues[ope.id];
        }
        return val;
    };
    $scope.finish = function() {

    };
    $scope.cancel = function() {

    };
}]);