angular.module('xxt', []).
controller('wallCtrl',['$scope','$http',function($scope,$http){
    $scope.open = function(wall) {
        location.href = '/rest/app/wall?wid='+wall.wid;
    };
    $scope.$watch('jsonWalls', function(nv){
        if (nv && nv.length > 0)
            $scope.walls = JSON.parse(decodeURIComponent(nv));
    });
}]);
