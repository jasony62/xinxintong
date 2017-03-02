define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlLog', ['$scope', 'http2', 'srvLog', function($scope, http2, srvLog) {
        var read, favor;
        $scope.read = read = {
            page: {},
            list: function() {
                var _this = this;
                srvLog.list($scope.editing.id, this.page, 'log').then(function(logs) {
                    _this.logs = logs;
                });
            }
        };
        $scope.favor = favor = {
            page: {},
            list: function() {
                var _this = this;
                srvLog.list($scope.editing.id, this.page, 'favor').then(function(favorers) {
                    _this.favorers = favorers;
                });
            }
        };
        read.list();
        favor.list();
    }]);
});
