define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlLog', ['$scope', 'srvEnrollLog', function($scope, srvEnrollLog) {
        var _oApp;
        $scope.criteria = {};
        $scope.operations = {
            'read': '阅读',
            'submit': '提交',
            'updateData': '修改数据',
            'removeData': '删除数据',
            'restoreData': '恢复数据',
            'add': '新增数据',
            'U': '修改活动'
        };
        $scope.filter = function(type) {
            srvEnrollLog.filter(type).then(function(data) {
                $scope.criteria = data;
                type == 'site' ? $scope.read.list() : $scope.record.list();
            });
        };
        $scope.clean = function() {
            $scope.criteria = {};
            $scope.active == '0' ? $scope.read.list() : $scope.record.list();
        }
        $scope.read = {
            page: {},
            list: function() {
                var _this = this;
                srvEnrollLog.list(this.page, 'site', $scope.criteria).then(function(logs) {
                    _this.logs = logs;
                });
            }
        };
        $scope.record = {
            page: {},
            list: function(){
                var _this = this;
                srvEnrollLog.list(this.page, 'pl', $scope.criteria).then(function(logs) {
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