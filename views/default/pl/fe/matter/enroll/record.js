define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRecord', ['$scope', 'srvApp', 'srvRecord', function($scope, srvApp, srvRecord) {
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
            srvRecord.edit(record);
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
        $scope.verifyAll = function() {
            srvRecord.verifyAll();
        };
        $scope.batchVerify = function() {
            srvRecord.batchVerify($scope.rows);
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
        $scope.countSelected = function() {
            var count = 0;
            for (var p in $scope.rows.selected) {
                if ($scope.rows.selected[p] === true) {
                    count++;
                }
            }
            return count;
        };
        $scope.importByOther = function() {
            srvRecord.importByOther().then(function() {
                $scope.rows.reset();
            });
        };
        $scope.createAppByRecords = function() {
            srvRecord.createAppByRecords($scope.rows).then(function(newApp) {
                location.href = '/rest/pl/fe/matter/enroll?site=' + newApp.siteid + '&id=' + newApp.id;
            });
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
        $scope.tmsTableWrapReady = 'N';
        $scope.$watch('app', function(app) {
            if (!app) return;
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
