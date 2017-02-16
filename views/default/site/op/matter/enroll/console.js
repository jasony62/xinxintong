'use strict';
define(["require", "angular", "util.site"], function(require, angular) {
    var ngApp = angular.module('app', ['ui.bootstrap', 'util.site.tms']);
    ngApp.config(['$controllerProvider', function($cp) {
        ngApp.provider = {
            controller: $cp.register
        };
    }]);
    ngApp.controller('ctrl', ['$scope', '$http', '$timeout', '$uibModal', 'PageLoader', 'PageUrl', function($scope, $http, $timeout, $uibModal, PageLoader, PageUrl) {
        var PU, criteria, page, schemasById = {},
            filterSchemas = [];

        PU = PageUrl.ins('/rest/site/op/matter/enroll', ['site', 'app', 'accessToken']);
        // 数据筛选条件
        $scope.criteria = criteria = {
            join: function() {
                var params = '';
                if (this.verified && this.verified.length) {
                    params += '&verified=' + this.verified;
                }
                return params;
            }
        };
        // 数据分页条件
        $scope.page = page = {
            at: 1,
            size: 30,
            numbers: [],
            orderBy: 'time',
            byRound: '',
            join: function() {
                var p;
                p = '&page=' + (this.at || 1) + '&size=' + this.size;
                this.byRound && (p += '&rid=' + this.byRound);
                p += '&orderby=' + this.orderBy;
                return p;
            },
            setTotal: function(total) {
                var lastNumber;
                this.total = total;
                this.numbers = [];
                lastNumber = this.total > 0 ? Math.ceil(this.total / this.size) : 1;
                for (var i = 1; i <= lastNumber; i++) {
                    this.numbers.push(i);
                }
            }
        };
        $scope.rows = {
            allSelected: 'N',
            selected: {}
        };
        $scope.getRecords = function() {
            var url = PU.j('record/list', 'site', 'app', 'accessToken');

            url += criteria.join();
            url += page.join();
            $http.post(url, $scope.criteria).success(function(rsp) {
                if (rsp.err_code !== 0) {
                    $scope.errmsg = rsp.err_msg;
                    return;
                }
                $scope.records = rsp.data.records;
                $scope.page.setTotal(rsp.data.total);
            });
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
        $scope.countSelected = function() {
            var count = 0;
            for (var p in $scope.rows.selected) {
                if ($scope.rows.selected[p] === true) {
                    count++;
                }
            }
            return count;
        };
        $scope.value2Html = function(record, schemaId) {
            var schema, val;
            if (undefined !== (schema = schemasById[schemaId])) {
                if (schema.type === 'member' && record.data.member) {
                    val = record.data.member[schema.id.substr(7)] || '';
                } else {
                    val = record.data[schema.id] || '';
                    if (schema.ops && schema.ops.length) {
                        if (schema.type === 'score' && angular.isObject(val)) {
                            var label = '';
                            schema.ops.forEach(function(op, index) {
                                label += op.l + ':' + (val[op.v] ? val[op.v] : 0) + ' / ';
                            });
                            val = label.replace(/\s\/\s$/, '');
                        } else if (val.length) {
                            var aVal, aLab = [];
                            aVal = val.split(',');
                            schema.ops.forEach(function(op) {
                                aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                            });
                            if (aLab.length) val = aLab.join(',');
                        }
                    }
                }
            }

            return val;
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
        $scope.batchVerify = function() {
            var eks = [];
            for (var p in $scope.rows.selected) {
                if ($scope.rows.selected[p] === true) {
                    eks.push($scope.records[p].enroll_key);
                }
            }
            if (eks.length) {
                var url = PU.j('record/batchVerify', 'site', 'app', 'accessToken');
                $http.post(url, {
                    eks: eks
                }).success(function(rsp) {
                    for (var p in $scope.rows.selected) {
                        if ($scope.rows.selected[p] === true) {
                            $scope.records[p].verified = 'Y';
                        }
                    }
                });
            }
        };
        $scope.filter = function() {
            $uibModal.open({
                templateUrl: 'recordFilter.html',
                controller: ['$scope', '$uibModalInstance', 'criteria', function($scope, $mi, criteria) {
                    $scope.criteria = criteria;
                    $scope.filterSchemas = filterSchemas;
                    $scope.clean = function() {
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
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope.ok = function() {
                        $mi.close(criteria);
                    };
                }],
                backdrop: 'static',
                resolve: {
                    criteria: function() {
                        return angular.copy($scope.criteria);
                    }
                }
            }).result.then(function(criteria) {
                angular.extend($scope.criteria, criteria);
                $scope.getRecords();
            });
        };
        $http.get(PU.j('get', 'site', 'app', 'accessToken')).success(function(rsp) {
            var recordSchemas = [];
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            $scope.app = rsp.data.app;
            PageLoader.render($scope, rsp.data.page, ngApp).then(function() {
                $scope.doc = rsp.data.page;
            });
            if ($scope.app.data_schemas && $scope.app.data_schemas.length) {
                $scope.app.dataSchemas = JSON.parse($scope.app.data_schemas);
                $scope.app.dataSchemas.forEach(function(schema) {
                    schemasById[schema.id] = schema;
                    if (schema.type !== 'html') {
                        recordSchemas.push(schema);
                    }
                    if (/single|phase|multiple/.test(schema.type)) {
                        filterSchemas.push(schema);
                    }
                });
            }
            $scope.recordSchemas = recordSchemas;
            $timeout(function() {
                $scope.$broadcast('xxt.app.enroll.ready');
            });
            $scope.getRecords();
            window.loading.finish();
        }).error(function(content, httpCode) {
            $scope.errmsg = content;
        });
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});
