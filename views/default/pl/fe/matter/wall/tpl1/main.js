xxtApp.controller('wallCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.doSearch = function() {
        var url = '/rest/mp/app/wall/list';
        $scope.fromParent && $scope.fromParent === 'Y' && (url += '?src=p');
        http2.get(url, function(rsp) {
            $scope.walls = rsp.data;
        });
    };
    $scope.open = function(wid) {
        if (wid === undefined)
            http2.get('/rest/mp/app/wall/create', function(rsp) {
                location.href = '/rest/mp/app/wall/detail?wall=' + rsp.data;
            });
        else
            location.href = '/rest/mp/app/wall/detail?wall=' + wid;
    };
    $scope.doSearch();
}]);