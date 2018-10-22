var ngApp = angular.module('app', []);
ngApp.controller('ctrlMain', ['$scope', '$http', function($scope, $http) {
    $http.get('/rest/site/fe/user/memberschema/list' + location.search).success(function(rsp) {
        $scope.schemas = rsp.data;
    });
}]);
