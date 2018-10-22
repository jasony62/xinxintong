define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlUser', ['$scope', 'http2', function($scope, http2) {
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
            var param;
            param = '?site=' + $scope.siteId;
            if (page) {
                $scope.page.at = page;
            }
            param += '&page=' + $scope.page.at + '&size=' + $scope.page.size;
            if ($scope.page.keyword && $scope.page.keyword.length > 0) {
                param += '&keyword=' + $scope.page.keyword;
            }
            if ($scope.selectedGroup) {
                param += '&gid=' + $scope.selectedGroup.id;
            }
            param += '&order=' + $scope.order;
            http2.get('/rest/pl/fe/site/sns/yx/user/list' + param).then(function(rsp) {
                $scope.users = rsp.data.fans;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.keywordKeyup = function(evt) {
            if (evt.which === 13) $scope.doSearch();
        };
        $scope.viewUser = function(event, fan) {
            event.preventDefault();
            event.stopPropagation();
            location.href = '/rest/mp/user?openid=' + fan.openid;
        };
        http2.get('/rest/mp/user/fans/group').then(function(rsp) {
            $scope.groups = rsp.data;
        });
        $scope.doSearch();
    }]);
});