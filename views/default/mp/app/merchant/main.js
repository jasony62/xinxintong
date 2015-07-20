xxtApp.controller('shopCtrl', ['$scope', 'http2', function ($scope, http2) {
    $scope.subView = 'catelog';
    $scope.doSearch = function () {
        var url = '/rest/mp/app/merchant/get', params = {};
        $scope.fromParent && $scope.fromParent === 'Y' && (params.src = 'p');
        http2.post(url, params, function (rsp) {
            $scope.shops = rsp.data;
        });
    };
    $scope.open = function (shop) {
        location.href = '/rest/mp/app/merchant?shopId=' + shop.id;
    };
    $scope.create = function () {
        http2.get('/rest/mp/app/merchant/shopCreate', function (rsp) {
            location.href = '/rest/mp/app/merchant?shopId=' + rsp.data;
        });
    };
    http2.get('/rest/mp/mpaccount/get', function (rsp) {
        $scope.mpa = rsp.data;
        $scope.hasParentMp = $scope.mpa.parent_mpid && $scope.mpa.parent_mpid.length ? "Y" : "N";
    });
    $scope.doSearch();
}]);
