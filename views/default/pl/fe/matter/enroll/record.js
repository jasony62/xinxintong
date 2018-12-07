define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRecord', ['$scope', '$timeout', '$location', '$uibModal', 'srvEnrollApp', 'srvEnrollRound', 'srvEnrollRecord', '$filter', 'http2', 'noticebox', 'tmsRowPicker', function($scope, $timeout, $location, $uibModal, srvEnrollApp, srvEnlRnd, srvEnlRec, $filter, http2, noticebox, tmsRowPicker) {
        function fnSum4Schema() {
            var sum4SchemaAtPage;
            $scope.sum4SchemaAtPage = sum4SchemaAtPage = {};
            if ($scope.bRequireScore) {
                srvEnlRec.sum4Schema().then(function(oResult) {
                    $scope.sum4Schema = oResult;
                    for (var schemaId in oResult) {
                        if ($scope.records.length) {
                            $scope.records.forEach(function(oRecord) {
                                var recValue, sumValue;
                                if (recValue = oRecord.data[schemaId]) {
                                    if (angular.isObject(recValue)) { // 打分题的情况
                                        sumValue = 0;
                                        angular.forEach(recValue, function(v) {
                                            sumValue += parseFloat(v);
                                        });
                                    } else {
                                        sumValue = parseFloat(recValue);
                                    }
                                    if (sum4SchemaAtPage[schemaId]) {
                                        sum4SchemaAtPage[schemaId] += sumValue;
                                    } else {
                                        sum4SchemaAtPage[schemaId] = sumValue;
                                    }
                                }
                            });
                            if (sum4SchemaAtPage[schemaId]) {
                                sum4SchemaAtPage[schemaId] = $filter('number')(sum4SchemaAtPage[schemaId], 2).replace('.00', '');
                            }
                        } else {
                            sum4SchemaAtPage[schemaId] = 0;
                        }
                    }
                });
            }
        }

        function fnScore4Schema() {
            var score4SchemaAtPage;
            $scope.score4SchemaAtPage = score4SchemaAtPage = { sum: 0 };
            if ($scope.bRequireScore) {
                srvEnlRec.score4Schema().then(function(result) {
                    $scope.score4Schema = result;
                    for (var schemaId in result) {
                        if ($scope.records.length) {
                            $scope.records.forEach(function(oRecord) {
                                if (oRecord.score) {
                                    if (score4SchemaAtPage[schemaId]) {
                                        score4SchemaAtPage[schemaId] += parseFloat(oRecord.score[schemaId] || 0);
                                    } else {
                                        score4SchemaAtPage[schemaId] = parseFloat(oRecord.score[schemaId] || 0);
                                    }
                                }
                            });
                            if (score4SchemaAtPage[schemaId]) {
                                score4SchemaAtPage[schemaId] = $filter('number')(score4SchemaAtPage[schemaId], 2).replace('.00', '');
                            }
                        } else {
                            score4SchemaAtPage[schemaId] = 0;
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
                $scope.criteria.order.schemaId = '';
            }
        };
        $scope.doSearch = function(pageNumber) {
            $scope.rows.reset();
            srvEnlRec.search(pageNumber).then(function() {
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
            srvEnlRec.filter().then(function() {
                $scope.rows.reset();
                $scope.bRequireSum && fnSum4Schema();
                $scope.bRequireScore && $timeout(function() {
                    fnScore4Schema();
                });
            });
        };
        $scope.editRecord = function(record) {
            var newUrl = '/rest/pl/fe/matter/enroll/editor?site=' + $scope.app.siteid + '&id=' + $scope.app.id + '&ek=';
            newUrl += record ? record.enroll_key : '';
            window.open(newUrl, '_blank');
        };
        $scope.batchTag = function() {
            if ($scope.rows.count) {
                srvEnlRec.batchTag($scope.rows);
            }
        };
        $scope.removeRecord = function(record) {
            srvEnlRec.remove(record);
        };
        $scope.batchRemove = function() {
            if ($scope.rows.count) {
                noticebox.confirm('删除选中的记录，确定？').then(function() {
                    srvEnlRec.batchRemove($scope.rows);
                });
            }
        };
        $scope.empty = function() {
            srvEnlRec.empty();
        };
        $scope.verifyAll = function() {
            srvEnlRec.verifyAll();
        };
        $scope.batchVerify = function() {
            if ($scope.rows.count) {
                srvEnlRec.batchVerify($scope.rows);
            }
        };
        $scope.export = function() {
            srvEnlRec.export();
        };
        $scope.exportImage = function() {
            srvEnlRec.exportImage();
        };
        $scope.renewScore = function() {
            srvEnlRnd.list().then(function(oResult) {
                var rounds = oResult.rounds;

                function renewScoreByRound(i) {
                    var oRound;
                    if (i < rounds.length) {
                        srvEnrollApp.renewScore(rounds[i].rid).then(function() {
                            renewScoreByRound(++i);
                        });
                    } else {
                        noticebox.success('完成【' + i + '】个轮次数据的更新');
                        $scope.doSearch(1);
                    }
                }
                renewScoreByRound(0);
            });
        };
        $scope.importByOther = function() {
            srvEnlRec.importByOther().then(function() {
                $scope.rows.reset();
            });
        };
        $scope.exportToOther = function() {
            srvEnlRec.exportToOther($scope.app, $scope.rows);
        };
        $scope.transferVotes = function() {
            srvEnlRec.transferVotes($scope.app);
        };
        $scope.transferSchemaAndVotes = function() {
            srvEnlRec.transferSchemaAndVotes($scope.app);
        };
        $scope.transferGroupAndMarks = function() {
            srvEnlRec.transferGroupAndMarks($scope.app);
        };
        $scope.fillByOther = function() {
            srvEnlRec.fillByOther($scope.app);
        };
        $scope.openFileUrl = function(file) {
            var url;
            url = '/rest/site/fe/matter/enroll/attachment/download?app=' + $scope.app.id;
            url += '&file=' + JSON.stringify(file);
            window.open(url);
        }
        $scope.syncMissionUser = function() {
            var oPosted = {};
            if ($scope.criteria.record && $scope.criteria.record.rid) {
                oPosted.rid = $scope.criteria.record.rid;
            }
            http2.post('/rest/pl/fe/matter/enroll/record/syncMissionUser?app=' + $scope.app.id, oPosted).then(function(rsp) {
                if (rsp.data > 0) {
                    $scope.doSearch(1);
                }
            });
        };
        $scope.syncWithDataSource = function() {
            http2.get('/rest/pl/fe/matter/enroll/record/syncWithDataSource?app=' + $scope.app.id).then(function(rsp) {
                $scope.doSearch(1);
            });
        };
        $scope.syncWithGroupApp = function() {
            $uibModal.open({
                templateUrl: 'syncWithGroupApp.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.config = { $overwrite: 'N' };
                    $scope2.ok = function() {
                        $mi.close($scope2.config);
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss('cancel');
                    };
                }],
                backdrop: 'static',
            }).result.then(function(oConfig) {
                var url;
                url = '/rest/pl/fe/matter/enroll/record/syncGroup?app=' + $scope.app.id + '&overwrite=' + oConfig.overwrite;
                if ($scope.criteria.record && $scope.criteria.record.rid) {
                    url += '&rid=' + $scope.criteria.record.rid;
                }
                http2.get(url).then(function(rsp) {
                    $scope.doSearch(1);
                });
            });
        };
        $scope.syncWithMschema = function() {
            $uibModal.open({
                templateUrl: 'syncWithMschema.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.config = { $overwrite: 'N' };
                    $scope2.ok = function() {
                        $mi.close($scope2.config);
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss('cancel');
                    };
                }],
                backdrop: 'static',
            }).result.then(function(oConfig) {
                var url;
                url = '/rest/pl/fe/matter/enroll/record/syncMschema?app=' + $scope.app.id + '&overwrite=' + oConfig.overwrite;
                if ($scope.criteria.record && $scope.criteria.record.rid) {
                    url += '&rid=' + $scope.criteria.record.rid;
                }
                http2.get(url).then(function(rsp) {
                    $scope.doSearch(1);
                });
            });
        };
        $scope.copyToUser = function() {
            var oCopiedRecord;
            if ($scope.rows.count === 1) {
                oCopiedRecord = $scope.records[$scope.rows.indexes()[0]];
            }
            if (!oCopiedRecord) {
                return;
            }
            http2.post('/rest/script/time', { html: { 'enrollee': '/views/default/pl/fe/matter/enroll/component/enrolleePicker' } }).then(function(rsp) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/enrolleePicker.html?_=' + rsp.data.html.enrollee.time,
                    controller: ['$scope', '$uibModalInstance', 'tmsRowPicker', 'facListFilter', function($scope2, $mi, tmsRowPicker, facListFilter) {
                        var _oPage, _oCriteria, _oRows;
                        $scope2.tmsTableWrapReady = 'Y';
                        $scope2.page = _oPage = {};
                        $scope2.criteria = _oCriteria = { filter: {} };
                        $scope2.rows = _oRows = new tmsRowPicker();
                        $scope2.filter = facListFilter.init(function() {
                            $scope2.doSearch(1);
                        }, _oCriteria.filter);
                        $scope2.doSearch = function(pageAt) {
                            var url;

                            _oRows.reset();
                            pageAt && (_oPage.at = pageAt);
                            url = '/rest/pl/fe/matter/enroll/user/enrollee?app=' + $scope.app.id;
                            http2.post(url, _oCriteria, { page: _oPage }).then(function(rsp) {
                                $scope2.enrollees = rsp.data.users;
                            });
                        };
                        $scope2.execute = function(bClose) {
                            var pickedEnrollees;
                            if (_oRows.count) {
                                pickedEnrollees = [];
                                for (var i in _oRows.selected) {
                                    pickedEnrollees.push($scope2.enrollees[i]);
                                }
                                if (bClose) {
                                    $mi.close(pickedEnrollees);
                                } else {

                                }
                            }
                        };
                        $scope2.cancel = function() {
                            $mi.dismiss('cancel');
                        };
                        $scope2.doSearch(1);
                    }],
                    windowClass: 'auto-height',
                    backdrop: 'static',
                    size: 'lg'
                }).result.then(function(enrollees) {
                    if (enrollees && enrollees.length === 1) {
                        http2.get('/rest/pl/fe/matter/enroll/record/copy?ek=' + oCopiedRecord.enroll_key + '&owner=' + enrollees[0].userid).then(function() {
                            $scope.doSearch();
                        });
                    }
                });
            });
        };
        // 选中的记录
        $scope.rows = new tmsRowPicker();
        $scope.$watch('rows.allSelected', function(checked) {
            $scope.rows.setAllSelected(checked, $scope.records.length);
        });
        $scope.page = {}; // 分页条件
        $scope.criteria = {}; // 过滤条件
        $scope.records = []; // 登记记录
        $scope.tmsTableWrapReady = 'N';
        srvEnrollApp.get().then(function(oApp) {
            http2.get('/rest/pl/fe/matter/enroll/schema/get?app=' + oApp.id).then(function(rsp) {
                rsp.data.forEach(function(oSchema) {
                    oApp._unionSchemasById[oSchema.id] = oSchema;
                });
                srvEnlRec.init(oApp, $scope.page, $scope.criteria, $scope.records);
                // schemas
                var recordSchemas = [],
                    recordSchemasExt = [],
                    enrollDataSchemas = [],
                    bRequireSum = false,
                    bRequireScore = false,
                    groupDataSchemas = [];

                rsp.data.forEach(function(oSchema) {
                    if (oSchema.type !== 'html') {
                        recordSchemas.push(oSchema);
                        recordSchemasExt.push(oSchema);
                    }
                    if (oSchema.requireScore && oSchema.requireScore === 'Y') {
                        recordSchemasExt.push({ type: 'calcScore', title: '得分', id: oSchema.id });
                        bRequireScore = true;
                        if (oSchema.type === 'score') {
                            bRequireSum = true;
                        }
                    }
                    if (oSchema.format && oSchema.format === 'number') {
                        bRequireSum = true;
                    }
                });

                $scope.bRequireNickname = oApp.assignedNickname.valid !== 'Y' || !oApp.assignedNickname.schema;
                if (oApp.entryRule.group) {
                    $scope.bRequireGroup = oApp._schemasById['_round_id'] ? false : true;
                } else {
                    $scope.bRequireGroup = false;
                }
                $scope.bRequireSum = bRequireSum;
                $scope.bRequireScore = bRequireScore;
                $scope.recordSchemas = recordSchemas;
                $scope.recordSchemasExt = recordSchemasExt;
                if (oApp._schemasFromEnrollApp) {
                    oApp._schemasFromEnrollApp.forEach(function(schema) {
                        if (schema.type !== 'html') {
                            enrollDataSchemas.push(schema);
                        }
                    });
                }
                $scope.enrollDataSchemas = enrollDataSchemas;
                if (oApp._schemasFromGroupApp) {
                    oApp._schemasFromGroupApp.forEach(function(schema) {
                        if (schema.type !== 'html') {
                            groupDataSchemas.push(schema);
                        }
                    });
                }
                $scope.groupDataSchemas = groupDataSchemas;
                $scope.tmsTableWrapReady = 'Y';
                $scope.doSearch();
            });
        });
    }]);
});