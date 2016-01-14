xxtApp.controller('fansCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.SexMap = {
        '0': '未知',
        '1': '男',
        '2': '女',
        '3': '无效值'
    };
    $scope.page = {
        at: 1,
        size: 30,
        keyword: ''
    };
    $scope.order = 'time';
    $scope.doSearch = function(page) {
        if (page) $scope.page.at = page;
        var param = '?page=' + $scope.page.at + '&size=' + $scope.page.size;
        if ($scope.page.keyword && $scope.page.keyword.length > 0)
            param += '&keyword=' + $scope.page.keyword;
        if ($scope.selectedGroup)
            param += '&gid=' + $scope.selectedGroup.id;
        if ($scope.selectedAuthapi) {
            param += '&authid=' + $scope.selectedAuthapi.authid;
            if ($scope.mattrs === undefined)
                param += '&contain=memberAttrs';
        }
        param += '&order=' + $scope.order;
        http2.get('/rest/mp/user/fans/list' + param, function(rsp) {
            var fans = rsp.data[0];
            if ($scope.selectedAuthapi) {
                var i, fan;
                for (i in fans) {
                    fan = fans[i];
                    if (fan.m_extattr) fan.m_extattr = JSON.parse(decodeURIComponent(fan.m_extattr.replace(/\+/g, '%20')));
                }
            }
            $scope.fans = fans;
            $scope.page.total = rsp.data[1];
            rsp.data[2] && ($scope.mattrs = rsp.data[2]);
        });
    };
    $scope.changeAuthapi = function() {
        $scope.mattrs = undefined;
        $scope.doSearch();
    };
    $scope.keywordKeyup = function(evt) {
        if (evt.which === 13) $scope.doSearch();
    };
    $scope.viewUser = function(event, fan) {
        event.preventDefault();
        event.stopPropagation();
        location.href = '/rest/mp/user?openid=' + fan.openid;
    };
    http2.get('/rest/mp/user/fans/group', function(rsp) {
        $scope.groups = rsp.data;
    });
    http2.get('/rest/mp/authapi/get?valid=Y', function(rsp) {
        $scope.authapis = rsp.data;
    });
    $scope.doSearch();
}]);