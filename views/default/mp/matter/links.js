xxtApp.controller('linkCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.create = function() {
        http2.get('/rest/mp/matter/link/create', function(rsp) {
            location.href = '/page/mp/matter/link?id=' + rsp.data.id;
        });
    };
    $scope.edit = function(link) {
        location.href = '/page/mp/matter/link?id=' + link.id;
    };
    $scope.remove = function(event, link) {
        event.preventDefault();
        event.stopPropagation();
        if (confirm('确认删除？'))
            http2.get('/rest/mp/matter/link/remove?id=' + link.id, function(rsp) {
                var i = $scope.links.indexOf(link);
                $scope.links.splice(i, 1);
            });
    };
    $scope.doSearch = function() {
        var url = '/rest/mp/matter/link/list?cascade=n',
            params = {};
        $scope.fromParent && $scope.fromParent === 'Y' && (params.src = 'p');
        http2.post(url, params, function(rsp) {
            $scope.links = rsp.data;
        });
    };
    $scope.doSearch();
}]);