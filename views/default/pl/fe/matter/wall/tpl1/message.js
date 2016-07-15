(function() {
    xxtApp.register.controller('messageCtrl', ['$scope', 'http2', function($scope, http2) {
        $scope.$parent.subView = 'message';
        $scope.page = {
            at: 1,
            size: 30
        };
        $scope.doSearch = function(page) {
            if (!page)
                page = $scope.page.at;
            else
                $scope.page.at = page;
            var url = '/rest/mp/app/wall/message/list';
            url += '?wall=' + $scope.wid;
            url += '&page=' + page + '&size=' + $scope.page.size + '&contain=total';
            http2.get(url, function(rsp) {
                $scope.messages = rsp.data[0];
                $scope.page.total = rsp.data[1];
            });
        };
        $scope.resetWall = function() {
            var vcode;
            vcode = prompt('是否要删除收到的所有信息？，若是，请输入信息墙名称。');
            if (vcode === $scope.wall.title) {
                http2.get('/rest/mp/app/wall/message/reset?wall=' + $scope.wid, function(rsp) {
                    $scope.messages = [];
                    $scope.page.total = 0;
                    $scope.page.at = 1;
                });
            }
        };
        $scope.doSearch();
    }]);
})();