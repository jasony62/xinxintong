define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlSys', ['$scope', 'http2', function($scope, http2) {
        $scope.page = {
            at: 1,
            size: 50,
            total: 0,
            param: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.fetch = function() {
            var url;
            url = '/rest/pl/be/log/sys/list?' + $scope.page.param();
            http2.get(url, function(rsp) {
                $scope.page.total = rsp.data.total;
                $scope.logs = rsp.data.logs;
            });
        };
        $scope.remove = function(log, index) {
            if (window.confirm('确定删除？')) {
                var url;
                url = '/rest/pl/be/log/sys/remove?id=' + log.id;
                http2.get(url, function(rsp) {
                    $scope.logs.splice(index, 1);
                    $scope.page.total--;
                });
            }
        };
        $scope.fetch();
    }]);
});