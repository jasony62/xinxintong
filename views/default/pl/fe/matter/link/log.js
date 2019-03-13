define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlLog', ['$scope', 'http2', function($scope, http2) {
        var oApp, read, download;
        $scope.read = read = {
            page: {
                at: 1,
                size: 30,
                orderBy: 'time',
                j: function() {
                    var p;
                    p = '&page=' + this.at + '&size=' + this.size;
                    p += '&orderby=' + this.orderBy;
                    return p;
                }
            },
            list: function() {
                var _this = this,
                    url = '/rest/pl/fe/matter/link/log/list?id=' + oApp.id + _this.page.j();
                http2.get(url).then(function(rsp) {
                    _this.logs = rsp.data.logs;
                    _this.page.total = rsp.data.total;
                });
            }
        };
        $scope.download = download = {
            page: {
                at: 1,
                size: 30,
                orderBy: 'time',
                j: function() {
                    var p;
                    p = '&page=' + this.at + '&size=' + this.size;
                    p += '&orderby=' + this.orderBy;
                    return p;
                }
            },
            list: function() {
                var _this = this,
                    url = '/rest/pl/fe/matter/link/log/attachmentLog?site=' + oApp.siteid +'&appId=' + oApp.id + _this.page.j();
                http2.get(url).then(function(rsp) {
                    _this.logs = rsp.data.logs;
                    _this.page.total = rsp.data.total;
                });
            }
        }
        $scope.$watch('editing', function(nv) {
            if (!nv) return;
            oApp = nv;
            $scope.read.list();
            $scope.download.list();
        });
    }]);
});