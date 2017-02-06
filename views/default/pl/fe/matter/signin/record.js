define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRecord', ['$scope', '$uibModal', 'srvApp', 'srvRecord', 'cstApp', function($scope, $uibModal, srvApp, srvRecord, cstApp) {
        $scope.doSearch = function(pageNumber) {
            $scope.rows = {
                allSelected: 'N',
                selected: {}
            };
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
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/signin/component/recordFilter.html?_=2',
                controller: 'ctrlSigninFilter',
                windowClass: 'auto-height',
                backdrop: 'static',
                resolve: {
                    app: function() {
                        return $scope.app;
                    },
                    criteria: function() {
                        return angular.copy($scope.criteria);
                    }
                }
            }).result.then(function(criteria) {
                angular.extend($scope.criteria, criteria);
                $scope.doSearch(1);
            });
        };
        $scope.editRecord = function(record) {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/signin/component/recordEditor.html?_=4',
                controller: 'ctrlEdit',
                backdrop: 'static',
                windowClass: 'auto-height middle-width',
                resolve: {
                    app: function() {
                        return $scope.app;
                    },
                    record: function() {
                        if (record === undefined) {
                            return {
                                aid: $scope.app.id,
                                tags: '',
                                data: {}
                            };
                        } else {
                            record.aid = $scope.app.id;
                            return angular.copy(record);
                        }
                    },
                }
            }).result.then(function(updated) {
                if (record) {
                    srvRecord.update(record, updated[0]);
                } else {
                    srvRecord.add(updated[0]);
                }
            });
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
        $scope.notify = function(isBatch) {
            srvRecord.notify(cstApp.notifyMatter, $scope.rows, isBatch);
        };
        $scope.export = function() {
            srvRecord.export($scope.page);
        };
        $scope.exportImage = function() {
            srvRecord.exportImage($scope.page);
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
            selected: {}
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
            srvApp.mapSchemas(app);
            $scope.enrollDataSchemas = app._schemasFromEnrollApp;
            $scope.tmsTableWrapReady = 'Y';
            $scope.doSearch();
        });
    }]);
    /**
     * 设置过滤条件
     */
    ngApp.provider.controller('ctrlSigninFilter', ['$scope', '$uibModalInstance', 'app', 'criteria', function($scope, $mi, app, lastCriteria) {
        $scope.schemas = app._schemasCanFilter;
        $scope.criteria = lastCriteria;
        $scope.ok = function() {
            var criteria = $scope.criteria,
                optionCriteria;
            // 将单选题/多选题的结果拼成字符串
            if (app._schemasByType['multiple'] && app._schemasByType['multiple'].length) {
                app._schemasByType['multiple'].forEach(function(schema) {
                    if ((optionCriteria = criteria.data[schema.id])) {
                        criteria.data[schema.id] = Object.keys(optionCriteria).join(',');
                    }
                });
            }
            $mi.close(criteria);
        };
        $scope.cancel = function() {
            $mi.dismiss();
        };
    }]);
});
