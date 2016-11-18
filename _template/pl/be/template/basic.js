ngApp.provider.controller('ctrlTemplate', ['$scope', '$http', function($scope, $http) {
    var criteria;
    $scope.criteria = criteria = {
        scope: 'P'
    };
    $scope.page = {
        size: 21,
        at: 1,
        total: 0
    };
    $scope.changeScope = function(scope) {
        criteria.scope = scope;
        $scope.searchTemplate();
    };
    $scope.searchTemplate = function() {
        var url = '/rest/pl/fe/template/platform/list?matterType=enroll&scope=' + criteria.scope;
        $http.get(url).success(function(rsp) {
            $scope.templates = rsp.data.templates;
            $scope.page.total = rsp.data.total;
        });
    };
    $scope.searchTemplate();
}]);