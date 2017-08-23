define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlLog', ['$scope', 'srvEnrollLog', function($scope, srvEnrollLog) {
        $scope.$watch('app', function(oApp) {
            if (!oApp) return;
            var read;
            $scope.read = read = {
                page: {},
                list: function() {
                    var _this = this;
                    srvEnrollLog.list(oApp.id, this.page).then(function(logs) {
                        _this.logs = logs;
                    });
                }
            };
            read.list();
        });
    }]);
});