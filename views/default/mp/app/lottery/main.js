xxtApp.controller('lotteryCtrl', ['$scope', 'http2', function ($scope, http2) {
    $scope.doSearch = function () {
        var url = '/rest/mp/app/lottery/get';
        $scope.fromParent && $scope.fromParent === 'Y' && (url += '?src=p');
        http2.get(url, function (rsp) {
            $scope.apps = rsp.data;
        });
    };
    $scope.open = function (lid) {
        if (lid === undefined)
            http2.get('/rest/mp/app/lottery/create', function (rsp) {
                location.href = '/page/mp/app/lottery/detail?lid=' + rsp.data;
            });
        else
            location.href = '/page/mp/app/lottery/detail?lid=' + lid;
    };
    $scope.doSearch();
}]);
