var app = angular.module('ngApp', []);
app.controller('ctrlNgApp', ['$scope', '$http', function($scope, $http){
    var url = ;

    $scope.order = function(item) {

    }
    $http.get(url).success(function(rsp){
        $scope.results = rsp.data;
    })
}]);
