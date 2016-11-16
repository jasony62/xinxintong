ngApp.provider.controller('ctrlSite', ['$scope', '$http', function($scope, $http) {
    $scope.criteria = {
        scope: 'A'
    };
    $scope.page = {
        size: 21,
        at: 1,
        total: 0
    };
    $scope.changeScope = function(scope) {
        $scope.criteria.scope = scope;
        $scope.searchTemplate();
    };
    $scope.searchSite = function() {
        var url = '/rest/home/listSite';
        $http.get(url).success(function(rsp) {
            $scope.sites = rsp.data.sites;
        });
    };
    $scope.searchSite();
}]);