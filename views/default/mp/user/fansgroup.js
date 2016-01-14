xxtApp.controller('groupCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.groups = [];
    $scope.page = {
        at: 1,
        size: 30
    };
    $scope.edit = function(g) {
        if ($scope.editing !== g) {
            $scope.editing = g;
            $scope.fans = null;
            $scope.searchFans();
        }
    };
    $scope.searchFans = function() {
        if ($scope.editing.id === undefined) return;
        var url;
        url = '/rest/mp/user/fans/list?gid=' + $scope.editing.id;
        url += '&page=' + $scope.page.at + '&size=' + $scope.page.size;
        http2.get(url, function(rsp) {
            $scope.fans = rsp.data[0];
            rsp.data[1] !== undefined && ($scope.page.total = rsp.data[1]);
        });
    };
    $scope.addGroup = function() {
        var newObj = {
            name: '新分组'
        };
        $scope.groups.push(newObj);
        $scope.editing = newObj;
    };
    $scope.save = function() {
        if ($scope.editing.id === undefined) {
            http2.post('/rest/mp/user/fans/addGroup', $scope.editing, function(rsp) {
                $scope.editing.id = rsp.data.id;
            });
        } else
            http2.post('/rest/mp/user/fans/updateGroup', $scope.editing);
    };
    $scope.viewUser = function(fan) {
        location.href = '/rest/mp/user?openid=' + fan.openid;
    };
    http2.get('/rest/mp/mpaccount/apis', function(rsp) {
        $scope.apis = rsp.data;
    });
    http2.get('/rest/mp/user/fans/group', function(rsp) {
        $scope.groups = rsp.data
    });
}]);