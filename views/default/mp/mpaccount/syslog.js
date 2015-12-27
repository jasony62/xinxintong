xxtApp.controller('ctrlSyslog', ['$scope', 'http2', function($scope, http2) {
    $scope.page = {
        at: 1,
        size: 30,
        total: 0,
        param: function() {
            return 'page=' + this.at + '&size=' + this.size;
        }
    };
    $scope.fetch = function() {
        var url;
        url = '/rest/mp/syslog/list?' + $scope.page.param();
        http2.get(url, function(rsp) {
            $scope.page.total = rsp.data.total;
            $scope.logs = rsp.data.logs;
        });
    };
    $scope.fetch();
}]);