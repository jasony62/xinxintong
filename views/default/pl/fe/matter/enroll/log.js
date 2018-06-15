define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlLog', ['$scope', 'srvEnrollLog', function($scope, srvEnrollLog) {
        var _oApp;
        $scope.criteria = {};
        $scope.operations = {
            'read': '阅读',
            'site.matter.enroll.submit': '提交',
            'updateData': '修改记录',
            'removeData': '删除记录',
            'restoreData': '恢复记录',
            'site.matter.enroll.data.do.like': '表态其他人的填写内容',
            'site.matter.enroll.cowork.do.submit': '提交协作新内容',
            'site.matter.enroll.do.remark': '评论',
            'site.matter.enroll.cowork.do.like': '表态其他人填写的协作内容',
            'site.matter.enroll.remark.do.like': '表态其他人的评论',
            'site.matter.enroll.data.get.agree': '对记录表态',
            'site.matter.enroll.cowork.get.agree': '对协作记录表态',
            'site.matter.enroll.remark.get.agree': '对评论表态',
            'site.matter.enroll.remark.as.cowork': '将用户留言设置为协作记录',
            'site.matter.enroll.remove': '删除记录',
            'add': '新增记录',
            'U': '修改活动',
            'C': '创建活动',
            'verify.batch': '审核通过指定记录',
            'verify.all': '审核通过全部记录',
            'shareT': '分享',
            'shareF': '转发'
        };
        $scope.filter = function(type) {
            srvEnrollLog.filter(type).then(function(data) {
                $scope.criteria = data;
                switch(type) {
                    case 'pl':
                        $scope.plLog.list();
                    break;
                    case 'site':
                        $scope.siteLog.list();
                    break;
                    case 'page':
                        $scope.pageLog.list();
                    break;
                }
            });
        };
        $scope.clean = function() {
            $scope.criteria = {};
            switch($scope.active) {
                case 0:
                    $scope.plLog.list();
                break;
                case 1:
                    $scope.siteLog.list();
                break;
                case 2:
                    $scope.pageLog.list();
                break;
            }
        };
        $scope.plLog = {
            page: {},
            list: function() {
                var _this = this;
                srvEnrollLog.list(this.page, 'pl', $scope.criteria).then(function(logs) {
                    _this.logs = logs;
                });
            }
        };
        $scope.siteLog = {
            page: {},
            list: function() {
                var _this = this;
                srvEnrollLog.list(this.page, 'site', $scope.criteria).then(function(logs) {
                    _this.logs = logs;
                });
            }
        };
        $scope.pageLog = {
            page: {},
            list: function() {
                var _this = this;
                srvEnrollLog.list(this.page, 'page', $scope.criteria).then(function(logs) {
                    _this.logs = logs;
                });
            }
        };
        $scope.$watch('app', function(oApp) {
            if (!oApp) return;
            _oApp = oApp;
            $scope.active = 1;
            $scope.$watch('active', function(nv) {
                if(!nv) return;
                switch(nv) {
                    case 1:
                        $scope.plLog.list();
                    break;
                    case 2:
                        $scope.siteLog.list();
                    break;
                    case 3:
                        $scope.pageLog.list();
                    break;
                }
            });
        });
    }]);
});