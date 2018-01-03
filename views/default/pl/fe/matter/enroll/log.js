define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlLog', ['$scope', 'srvEnrollLog', function($scope, srvEnrollLog) {
        var _oApp;
        $scope.criteria = {};
        $scope.operations = {
            'read': '阅读',
            'submit': '提交',
            'saveData': '保存',
            'updateData': '修改记录',
            'removeData': '删除记录',
            'restoreData': '恢复记录',
            'add': '新增记录',
            'U': '修改活动',
            'C': '创建活动',
            'verify.batch': '审核通过指定记录',
            'verify.all': '审核通过全部记录'
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