angular.module('xxtApp', []).
controller('wallCtrl',['$scope','$http','$timeout',function($scope,$http,$timeout){
    $scope.doSearch = function() {
        $http.get('/rest/app/mywall?wid='+$scope.wid, {
            headers: {'Accept':'application/json'}                     
        }).success(function(rsp){
            $scope.messages = rsp.data[0];
        });
    };
    $scope.$watch('jsonMessages', function(nv){
        if (nv && nv.length > 0) {
            $scope.messages = JSON.parse(decodeURIComponent(nv));
            $timeout(function(){window.scrollBy(0,10000);},100);
        }
    })
}]);
