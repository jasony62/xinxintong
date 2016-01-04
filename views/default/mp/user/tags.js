xxtApp.controller('tagCtrl', ['$rootScope', '$scope', 'http2', function($rootScope, $scope, http2) {
    var removeEditing = function() {
        var i = $scope.tags.indexOf($scope.editing);
        $scope.tags.splice(i, 1);
        $scope.editing = null;
    };
    $scope.page = {
        at: 1,
        size: 30
    };
    $scope.open = function(t) {
        if ($scope.editing !== t) {
            $scope.editing = t;
            $scope.searchMembers();
        }
    };
    $scope.add = function() {
        var tag = {
            name: '新标签',
            type: '0',
            'authapi_id': $scope.selectedAuthapi.authid
        };
        $scope.tags.push(tag);
        $scope.open(tag);
    };
    $scope.save = function() {
        http2.post('/rest/mp/user/tag/update', $scope.editing, function(rsp) {
            if ($scope.editing.id === undefined) {
                $scope.editing.id = rsp.data.id;
                $scope.editing.extattr = rsp.data.extattr;
            }
            $rootScope.infomsg = '保存成功';
        });
    };
    $scope.remove = function() {
        if ($scope.editing.id) {
            http2.post('/rest/mp/user/tag/remove?id=' + $scope.editing.id, null, function(rsp) {
                removeEditing();
            });
        } else
            removeEditing();
    };
    $scope.doSearch = function() {
        var url = '/rest/mp/user/tag/get';
        url += '?authid=' + $scope.selectedAuthapi.authid;
        http2.get(url, function(rsp) {
            $scope.tags = rsp.data;
        });
    };
    $scope.viewUser = function(event, member) {
        event.preventDefault();
        event.stopPropagation();
        location.href = '/rest/mp/user?openid=' + member.openid;
    };
    $scope.searchMembers = function() {
        var url;
        url = '/rest/mp/user/member/list?authid=' + $scope.selectedAuthapi.authid;
        url += '&tag=' + $scope.editing.id;
        url += '&page=' + $scope.page.at + '&size=' + $scope.page.size
        url += '&contain=total';
        http2.get(url, function(rsp) {
            $scope.members = rsp.data.members;
            $scope.page.total = rsp.data.total;
        });
    };
    http2.get('/rest/mp/authapi/get?valid=Y', function(rsp) {
        $scope.authapis = rsp.data;
        $scope.selectedAuthapi = $scope.authapis[0];
        $scope.doSearch();
    });
}]);