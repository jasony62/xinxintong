define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRecord', ['$scope', '$uibModal', 'srvApp', 'srvRecord', function($scope, $uibModal, srvApp, srvRecord) {
        $scope.doSearch = function(pageNumber) {
            $scope.rows.reset();
            srvRecord.search(pageNumber);
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
            srvRecord.filter().then(function() {
                $scope.rows.reset();
            });
        };
        $scope.editRecord = function(record) {
            srvRecord.editRecord(record);
        };
        $scope.batchTag = function() {
            srvRecord.batchTag($scope.rows);
        };
        $scope.removeRecord = function(record) {
            srvRecord.remove(record);
        };
        $scope.empty = function() {
            srvRecord.empty();
        };
        $scope.batchVerify = function() {
            srvRecord.batchVerify($scope.rows);
        };
        $scope.verifyAll = function() {
            srvRecord.verifyAll();
        };
        $scope.notify = function(isBatch) {
            srvRecord.notify(isBatch ? $scope.rows : undefined);
        };
        $scope.export = function() {
            srvRecord.export();
        };
        $scope.exportImage = function() {
            srvRecord.exportImage();
        };
        $scope.importByEnrollApp = function() {
            srvRecord.importByEnrollApp().then(function(data) {
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
        srvApp.get().then(function(app) {
            srvRecord.init(app, $scope.page, $scope.criteria, $scope.records);
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
            $scope.doSearch();
        });
    }]);
});
