xxtApp.controller('appCtrl', ['$scope', 'http2', function ($scope, http2) {
    $scope.create = function () {
        http2.get('/rest/mp/app/contribute/create', function (rsp) {
            location.href = '/rest/mp/app/contribute?id=' + rsp.data.id;
        });
    };
    $scope.edit = function (app) {
        location.href = '/rest/mp/app/contribute?id=' + app.id;
    };
    $scope.remove = function (event, app, index) {
        event.preventDefault();
        event.stopPropagation();
        http2.get('/rest/mp/app/contribute/remove?id=' + app.id, function (rsp) {
            $scope.apps.splice(index, 1);
        });
    };
    $scope.doSearch = function () {
        http2.get('/rest/mp/app/contribute/get', function (rsp) {
            $scope.apps = rsp.data[0];
        });
    };
    $scope.doSearch();
}]);
