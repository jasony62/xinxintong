define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlTask', ['$scope', 'http2', 'noticebox', 'srvPlanApp', '$uibModal', 'tmsSchema', function($scope, http2, noticebox, srvPlanApp, $uibModal, tmsSchema) {
        var _oApp, _oCriteria, _oGroup, _oPage;
        _oGroup = {};
        $scope.page = _oPage = {
            at: 1,
            size: 30,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
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
        $scope.criteria = _oCriteria = {
            record: {
                verified: '',
            },
            byTaskSchema: '',
            tags: [],
            data: {},
            keyword: '',
        };
        $scope.tmsTableWrapReady = 'N';
        $scope.$watch('rows.allSelected', function(checked) {
            var index = 0;
            if (checked === 'Y') {
                while (index < $scope.tasks.length) {
                    $scope.rows.selected[index++] = true;
                }
                $scope.rows.count = $scope.tasks.length;
            } else if (checked === 'N') {
                $scope.rows.reset();
            }
        });
        $scope.gotoTask = function(oTask) {
            location.href = '/rest/pl/fe/matter/plan/taskDetail?id=' + _oApp.id + '&site=' + _oApp.siteid + '&task=' + oTask.id;
        };
        $scope.doSearch = function(pageNumber) {
            pageNumber && (_oPage.at = pageNumber);
            var url = '/rest/pl/fe/matter/plan/task/list?app=' + _oApp.id + _oPage.j();
            http2.post(url, _oCriteria).then(function(rsp) {
                var tasks, total, oSchemasById;
                tasks = rsp.data.tasks;
                oSchemasById = {};
                _oApp.checkSchemas.forEach(function(oSchema) {
                    oSchemasById[oSchema.id] = oSchema;
                });
                tasks.forEach(function(oTask) {
                    var oFirstAction, oFirstData;
                    if (oTask.actions.length) {
                        oFirstAction = oTask.actions[0];
                    }
                    if (oFirstAction && oTask.data && oTask.data[oFirstAction.id]) {
                        oFirstData = oTask.data[oFirstAction.id];
                        oFirstData = tmsSchema.forTable({ data: oFirstData }, oSchemasById);
                        oTask._data = oFirstData._data;
                    }
                });
                $scope.tasks = tasks;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.filter = function() {
            var that = this;
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/plan/component/planFilter.html?_=1',
                controller: 'ctrlPlanFilter',
                windowClass: 'auto-height',
                backdrop: 'static',
                resolve: {
                    tasks: function() {
                        return angular.copy(_oApp.taskSchemas);
                    },
                    dataSchemas: function() {
                        return angular.copy(_oApp.checkSchemas);
                    },
                    criteria: function() {
                        return angular.copy(that.criteria);
                    }
                }
            }).result.then(function(criteria) {
                angular.extend(that.criteria, criteria);
                that.doSearch(1);
            });
        };
        $scope.batchVerify = function(rows) {
            var ids = [],
                selectedTasks = [];
            for (var p in rows.selected) {
                if (rows.selected[p] === true) {
                    ids.push($scope.tasks[p].id);
                    selectedTasks.push($scope.tasks[p]);
                }
            }
            if (ids.length) {
                http2.post('/rest/pl/fe/matter/plan/task/batchVerify?app=' + _oApp.id, {
                    ids: ids
                }).then(function(rsp) {
                    selectedTasks.forEach(function(oTask) {
                        oTask.verified = 'Y';
                    });
                });
            }
        };
        $scope.verifyAll = function() {
            if (window.confirm('确定审核通过所有记录（共' + $scope.page.total + '条）？')) {
                http2.get('/rest/pl/fe/matter/plan/task/verifyAll?app=' + _oApp.id).then(function(rsp) {
                    $scope.tasks.forEach(function(task) {
                        task.verified = 'Y';
                    });
                    noticebox.success('完成操作');
                });
            }
        };
        $scope.export = function() {
            var url, oCriteria;
            oCriteria = {};
            if (_oCriteria.keyword) {
                oCriteria.keyword = _oCriteria.keyword;
            }
            if (_oCriteria.data && Object.keys(_oCriteria.data).length) {
                var oFilterDat = {};
                angular.forEach(_oCriteria.data, function(v, k) {
                    v && (oFilterDat[k] = v);
                });
                if (Object.keys(oFilterDat).length) {
                    oCriteria.data = oFilterDat;
                }
            }
            if (_oCriteria.byTaskSchema) {
                oCriteria.byTaskSchema = _oCriteria.byTaskSchema;
            }
            if (_oCriteria.record) {
                var oFilterRec = {};
                if (_oCriteria.record.verified) {
                    oFilterRec.verified = _oCriteria.record.verified;
                }
                if (Object.keys(oFilterRec).length) {
                    oCriteria.record = oFilterRec;
                }
            }
            url = '/rest/pl/fe/matter/plan/task/export?app=' + _oApp.id;
            url += '&filter=' + JSON.stringify(oCriteria);
            window.open(url);
        };
        $scope.exportImage = function() {
            var url;
            url = '/rest/pl/fe/matter/plan/task/exportImage?app=' + _oApp.id;
            window.open(url);
        };
        srvPlanApp.get().then(function(oApp) {
            if (oApp.entryRule.scope.group && oApp.entryRule.scope.group == 'Y' && oApp.groupApp.rounds.length) {
                oApp.groupApp.rounds.forEach(function(round) {
                    _oGroup[round.round_id] = round;
                });
            }
            oApp._rounds = _oGroup;
            _oApp = oApp;
            $scope.tmsTableWrapReady = 'Y';
            $scope.doSearch();
        });
    }]);
    ngApp.provider.controller('ctrlPlanFilter', ['$scope', '$uibModalInstance', 'tasks', 'dataSchemas', 'criteria', function($scope, $mi, tasks, dataSchemas, lastCriteria) {
        var canFilteredSchemas = [];
        $scope.tasks = tasks;
        dataSchemas.forEach(function(schema) {
            if (false === /image|file|score|html/.test(schema.type) && schema.id.indexOf('member') !== 0) {
                canFilteredSchemas.push(schema);
            }
            if (/multiple/.test(schema.type)) {
                var options = {};
                if (lastCriteria.data[schema.id]) {
                    lastCriteria.data[schema.id].split(',').forEach(function(key) {
                        options[key] = true;
                    })
                }
                lastCriteria.data[schema.id] = options;
            }
            $scope.schemas = canFilteredSchemas;
            $scope.criteria = lastCriteria;
        });
        $scope.clean = function() {
            var criteria = $scope.criteria;
            if (criteria.record) {
                if (criteria.record.verified) {
                    criteria.record.verified = '';
                }
            }
            if (criteria.data) {
                angular.forEach(criteria.data, function(val, key) {
                    criteria.data[key] = '';
                });
            }
        };
        $scope.ok = function() {
            var criteria = $scope.criteria,
                optionCriteria;
            // 将单选题/多选题的结果拼成字符串
            canFilteredSchemas.forEach(function(schema) {
                var result;
                if (/multiple/.test(schema.type)) {
                    if ((optionCriteria = criteria.data[schema.id])) {
                        result = [];
                        Object.keys(optionCriteria).forEach(function(key) {
                            optionCriteria[key] && result.push(key);
                        });
                        criteria.data[schema.id] = result.join(',');
                    }
                }
            });
            $mi.close(criteria);
        };
        $scope.cancel = function() {
            $mi.dismiss('cancel');
        };
    }]);
});