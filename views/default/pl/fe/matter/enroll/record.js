define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRecord', ['$scope', '$timeout', '$location', 'srvEnrollApp', 'srvEnrollRound', 'srvEnrollRecord', function($scope, $timeout, $location, srvEnrollApp, srvEnlRnd, srvEnrollRecord) {
        function fnSum4Schema() {
            var sum4SchemaAtPage;
            $scope.sum4SchemaAtPage = sum4SchemaAtPage = {};
            if ($scope.bRequireScore) {
                srvEnrollRecord.sum4Schema().then(function(result) {
                    $scope.sum4Schema = result;
                    for (var p in result) {
                        if ($scope.records.length) {
                            $scope.records.forEach(function(oRecord) {
                                if (sum4SchemaAtPage[p]) {
                                    sum4SchemaAtPage[p] += oRecord.data[p] ? parseInt(oRecord.data[p]) : 0;
                                } else {
                                    sum4SchemaAtPage[p] = oRecord.data[p] ? parseInt(oRecord.data[p]) : 0;
                                }
                            });
                        } else {
                            sum4SchemaAtPage[p] = 0;
                        }
                    }
                });
            }
        }

        function fnScore4Schema() {
            var score4SchemaAtPage;
            $scope.score4SchemaAtPage = score4SchemaAtPage = {};
            if ($scope.bRequireScore) {
                srvEnrollRecord.score4Schema().then(function(result) {
                    $scope.score4Schema = result;
                    for (var p in result) {
                        if ($scope.records.length) {
                            $scope.records.forEach(function(oRecord) {
                                if (oRecord.score) {
                                    if (score4SchemaAtPage[p]) {
                                        score4SchemaAtPage[p] += parseFloat(oRecord.score[p] || 0);
                                    } else {
                                        score4SchemaAtPage[p] = parseFloat(oRecord.score[p] || 0);
                                    }
                                    score4SchemaAtPage.sum = parseFloat(oRecord.score.sum || 0);
                                }
                            });
                        } else {
                            score4SchemaAtPage[p] = 0;
                        }
                    }
                });
            }
        }
        $scope.clickAdvCriteria = function(event) {
            event.preventDefault();
            event.stopPropagation();
        };
        $scope.shiftOrderBy = function() {
            if ($scope.criteria.order.orderby == 'sum') {
                $scope.criteria.order.schemaId = ''
            }
            $scope.doSearch(1);
        }
        $scope.doSearch = function(pageNumber) {
            $scope.rows.reset();
            srvEnrollRecord.search(pageNumber).then(function() {
                $scope.bRequireSum && fnSum4Schema();
                $scope.bRequireScore && $timeout(function() {
                    fnScore4Schema();
                });
            });
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
            srvEnrollRecord.filter().then(function() {
                $scope.rows.reset();
                $scope.bRequireSum && fnSum4Schema();
                $scope.bRequireScore && $timeout(function() {
                    fnScore4Schema();
                });
            });
        };
        $scope.editRecord = function(record) {
            $location.path('/rest/pl/fe/matter/enroll/editor').search({ site: $scope.app.siteid, id: $scope.app.id, ek: record ? record.enroll_key : '' });
        };
        $scope.batchTag = function() {
            if ($scope.rows.count) {
                srvEnrollRecord.batchTag($scope.rows);
            }
        };
        $scope.removeRecord = function(record) {
            srvEnrollRecord.remove(record);
        };
        $scope.empty = function() {
            srvEnrollRecord.empty();
        };
        $scope.verifyAll = function() {
            srvEnrollRecord.verifyAll();
        };
        $scope.batchVerify = function() {
            if ($scope.rows.count) {
                srvEnrollRecord.batchVerify($scope.rows);
            }
        };
        $scope.export = function() {
            srvEnrollRecord.export();
        };
        $scope.exportImage = function() {
            srvEnrollRecord.exportImage();
        };
        $scope.importByOther = function() {
            srvEnrollRecord.importByOther().then(function() {
                $scope.rows.reset();
            });
        };
        $scope.createAppByRecords = function() {
            srvEnrollRecord.createAppByRecords($scope.rows).then(function(newApp) {
                location.href = '/rest/pl/fe/matter/enroll?site=' + newApp.siteid + '&id=' + newApp.id;
            });
        };
        // 选中的记录
        $scope.rows = {
            allSelected: 'N',
            selected: {},
            count: 0,
            change: function(index) {
                this.selected[index] ? this.count++ : this.count--;
            },
            reset: function() {
                this.allSelected = 'N';
                this.selected = {};
                this.count = 0;
            }
        };
        $scope.$watch('rows.allSelected', function(checked) {
            var index = 0;
            if (checked === 'Y') {
                while (index < $scope.records.length) {
                    $scope.rows.selected[index++] = true;
                }
                $scope.rows.count = $scope.records.length;
            } else if (checked === 'N') {
                $scope.rows.reset();
            }
        });

        $scope.page = {}; // 分页条件
        $scope.criteria = {}; // 过滤条件
        $scope.records = []; // 登记记录
        $scope.tmsTableWrapReady = 'N';
        srvEnrollApp.get().then(function(app) {
            srvEnrollRecord.init(app, $scope.page, $scope.criteria, $scope.records);
            // schemas
            var recordSchemas = [],
                recordSchemasExt = [],
                enrollDataSchemas = [],
                bRequireSum = false,
                bRequireScore = false,
                groupDataSchemas = [];
            app.dataSchemas.forEach(function(oSchema) {
                if (oSchema.type !== 'html') {
                    recordSchemas.push(oSchema);
                    recordSchemasExt.push(oSchema);
                }
                if (oSchema.remarkable && oSchema.remarkable === 'Y') {
                    recordSchemasExt.push({ type: 'remark', title: '评论数', id: oSchema.id });
                }
                if (oSchema.requireScore && oSchema.requireScore === 'Y') {
                    recordSchemasExt.push({ type: 'score', title: '得分', id: oSchema.id });
                    bRequireScore = true;
                }
                if (oSchema.format && oSchema.format === 'number') {
                    recordSchemasExt.push({ type: 'score', title: '得分', id: oSchema.id });
                    bRequireSum = true;
                    bRequireScore = true;
                }
            });

            $scope.bRequireNickname = app.assignedNickname.valid !== 'Y' || !app.assignedNickname.schema;
            $scope.bRequireSum = bRequireSum;
            $scope.bRequireScore = bRequireScore;
            $scope.recordSchemas = recordSchemas;
            $scope.recordSchemasExt = recordSchemasExt;
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