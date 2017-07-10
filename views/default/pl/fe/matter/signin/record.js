define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRecord', ['$scope', '$uibModal', 'srvSigninApp', 'srvSigninRecord', function($scope, $uibModal, srvSigninApp, srvSigninRecord) {
        $scope.doSearch = function(pageNumber) {
            $scope.rows.reset();
            srvSigninRecord.search(pageNumber);
        };
        $scope.$on('search-tag.xxt.combox.done', function(event, aSelected) {
            $scope.criteria.tags = $scope.criteria.tags.concat(aSelected);
            $scope.doSearch();
        });
        $scope.$on('search-tag.xxt.combox.del', function(event, removed) {
            var i = $scope.criteria.tags.indexOf(removed);
            $scope.criteria.tags.splice(i, 1);
            $scope.doSearch();
        });
        $scope.filter = function() {
            srvSigninRecord.filter().then(function() {
                $scope.rows.reset();
            });
        };
        $scope.editRecord = function(record) {
            srvSigninRecord.editRecord(record);
        };
        $scope.batchTag = function() {
            srvSigninRecord.batchTag($scope.rows);
        };
        $scope.removeRecord = function(record) {
            srvSigninRecord.remove(record);
        };
        $scope.empty = function() {
            srvSigninRecord.empty();
        };
        $scope.batchVerify = function() {
            srvSigninRecord.batchVerify($scope.rows);
        };
        $scope.verifyAll = function() {
            srvSigninRecord.verifyAll();
        };
        $scope.notify = function(isBatch) {
            srvSigninRecord.notify(isBatch ? $scope.rows : undefined);
        };
        $scope.export = function() {
            srvSigninRecord.export();
        };
        $scope.exportImage = function() {
            srvSigninRecord.exportImage();
        };
        $scope.importByEnrollApp = function() {
            srvSigninRecord.importByEnrollApp().then(function(data) {
                if (data && data.length) {
                    $scope.doSearch(1);
                }
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
        $scope.tmsTableWrapReady = 'N';
        srvSigninApp.get().then(function(app) {
            srvSigninRecord.init(app, $scope.page, $scope.criteria, $scope.records);
            // schemas
            var recordSchemas = [],
                enrollDataSchemas = [],
                groupDataSchemas = [];
            app.dataSchemas.forEach(function(schema) {
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
            $scope.doSearch();
        });
    }]);
});
