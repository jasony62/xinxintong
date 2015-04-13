xxtApp.controller('WallCtrl',['$scope','http2', function($scope,http2){
    $scope.doSearch = function() {
        var url = '/rest/mp/activity/discuss';
        $scope.fromParent && $scope.fromParent === 'Y' && (url += '?src=p');
        http2.get(url, function(rsp){
            $scope.walls = rsp.data;
        });
    };
    $scope.open = function(wid){
        if (wid === undefined)
            http2.get('/rest/mp/activity/discuss/create', function(rsp){
                location.href = '/page/mp/activity/discuss/detail?wid='+rsp.data;
            });
        else
            location.href = '/page/mp/activity/discuss/detail?wid='+wid;
    };
    $scope.doSearch();
}]);
