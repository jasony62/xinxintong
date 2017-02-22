'use strict';
define(["require", "angular", "util.site","enrollService"], function(require, angular) {
    var ngApp = angular.module('app', ['ui.bootstrap', 'util.site.tms', 'ui.tms', 'ui.xxt', 'service.matter', 'service.enroll']);
    ngApp.constant('cstApp', {
        notifyMatter: [{
            value: 'tmplmsg',
            title: '模板消息',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'article',
            title: '单图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'news',
            title: '多图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'channel',
            title: '频道',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'enroll',
            title: '登记活动',
            url: '/rest/pl/fe/matter'
        }],
        innerlink: [{
            value: 'article',
            title: '单图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'news',
            title: '多图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'channel',
            title: '频道',
            url: '/rest/pl/fe/matter'
        }],
        alertMsg: {
            'schema.duplicated': '不允许重复添加登记项',
            'require.mission.phase': '请先指定项目的阶段'
        }
    });
    ngApp.config(['$controllerProvider', 'srvAppProvider', 'srvRecordProvider', function($cp, srvAppProvider,  srvRecordProvider) {
        ngApp.provider = {
            controller: $cp.register
        };
        (function() {
            var ls, siteId, appId, accessId;
            ls = location.search;
            siteId = ls.match(/[\?&]site=([^&]*)/)[1];
            appId = ls.match(/[\?&]app=([^&]*)/)[1];
            accessId = ls.match(/[\?&]accessToken=([^&]*)/)[1];
            //
            srvAppProvider.config(siteId, appId, accessId);
            srvRecordProvider.config(siteId, appId, accessId);
        })();
    }]);
    ngApp.controller('ctrl', ['$scope', '$http', '$timeout', '$uibModal', 'PageLoader', 'PageUrl', 'srvApp', 'srvRecord', function($scope, $http, $timeout, $uibModal, PageLoader, PageUrl, srvApp, srvRecord) {
        $scope.getRecords = function(pageNumber) {
            $scope.rows.reset();
            srvRecord.opSearch(pageNumber);
        };
        $scope.removeRecord = function(record) {
            srvRecord.opRemove(record);
        };
        $scope.batchVerify = function() {
            srvRecord.opBatchVerify($scope.rows);
        };
        $scope.filter = function() {
            srvRecord.opFilter().then(function() {
                $scope.rows.reset();
            });
        };
        $scope.scoreRangeArray = function(schema) {
            var arr = [];
            if (schema.range && schema.range.length === 2) {
                for (var i = schema.range[0]; i <= schema.range[1]; i++) {
                    arr.push('' + i);
                }
            }
            return arr;
        };
        $scope.countSelected = function() {
            var count = 0;
            for (var p in $scope.rows.selected) {
                if ($scope.rows.selected[p] === true) {
                    count++;
                }
            }
            return count;
        };
        // 选中的记录
        $scope.rows = {
            allSelected: 'N',
            selected: {},
            reset: function() {
                this.allSelected = 'N';
                this.selected = {};
            }
        };
        $scope.$watch('rows.allSelected', function(checked) {
            var index = 0;
            if (checked === 'Y') {
                while (index < $scope.records.length) {
                    $scope.rows.selected[index++] = true;
                }
            } else if (checked === 'N') {
                $scope.rows.selected = {};
            }
        });

        $scope.page = {}; // 分页条件
        $scope.criteria = {}; // 过滤条件
        $scope.records = []; // 登记记录
        $scope.subView = 'list'; // 规定初始化展示页面
        $scope.tmsTableWrapReady = 'N';
        srvApp.opGet().then(function(data) {
            var app = data.app, pages = data.page;
            srvRecord.init(app, $scope.page, $scope.criteria, $scope.records);
            PageLoader.render($scope, pages, ngApp).then(function() {
                $scope.doc = pages;
            });
            // schemas
            var recordSchemas = [],
                enrollDataSchemas = [],
                groupDataSchemas = [];
            app.data_schemas.forEach(function(schema) {
                if (schema.type !== 'html') {
                    recordSchemas.push(schema);
                }
            });
            $scope.recordSchemas = recordSchemas;
            app._schemasFromEnrollApp.forEach(function(schema) {
                if (schema.type !== 'html') {
                    enrollDataSchemas.push(schema);
                }
            });
            $scope.enrollDataSchemas = enrollDataSchemas;
            app._schemasFromGroupApp.forEach(function(schema) {
                if (schema.type !== 'html') {
                    groupDataSchemas.push(schema);
                }
            });
            $scope.groupDataSchemas = groupDataSchemas;
            $scope.tmsTableWrapReady = 'Y';
            $timeout(function() {
                $scope.$broadcast('xxt.app.enroll.ready');
            });
            $scope.getRecords();
            window.loading.finish();
        });
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});
