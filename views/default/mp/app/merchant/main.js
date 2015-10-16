xxtApp.controller('shopCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.subView = 'catelog';
    $scope.doSearch = function() {
        var url = '/rest/mp/app/merchant/shop/list',
            params = {};
        $scope.fromParent && $scope.fromParent === 'Y' && (params.src = 'p');
        http2.post(url, params, function(rsp) {
            $scope.shops = rsp.data;
        });
    };
    $scope.open = function(shop) {
        location.href = '/rest/mp/app/merchant/shop?shop=' + shop.id;
    };
    $scope.create = function() {
        http2.get('/rest/mp/app/merchant/shop/create', function(rsp) {
            location.href = '/rest/mp/app/merchant/shop?shop=' + rsp.data;
        });
    };
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpa = rsp.data;
        $scope.hasParentMp = $scope.mpa.parent_mpid && $scope.mpa.parent_mpid.length ? "Y" : "N";
    });
    $scope.doSearch();
}]);