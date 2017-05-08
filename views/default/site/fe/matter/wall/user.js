var ngApp = angular.module('app', []);
ngApp.config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlWall', ['$scope', '$http', '$location', function($scope, $http, $location) {
    var ls = $location.search();
    $scope.id = ls.app;
    $scope.siteId = ls.site;
    $scope.open = function(wall) {
        if (wall.active == 'N') {
            alert('信息墙已停用');
            return
        };
        location.href = '/rest/site/fe/matter/wall?site=' + $scope.siteId + '&app=' + wall.id;
    };
    var url = '/rest/site/fe/matter/wall/byUser?site=' + $scope.siteId;
    $http.get(url).success(function(rsp) {
        $scope.walls = rsp.data;
    });
}]);
