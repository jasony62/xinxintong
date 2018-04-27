define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlLog', ['$scope', 'http2', 'srvLog', function($scope, http2, srvLog) {
        var favor, download;
        $scope.favor = favor = {
            page: {},
            list: function() {
                var _this = this;
                srvLog.list($scope.editing, this.page, 'favor').then(function(favorers) {
                    _this.favorers = favorers.data;
                    _this.page.total = favorers.total;
                });
            }
        };
        $scope.download = download = {
            page: {},
            criteria: {},
            list: function() {
                var _this = this;
                srvLog.list($scope.editing, this.page, 'download', this.criteria).then(function(logs) {
                    _this.logs = logs.logs;
                    _this.page.total = logs.total;
                });
            }
        }
        $scope.$watch('editing', function(nv) {
            if(!nv) return;
            favor.list();
            download.list();
        });
    }]);
});
