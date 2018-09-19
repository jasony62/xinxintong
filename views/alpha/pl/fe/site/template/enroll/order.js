define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlOrder', ['$scope', 'srvTempRecord', function($scope, srvTempRecord) {
        var record;
        $scope.record = record = {
            page: {},
            list: function() {
                var _this = this;
                srvTempRecord.list($scope.app, this.page).then(function(data) {
                    _this.orders = data.users;
                });
            }
        };
        record.list();
    }]);
});