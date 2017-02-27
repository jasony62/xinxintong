define(["require", "angular", "util.site", "signinService"], function(require, angular) {
    'use strict';
    var ngApp = angular.module('app', ['ui.bootstrap', 'util.site.tms', 'ui.tms', 'ui.xxt', 'service.matter', 'service.signin']);
    ngApp.constant('cstApp', {});
    ngApp.config(['$controllerProvider', 'srvSigninAppProvider', 'srvOpSigninRecordProvider', function($cp, srvSigninAppProvider, srvOpSigninRecordProvider) {
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
            srvSigninAppProvider.config(siteId, appId, accessId);
            srvOpSigninRecordProvider.config(siteId, appId, accessId);
        })();
    }]);
    ngApp.controller('ctrl', ['$scope', '$http', '$timeout', '$uibModal', 'PageLoader', 'PageUrl', 'srvSigninApp', 'srvOpSigninRecord', function($scope, $http, $timeout, $uibModal, PageLoader, PageUrl, srvSigninApp, srvOpSigninRecord) {
        $scope.getRecords = function(pageNumber) {
            $scope.rows.reset();
            srvOpSigninRecord.search(pageNumber);
        };
        $scope.removeRecord = function(record) {
            srvOpSigninRecord.remove(record);
        };
        $scope.batchVerify = function() {
            srvOpSigninRecord.batchVerify($scope.rows);
        };
        $scope.filter = function() {
            srvOpSigninRecord.filter().then(function() {
                $scope.rows.reset();
            });
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
        $scope.rows = {
            allSelected: 'N',
            selected: {},
            reset: function() {
                this.allSelected = 'N';
                this.selected = {};
            }
        };
        // 选中的记录
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
        srvSigninApp.opGet().then(function(data) {
            var app = data.app,
                pages = data.page;
            srvOpSigninRecord.init(app, $scope.page, $scope.criteria, $scope.records);
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
