xxtApp.controller('innerCtrl', ['$scope', 'http2', function($scope, http2) {
    var edit = function(inner) {
        $scope.editing = inner;
    };
    $scope.update = function(prop) {
        var nv = {};
        nv[prop] = $scope.editing[prop];
        http2.post('/rest/mp/matter/inner/update?id=' + $scope.editing.id, nv);
    };
    $scope.edit = function(selected) {
        if (selected._cascade === undefined) {
            http2.get('/rest/mp/matter/inner/get?id=' + selected.id, function(rsp) {
                rsp.data.acl !== undefined && (selected.acl = rsp.data.acl);
                edit(selected);
            });
        } else {
            edit(selected);
        }
    };
    $scope.doSearch = function() {
        var url = '/rest/mp/matter/inner/list';
        $scope.fromParent && $scope.fromParent === 'Y' && (url += '&src=p');
        http2.get(url, function(rsp) {
            $scope.inners = rsp.data;
            if ($scope.inners.length)
                $scope.edit($scope.inners[0]);
        });
    };
    $scope.doSearch();
}]);