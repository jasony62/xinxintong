xxtApp.controller('RouletteCtrl',['$scope','http2', function($scope,http2){
    $scope.doSearch = function() {
        var url = '/rest/mp/activity/lottery';
        $scope.fromParent && $scope.fromParent === 'Y' && (url += '?src=p');
        http2.get(url, function(rsp){
            $scope.activities = rsp.data;
        });
    };
    $scope.open = function(lid){
        if (lid === undefined)
            http2.get('/rest/mp/activity/lottery/create', function(rsp){
                location.href = '/page/mp/activity/lottery/detail?lid='+rsp.data;
            });
        else
            location.href = '/page/mp/activity/lottery/detail?lid='+lid;
    };
    $scope.doSearch();
}]);
