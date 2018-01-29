define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlLog', ['$scope', 'srvPlanApp', 'srvPlanLog', function($scope, srvPlanApp, srvPlanLog) {
        var _oApp;
        $scope.criteria = {};
        $scope.operations = {
            'read': '阅读',
            'submit': '提交',
            'updateData': '修改',
            'U': '修改活动',
            'C': '创建活动',
            'addSchemaTask': '添加任务',
            'batchSchemaTask': '批量添加任务',
            'updateSchemaTask': '修改任务',
            'removeSchemaTask': '删除任务',
            'addSchemaAction': '增加行动项',
            'updateSchemaAction': '修改行动项',
            'removeSchemaAction': '删除行动项',
            'updateTask': '修改用户任务',
            'addUser': '添加用户',
            'updateUser': '修改用户备注信息',
            'verify.batch': '审核通过指定记录',
            'verify.all': '审核通过全部记录'
        };
        $scope.filter = function(type) {
            srvPlanLog.filter(type).then(function(data) {
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
                srvPlanLog.list(this.page, 'site', $scope.criteria).then(function(logs) {
                    _this.logs = logs;
                });
            }
        };
        $scope.record = {
            page: {},
            list: function(){
                var _this = this;
                srvPlanLog.list(this.page, 'pl', $scope.criteria).then(function(logs) {
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