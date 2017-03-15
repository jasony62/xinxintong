define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlOrder', ['$scope', 'srvTempRecord', function($scope, srvTempRecord) {
        $scope.record = record = {
            page: {},
            list: function() {
                var _this = this;
                srvLog.list($scope.app, this.page).then(function(data) {
                    _this.orders = data;
                });
            }
        };
    }]);
});