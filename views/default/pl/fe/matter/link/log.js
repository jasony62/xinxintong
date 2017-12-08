define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlLog', ['$scope', 'http2', function($scope, http2) {
        var page, oApp;
        $scope.page = page = {
            at: 1,
            size: 30,
            orderBy: 'time',
            j: function() {
                var p;
                p = '&page=' + this.at + '&size=' + this.size;
                p += '&orderby=' + this.orderBy;
                return p;
            }
        };
        $scope.read = function() {
            var url = '/rest/pl/fe/matter/link/log/list?id=' + oApp.id +  page.j();
            http2.get(url, function(rsp) {
                $scope.logs = rsp.data.logs;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.$watch('editing', function(nv) {
            if(!nv) return;
            oApp = nv;
            $scope.read();
        });
    }]);
});