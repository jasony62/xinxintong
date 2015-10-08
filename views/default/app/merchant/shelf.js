app.controller('ctrl', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
    var ls, pageId;
    ls = location.search;
    $scope.mpid = ls.match(/mpid=([^&]*)/)[1];
    $scope.shopId = ls.match(/shop=([^&]*)/)[1];
    pageId = ls.match(/page=([^&]*)/)[1];
    $scope.errmsg = '';
    $http.get('/rest/app/merchant/shelf/get?mpid=' + $scope.mpid + '&page=' + pageId).success(function(rsp) {
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
    $scope.gotoOrder = function(product) {
        location.href = '/rest/app/merchant/order?mpid=' + $scope.mpid + '&shop=' + $scope.shopId + '&product=' + product.id;
    };
}]);