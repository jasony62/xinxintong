define([], function () {
    'use strict';
    var ngMod = angular.module('schema.matter', []);
    ngMod.provider('srvMatterSchema', function () {
        this.$get = ['$q', '$uibModal', function ($q, $uibModal) {
            return {
                setOptGroup: function (oApp, oSchema) {
                    var defer = $q.defer();
                    if (!oSchema || !/single|multiple/.test(oSchema.type)) {
                        defer.reject()
                    } else {
                        $uibModal.open({
                            templateUrl: '/views/default/pl/fe/matter/enroll/component/setOptGroup.html?_=1',
                            controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                                function genId() {
                                    var newKey = 1;
                                    _groups.forEach(function (oGroup) {
                                        var gKey;
                                        gKey = parseInt(oGroup.i.split('_')[1]);
                                        if (gKey >= newKey) {
                                            newKey = gKey + 1;
                                        }
                                    });
                                    return 'i_' + newKey;
                                }
                                var _oSchema, _groups, _options, singleSchemas;
                                _oSchema = angular.copy(oSchema);
                                if (_oSchema.optGroups === undefined) {
                                    _oSchema.optGroups = [];
                                }
                                if (_oSchema.ops === undefined) {
                                    _oSchema.ops = [];
                                }
                                singleSchemas = []; //所有单项选择题
                                oApp.dataSchemas.forEach(function (oAppSchema) {
                                    if (oAppSchema.type === 'single' && oAppSchema.id !== oSchema.id) {
                                        singleSchemas.push(oAppSchema);
                                    }
                                });
                                $scope2.singleSchemas = singleSchemas;
                                $scope2.groups = _groups = _oSchema.optGroups;
                                $scope2.options = _options = _oSchema.ops;
                                $scope2.addGroup = function () {
                                    var oNewGroup;
                                    oNewGroup = {
                                        i: genId(),
                                        l: '分组-' + (_groups.length + 1)
                                    };
                                    _groups.push(oNewGroup);
                                    $scope2.toggleGroup(oNewGroup);
                                };
                                $scope2.toggleGroup = function (oGroup) {
                                    var oAppSchema;
                                    $scope2.activeGroup = oGroup;
                                    $scope2.activeOps = [];
                                    if (oGroup.assocOp && oGroup.assocOp.schemaId) {
                                        for (var i = 0, ii = singleSchemas.length; i < ii; i++) {
                                            oAppSchema = singleSchemas[i];
                                            if (oAppSchema.id === oGroup.assocOp.schemaId) {
                                                oGroup.assocOp.schema = oAppSchema;
                                                break;
                                            }
                                        }
                                    }
                                };
                                $scope2.ok = function () {
                                    _groups.forEach(function (oGroup) {
                                        if (oGroup.assocOp && oGroup.assocOp.schema) {
                                            oGroup.assocOp.schemaId = oGroup.assocOp.schema.id;
                                            delete oGroup.assocOp.schema;
                                        }
                                    });
                                    $mi.close({
                                        groups: _groups,
                                        options: _options
                                    });
                                };
                                $scope2.cancel = function () {
                                    $mi.dismiss();
                                };
                            }],
                            backdrop: 'static',
                        }).result.then(function (groupAndOps) {
                            oSchema.ops = groupAndOps.options;
                            oSchema.optGroups = groupAndOps.groups;
                            defer.resolve(oSchema)
                        });
                    }
                    return defer.promise;
                },
                setVisibility: function (oApp, oSchema) {
                    return $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/enroll/component/schema/setVisibility.html?_=2',
                        controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                            var _optSchemas, _rules, _oBeforeRules;
                            _optSchemas = []; //所有选择题
                            _rules = []; // 当前题目的可见规则
                            if (oSchema.visibility && oSchema.visibility.rules && oSchema.visibility.rules.length) {
                                _oBeforeRules = {};
                                oSchema.visibility.rules.forEach(function (oRule) {
                                    if (_oBeforeRules[oRule.schema]) {
                                        _oBeforeRules[oRule.schema].push(oRule.op);
                                    } else {
                                        _oBeforeRules[oRule.schema] = [oRule.op];
                                    }
                                });
                            }
                            oApp.dataSchemas.forEach(function (oAppSchema) {
                                if (/single|multiple/.test(oAppSchema.type) && oAppSchema.id !== oSchema.id) {
                                    _optSchemas.push(oAppSchema);
                                    if (_oBeforeRules && _oBeforeRules[oAppSchema.id]) {
                                        var oBeforeRule;
                                        for (var i = 0, ii = oAppSchema.ops.length; i < ii; i++) {
                                            if (_oBeforeRules[oAppSchema.id].indexOf(oAppSchema.ops[i].v) !== -1) {
                                                oBeforeRule = {
                                                    schema: oAppSchema
                                                };
                                                oBeforeRule.op = oAppSchema.ops[i];
                                                _rules.push(oBeforeRule);
                                            }
                                        }
                                    }
                                }
                            });
                            $scope2.data = {
                                logicOR: false
                            };
                            if (oSchema.visibility && oSchema.visibility.logicOR) {
                                $scope2.data.logicOR = true;
                            }
                            $scope2.optSchemas = _optSchemas;
                            $scope2.rules = _rules;
                            $scope2.addRule = function () {
                                _rules.push({});
                            };
                            $scope2.removeRule = function (oRule) {
                                _rules.splice(_rules.indexOf(oRule), 1);
                            };
                            $scope2.cleanRule = function () {
                                _rules.splice(0, _rules.length);
                            };
                            $scope2.ok = function () {
                                var oConfig = {
                                    rules: [],
                                    logicOR: $scope2.data.logicOR
                                };
                                _rules.forEach(function (oRule) {
                                    oConfig.rules.push({
                                        schema: oRule.schema.id,
                                        op: oRule.op.v
                                    });
                                });
                                oSchema.visibility = oConfig;
                                $mi.close(oSchema);
                            };
                            $scope2.cancel = function () {
                                $mi.dismiss();
                            };
                        }],
                        backdrop: 'static',
                    }).result
                }
            }
        }]
    })
})