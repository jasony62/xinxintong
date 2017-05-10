define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMessage', ['$scope', 'http2', function($scope, http2) {
        $scope.$parent.subView = 'message';
        $scope.resetWall = function() {
            var vcode;
            vcode = prompt('是否要删除收到的所有信息？，若是，请输入信息墙名称。');
            if (vcode === $scope.wall.title) {
                http2.get('/rest/pl/fe/matter/wall/message/reset?id=' + $scope.id, function(rsp) {
                    delete $scope.wall.sourceApp;
                    $scope.messages = [];
                    $scope.page.total = 0;
                    $scope.page.at = 1;
                });
            }
        };
        $scope.page = {
            at: 1,
            size: 30
        };
        $scope.doSearch = function(page) {
            if (!page) {
                page = $scope.page.at;
            } else {
                $scope.page.at = page;
            }
            var url = '/rest/pl/fe/matter/wall/message/list';
            url += '?id=' + $scope.id;
            url += '&page=' + page + '&size=' + $scope.page.size + '&contain=total' + '&site=' + $scope.siteId;
            http2.get(url, function(rsp) {
                $scope.messages = rsp.data[0];
                $scope.page.total = rsp.data[1];
            });
        };
        $scope.doSearch();
    }]);
});
