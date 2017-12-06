define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlLog', ['$scope', 'srvEnrollLog', function($scope, srvEnrollLog) {
        var _oApp;
        $scope.operations = {
            'read': '阅读',
            'add': '新建',
            'updateData': '修改',
            'removeData': '删除',
            'restore': '恢复',
            'submit': '新建',
        };
        $scope.filter = function() {
            srvEnrollLog.filter().then(function(data) {
                $scope.read.criteria = data;
                $scope.read.list();
            });
        };
        $scope.read = {
            page: {},
            criteria: {},
            list: function() {
                var _this = this;
                srvEnrollLog.list(this.page, 'site', this.criteria).then(function(logs) {
                    _this.logs = logs;
                });
            }
        };
        $scope.record = {
            page: {},
            criteria: {},
            list: function(){
                var _this = this;
                srvEnrollLog.list(this.page, 'pl', this.criteria).then(function(logs) {
                    _this.logs = logs;
                });
            }
        };

        $scope.$watch('app', function(oApp) {
            if (!oApp) return;
            _oApp = oApp;
            $scope.active = 0;
            $scope.read.list("site");
            $scope.record.list("pl");
        });
    }]);
});