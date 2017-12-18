define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlLog', ['$scope', 'http2', 'srvLog', function($scope, http2, srvLog) {
        var read, favor;
        $scope.read = read = {
            page: {},
            list: function() {
                var _this = this;
                srvLog.list($scope.editing, this.page, 'log').then(function(data) {
                    _this.logs = data.logs;
                    _this.page.total = data.total;
                });
            }
        };
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
        $scope.$watch('editing', function(nv) {
            if(!nv) return;
            read.list();
            favor.list();
        });
    }]);
});
