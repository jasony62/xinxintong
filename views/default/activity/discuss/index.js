angular.module('xxt', []).
controller('discussCtrl',['$scope','$http',function($scope,$http){
    $scope.open = function(wall) {
        location.href = '/rest/activity/discuss?wid='+wall.wid;
    };
    $scope.$watch('jsonWalls', function(nv){
        if (nv && nv.length > 0)
            $scope.walls = JSON.parse(decodeURIComponent(nv));
    });
}]);
