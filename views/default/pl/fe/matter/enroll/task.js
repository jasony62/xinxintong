define(['frame', 'schema'], function(ngApp, schemaLib) {
    'use strict';

    function fnWatchConfig($scope, oConfig, oConfigsModified) {
        var $configScope;
        $configScope = $scope.$new(true);
        $configScope.config = oConfig;
        if (oConfig.id)
            oConfigsModified[oConfig.id] = false;
        $configScope.$watch('config', function(nv, ov) {
            if (nv && nv !== ov && nv.id) {
                oConfigsModified[nv.id] = true;
            }
        }, true);
    }
    ngApp.provider.directive('tmsSwitch', function($parse, $timeout) {
        return {
            restrict: 'A',
            require: 'ngModel',
            link: function link(scope, element, attrs, controller) {
                console.log('xxxxx', scope.$id);
                var isInit = false;

                /**
                 * Return the true value for this specific checkbox.
                 * @returns {Object} representing the true view value; if undefined, returns true.
                 */
                var getTrueValue = function() {
                    if (attrs.type === 'radio') {
                        return attrs.value || $parse(attrs.ngValue)(scope) || true;
                    }
                    var trueValue = ($parse(attrs.ngTrueValue)(scope));
                    if (angular.isUndefined(trueValue)) {
                        trueValue = true;
                    }
                    return trueValue;
                };

                /**
                 * Get a boolean value from a boolean-like string, evaluating it on the current scope.
                 * @param value The input object
                 * @returns {boolean} A boolean value
                 */
                var getBooleanFromString = function(value) {
                    return scope.$eval(value) === true;
                };

                /**
                 * Get a boolean value from a boolean-like string, defaulting to true if undefined.
                 * @param value The input object
                 * @returns {boolean} A boolean value
                 */
                var getBooleanFromStringDefTrue = function(value) {
                    return (value === true || value === 'true' || !value);
                };

                /**
                 * Returns the value if it is truthy, or undefined.
                 *
                 * @param value The value to check.
                 * @returns the original value if it is truthy, {@link undefined} otherwise.
                 */
                var getValueOrUndefined = function(value) {
                    return (value ? value : undefined);
                };

                /**
                 * Returns a function that executes the provided expression
                 *
                 * @param value The string expression
                 * @return a function that evaluates the expression
                 */
                var getExprFromString = function(value) {
                    if (angular.isUndefined(value)) {
                        return angular.noop;
                    }
                    return function() {
                        scope.$evalAsync(value);
                    };
                };

                /**
                 * Get the value of the angular-bound attribute, given its name.
                 * The returned value may or may not equal the attribute value, as it may be transformed by a function.
                 *
                 * @param attrName  The angular-bound attribute name to get the value for
                 * @returns {*}     The attribute value
                 */
                var getSwitchAttrValue = function(attrName) {
                    var map = {
                        'switchRadioOff': getBooleanFromStringDefTrue,
                        'switchActive': function(value) {
                            return !getBooleanFromStringDefTrue(value);
                        },
                        'switchAnimate': getBooleanFromStringDefTrue,
                        'switchLabel': function(value) {
                            return value ? value : '&nbsp;';
                        },
                        'switchIcon': function(value) {
                            if (value) {
                                return '<span class=\'' + value + '\'></span>';
                            }
                        },
                        'switchWrapper': function(value) {
                            return value || 'wrapper';
                        },
                        'switchInverse': getBooleanFromString,
                        'switchReadonly': getBooleanFromString,
                        'switchChange': getExprFromString
                    };
                    var transFn = map[attrName] || getValueOrUndefined;
                    return transFn(attrs[attrName]);
                };

                /**
                 * Set a bootstrapSwitch parameter according to the angular-bound attribute.
                 * The parameter will be changed only if the switch has already been initialized
                 * (to avoid creating it before the model is ready).
                 *
                 * @param element   The switch to apply the parameter modification to
                 * @param attr      The name of the switch parameter
                 * @param modelAttr The name of the angular-bound parameter
                 */
                var setSwitchParamMaybe = function(element, attr, modelAttr) {
                    if (!isInit) {
                        return;
                    }
                    var newValue = getSwitchAttrValue(modelAttr);
                    //if (/disabled|onText|offText|handleWidth|labelWidth/.test(attr)) return
                    //if (/disabled|handleWidth|labelWidth/.test(attr)) return
                    //element.bootstrapSwitch(attr, newValue);
                };

                var setActive = function() {
                    setSwitchParamMaybe(element, 'disabled', 'switchActive');
                };

                /**
                 * If the directive has not been initialized yet, do so.
                 */
                var initMaybe = function() {
                    // if it's the first initialization
                    if (!isInit) {
                        var viewValue = (controller.$modelValue === getTrueValue());
                        isInit = !isInit;
                        // Bootstrap the switch plugin
                        // element.bootstrapSwitch({
                        //     radioAllOff: getSwitchAttrValue('switchRadioOff'),
                        //     disabled: getSwitchAttrValue('switchActive'),
                        //     state: viewValue,
                        //     onText: getSwitchAttrValue('switchOnText'),
                        //     offText: getSwitchAttrValue('switchOffText'),
                        //     onColor: getSwitchAttrValue('switchOnColor'),
                        //     offColor: getSwitchAttrValue('switchOffColor'),
                        //     animate: getSwitchAttrValue('switchAnimate'),
                        //     size: getSwitchAttrValue('switchSize'),
                        //     labelText: attrs.switchLabel ? getSwitchAttrValue('switchLabel') : getSwitchAttrValue('switchIcon'),
                        //     wrapperClass: getSwitchAttrValue('switchWrapper'),
                        //     handleWidth: getSwitchAttrValue('switchHandleWidth'),
                        //     labelWidth: getSwitchAttrValue('switchLabelWidth'),
                        //     inverse: getSwitchAttrValue('switchInverse'),
                        //     readonly: getSwitchAttrValue('switchReadonly')
                        // });
                        if (attrs.type === 'radio') {
                            controller.$setViewValue(controller.$modelValue);
                        } else {
                            controller.$setViewValue(viewValue);
                            controller.$formatters[0] = function(value) {
                                if (value === undefined || value === null) {
                                    return value;
                                }
                                return angular.equals(value, getTrueValue());
                            };
                        }
                    }
                };

                var switchChange = getSwitchAttrValue('switchChange');

                /**
                 * Listen to model changes.
                 */
                var listenToModel = function() {

                    attrs.$observe('switchActive', function(newValue) {

                        var active = getBooleanFromStringDefTrue(newValue);
                        // if we are disabling the switch, delay the deactivation so that the toggle can be switched
                        if (!active) {
                            $timeout(setActive);
                        } else {
                            // if we are enabling the switch, set active right away
                            setActive();
                        }
                    });

                    // When the model changes
                    controller.$render = function() {
                        initMaybe();

                        // WORKAROUND for https://github.com/Bttstrp/bootstrap-switch/issues/540
                        // to update model value when bootstrapSwitch is disabled we should
                        // re-enable it and only then update 'state'
                        element.bootstrapSwitch('disabled', '');

                        var newValue = controller.$modelValue;
                        if (newValue !== undefined && newValue !== null) {
                            console.log('fffff', newValue, getTrueValue());
                            element.bootstrapSwitch('state', newValue === getTrueValue(), true);
                        } else {
                            element.bootstrapSwitch('indeterminate', true, true);
                            controller.$setViewValue(undefined);
                        }

                        // return initial value for "disabled"
                        setActive();

                        switchChange();
                    };

                    // angular attribute to switch property bindings
                    var bindings = {
                        'switchRadioOff': 'radioAllOff',
                        'switchOnText': 'onText',
                        'switchOffText': 'offText',
                        'switchOnColor': 'onColor',
                        'switchOffColor': 'offColor',
                        'switchAnimate': 'animate',
                        'switchSize': 'size',
                        'switchLabel': 'labelText',
                        'switchIcon': 'labelText',
                        'switchWrapper': 'wrapperClass',
                        'switchHandleWidth': 'handleWidth',
                        'switchLabelWidth': 'labelWidth',
                        'switchInverse': 'inverse',
                        'switchReadonly': 'readonly'
                    };

                    var observeProp = function(prop, bindings) {
                        return function() {
                            attrs.$observe(prop, function() {
                                console.log('ooo', arguments);
                                setSwitchParamMaybe(element, bindings[prop], prop);
                            });
                        };
                    };

                    // for every angular-bound attribute, observe it and trigger the appropriate switch function
                    for (var prop in bindings) {
                        attrs.$observe(prop, observeProp(prop, bindings));
                    }
                };

                /**
                 * Listen to view changes.
                 */
                var listenToView = function() {

                    if (attrs.type === 'radio') {
                        // when the switch is clicked
                        element.on('change.bootstrapSwitch', function(e) {
                            // discard not real change events
                            if ((controller.$modelValue === controller.$viewValue) && (e.target.checked !== $(e.target).bootstrapSwitch('state'))) {
                                // $setViewValue --> $viewValue --> $parsers --> $modelValue
                                // if the switch is indeed selected
                                if (e.target.checked) {
                                    // set its value into the view
                                    controller.$setViewValue(getTrueValue());
                                } else if (getTrueValue() === controller.$viewValue) {
                                    // otherwise if it's been deselected, delete the view value
                                    controller.$setViewValue(undefined);
                                }
                                switchChange();
                            }
                        });
                    } else {
                        // When the checkbox switch is clicked, set its value into the ngModel
                        element.on('switchChange.bootstrapSwitch', function(e) {
                            // $setViewValue --> $viewValue --> $parsers --> $modelValue
                            controller.$setViewValue(e.target.checked);
                            switchChange();
                        });
                    }
                };

                // Listen and respond to view changes
                //listenToView();

                // Listen and respond to model changes
                listenToModel();

                // On destroy, collect ya garbage
                scope.$on('$destroy', function() {
                    element.bootstrapSwitch('destroy');
                });
            }
        };
    });
    ngApp.provider.controller('ctrlTask', ['$scope', function($scope) {}]);
    ngApp.provider.controller('ctrlTaskBaseline', ['$scope', '$timeout', 'http2', 'noticebox', 'srvEnrollApp', function($scope, $timeout, http2, noticebox, srvEnlApp) {
        var _aConfigs, _oConfigsModified;
        $scope.configs = _aConfigs = [];
        $scope.configsModified = _oConfigsModified = {};
        $scope.addConfig = function() {
            _aConfigs.push({});
        };
        $scope.delConfig = function(oConfig) {
            noticebox.confirm('删除设置目标环节，确定？').then(function() {
                if (oConfig.id) {
                    http2.post('/rest/pl/fe/matter/enroll/updateBaselineConfig?app=' + $scope.app.id, { method: 'delete', data: oConfig }).then(function() {
                        _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                        delete _oConfigsModified[oConfig.id];
                    });
                } else {
                    _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                }
            });
        };
        $scope.save = function(oConfig) {
            http2.post('/rest/pl/fe/matter/enroll/updateBaselineConfig?app=' + $scope.app.id, { method: 'save', data: oConfig }).then(function(rsp) {
                http2.merge(oConfig, rsp.data);
                fnWatchConfig($scope, oConfig, _oConfigsModified);
                noticebox.success('保存成功！');
            });
        };
        srvEnlApp.get().then(function(oApp) {
            if (oApp.baselineConfig && oApp.baselineConfig.length) {
                oApp.baselineConfig.forEach(function(oConfig, index) {
                    var oCopied;
                    oCopied = angular.copy(oConfig);
                    _aConfigs.push(oCopied);
                    fnWatchConfig($scope, oCopied, _oConfigsModified);
                });
            }
        });
    }]);
    ngApp.provider.controller('ctrlTaskQuestion', ['$scope', 'http2', 'noticebox', 'srvEnrollApp', function($scope, http2, noticebox, srvEnlApp) {
        var _aConfigs, _oConfigsModified;
        $scope.configs = _aConfigs = [];
        $scope.configsModified = _oConfigsModified = {};
        $scope.addConfig = function() {
            _aConfigs.push({});
        };
        $scope.delConfig = function(oConfig) {
            noticebox.confirm('删除提问环节，确定？').then(function() {
                if (oConfig.id) {
                    http2.post('/rest/pl/fe/matter/enroll/updateQuestionConfig?app=' + $scope.app.id, { method: 'delete', data: oConfig }).then(function() {
                        _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                        delete _oConfigsModified[oConfig.id];
                    });
                } else {
                    _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                }
            });
        };
        $scope.save = function(oConfig) {
            http2.post('/rest/pl/fe/matter/enroll/updateQuestionConfig?app=' + $scope.app.id, { method: 'save', data: oConfig }).then(function(rsp) {
                http2.merge(oConfig, rsp.data);
                fnWatchConfig($scope, oConfig, _oConfigsModified);
                noticebox.success('保存成功！');
            });
        };
        srvEnlApp.get().then(function(oApp) {
            if (oApp.questionConfig && oApp.questionConfig.length) {
                oApp.questionConfig.forEach(function(oConfig, index) {
                    var oCopied;
                    oCopied = angular.copy(oConfig);
                    _aConfigs.push(oCopied);
                    fnWatchConfig($scope, oCopied, _oConfigsModified);
                });
            }
        });
    }]);
    ngApp.provider.controller('ctrlTaskAnswer', ['$scope', 'http2', 'noticebox', 'srvEnrollApp', function($scope, http2, noticebox, srvEnlApp) {
        var _aConfigs, _oConfigsModified;
        $scope.configs = _aConfigs = [];
        $scope.configsModified = _oConfigsModified = {};
        $scope.addConfig = function() {
            _aConfigs.push({});
        };
        $scope.delConfig = function(oConfig) {
            noticebox.confirm('删除回答环节，确定？').then(function() {
                if (oConfig.id) {
                    http2.post('/rest/pl/fe/matter/enroll/updateAnswerConfig?app=' + $scope.app.id, { method: 'delete', data: oConfig }).then(function() {
                        _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                        delete _oConfigsModified[oConfig.id];
                    });
                } else {
                    _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                }
            });
        };
        $scope.save = function(oConfig) {
            http2.post('/rest/pl/fe/matter/enroll/updateAnswerConfig?app=' + $scope.app.id, { method: 'save', data: oConfig }).then(function(rsp) {
                http2.merge(oConfig, rsp.data);
                fnWatchConfig($scope, oConfig, _oConfigsModified);
                noticebox.success('保存成功！');
            });
        };
        srvEnlApp.get().then(function(oApp) {
            $scope.answerSchemas = oApp.dataSchemas.filter(function(oSchema) { return oSchema.type === 'multitext' && oSchema.cowork === 'Y'; });
            if (oApp.answerConfig && oApp.answerConfig.length) {
                oApp.answerConfig.forEach(function(oConfig, index) {
                    var oCopied;
                    oCopied = angular.copy(oConfig);
                    _aConfigs.push(oCopied);
                    fnWatchConfig($scope, oCopied, _oConfigsModified);
                });
            }
        });
    }]);
    ngApp.provider.controller('ctrlTaskVote', ['$scope', 'http2', 'noticebox', 'srvEnrollApp', function($scope, http2, noticebox, srvEnlApp) {
        var _aConfigs, _oConfigsModified;
        $scope.configs = _aConfigs = [];
        $scope.configsModified = _oConfigsModified = {};
        $scope.addConfig = function() {
            _aConfigs.push({});
        };
        $scope.delConfig = function(oConfig) {
            noticebox.confirm('删除投票环节，确定？').then(function() {
                if (oConfig.id) {
                    http2.post('/rest/pl/fe/matter/enroll/updateVoteConfig?app=' + $scope.app.id, { method: 'delete', data: oConfig }).then(function() {
                        _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                        delete _oConfigsModified[oConfig.id];
                    });
                } else {
                    _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                }
            });
        };
        $scope.save = function(oConfig) {
            http2.post('/rest/pl/fe/matter/enroll/updateVoteConfig?app=' + $scope.app.id, { method: 'save', data: oConfig }).then(function(rsp) {
                http2.merge(oConfig, rsp.data);
                fnWatchConfig($scope, oConfig, _oConfigsModified);
                noticebox.success('保存成功！');
            });
        };
        srvEnlApp.get().then(function(oApp) {
            $scope.votingSchemas = [];
            oApp.dataSchemas.forEach(function(oSchema) {
                if (!/html|single|multiplue|score/.test(oSchema.type)) {
                    $scope.votingSchemas.push(oSchema);
                }
            });
            if (oApp.voteConfig && oApp.voteConfig.length) {
                oApp.voteConfig.forEach(function(oConfig, index) {
                    var oCopied;
                    oCopied = angular.copy(oConfig);
                    _aConfigs.push(oCopied);
                    fnWatchConfig($scope, oCopied, _oConfigsModified);
                });
            }
        });
    }]);
    ngApp.provider.controller('ctrlTaskScore', ['$scope', '$uibModal', 'http2', 'noticebox', 'srvEnrollApp', 'srvEnrollSchema', function($scope, $uibModal, http2, noticebox, srvEnlApp, srvEnlSch) {
        function fnPostScoreConfig(method, oConfig) {
            http2.post('/rest/pl/fe/matter/enroll/updateScoreConfig?app=' + $scope.app.id, { method: method, data: oConfig }).then(function(rsp) {
                if (rsp.data.config) {
                    switch (method) {
                        case 'save':
                            http2.merge(oConfig, rsp.data.config);
                            fnWatchConfig($scope, oConfig, _oConfigsModified);
                            break;
                        case 'delete':
                            _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                            delete _oConfigsModified[oConfig.id];
                            break;
                    }
                }
                if (rsp.data.updatedSchemas && Object.keys(rsp.data.updatedSchemas).length) {
                    $scope.app.dataSchemas.forEach(function(oSchema) {
                        if (rsp.data.updatedSchemas[oSchema.id])
                            http2.merge(oSchema, rsp.data.updatedSchemas[oSchema.id]);
                    });
                }
                noticebox.success('已保存修改！');
            });
        }

        var _aConfigs, _oConfigsModified;
        $scope.configs = _aConfigs = [];
        $scope.configsModified = _oConfigsModified = {};
        $scope.addConfig = function() {
            _aConfigs.push({});
        };
        $scope.delConfig = function(oConfig) {
            noticebox.confirm('删除投票环节，确定？').then(function() {
                if (oConfig.id) {
                    fnPostScoreConfig('delete', oConfig);
                } else {
                    _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                }
            });
        };
        /**
         * 设置题目的打分活动
         */
        $scope.setScoreApp = function(oConfig) {
            var _oSourceApp, _aSourceSchemas;
            if (!oConfig || !oConfig.schemas || oConfig.schemas.length === 0) {
                return;
            }
            _oSourceApp = $scope.app;
            _aSourceSchemas = _oSourceApp.dataSchemas.filter(function(oSourceSchema) { return oConfig.schemas.indexOf(oSourceSchema.id) !== -1; });
            if (_aSourceSchemas.length === 0) {
                return;
            }
            http2.post('/rest/script/time', { html: { 'score': '/views/default/pl/fe/matter/enroll/component/schema/setScoreApp' } }).then(function(rsp) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/schema/setScoreApp.html?_=' + rsp.data.html.score.time,
                    controller: ['$scope', '$uibModalInstance', 'noticebox', 'tkEnrollApp', 'tkDataSchema', function($scope2, $mi, noticebox, tkEnlApp, tkSchema) {
                        function fnNewScoreSchema(oSourceSchema) {
                            var oNewScoreSchema = {
                                title: oSourceSchema.title,
                                dsSchema: { schema: { id: oSourceSchema.id } }
                            };
                            $scope2.addOption(oNewScoreSchema);
                            $scope2.addOption(oNewScoreSchema);
                            return oNewScoreSchema;
                        }

                        var _oResult, _oScoreApp, _aAddedScoreSchemas;

                        $scope2.disabled = false;
                        $scope2.result = _oResult = {};
                        $scope2.$on('xxt.editable.remove', function(e, oOp) {
                            _oResult.schema.ops.splice(_oResult.schema.ops.indexOf(oOp), 1);
                        });
                        $scope2.cancel = function() { $mi.dismiss(); };
                        $scope2.addOption = function(oSchema) {
                            var oNewOp;
                            oNewOp = schemaLib.addOption(oSchema);
                            oNewOp.l = '打分项';
                        };
                        $scope2.ok = function() {
                            if (_oScoreApp) {
                                if (_aAddedScoreSchemas) {
                                    http2.post('/rest/pl/fe/matter/enroll/schema/scoreBySchema?sourceApp=' + _oSourceApp.id, _aAddedScoreSchemas).then(function(rsp) {
                                        var newScoreSchemas;
                                        if (rsp.data) {
                                            angular.forEach(rsp.data, function(oScoreSchema, sourceSchemaId) {
                                                _oScoreApp.dataSchemas.push(oScoreSchema);
                                                for (var i = _aSourceSchemas.length - 1; i >= 0; i--) {
                                                    if (_aSourceSchemas[i].id === sourceSchemaId) {
                                                        _aSourceSchemas[i].scoreApp = { id: _oScoreApp.id, schema: { id: oScoreSchema.id } };
                                                        break;
                                                    }
                                                }
                                            });
                                            /* 更新打分活动 */
                                            tkEnlApp.update(_oScoreApp, { title: _oResult.title, dataSchemas: _oScoreApp.dataSchemas }).then(function() {
                                                tkEnlApp.update(_oSourceApp, { dataSchemas: _oSourceApp.dataSchemas }).then(function() {
                                                    $mi.close();
                                                });
                                            });
                                        }
                                    });
                                } else {
                                    /* 更新活动 */
                                    tkEnlApp.update(_oScoreApp, { title: _oResult.title, dataSchemas: _oScoreApp.dataSchemas }).then(function() {
                                        $mi.close();
                                    });
                                }
                            } else {
                                /* 新建活动 */
                                http2.post('/rest/pl/fe/matter/enroll/create/asScoreBySchema?app=' + _oSourceApp.id, _oResult).then(function(rsp) {
                                    var oNewSchemas;
                                    _oScoreApp = rsp.data.app;
                                    oConfig.scoreApp = { id: _oScoreApp.id };
                                    oNewSchemas = rsp.data.schemas;
                                    _aSourceSchemas.forEach(function(oSourceSchema) {
                                        if (oNewSchemas[oSourceSchema.id]) {
                                            http2.merge(oSourceSchema, oNewSchemas[oSourceSchema.id]);
                                        }
                                    });
                                    $mi.close(oConfig);
                                });
                            }
                        };
                        if (oConfig.scoreApp && oConfig.scoreApp.id) {
                            tkEnlApp.get(oConfig.scoreApp.id).then(function(oScoreApp) {
                                var scoreSchemas, linkedSourceSchemaIds, newSourceSchemaIds;
                                _oScoreApp = oScoreApp;
                                _oResult.title = oScoreApp.title;
                                scoreSchemas = oScoreApp.dataSchemas.filter(function(oScoreSchema) { return oScoreSchema.dsSchema && oScoreSchema.dsSchema.app && oScoreSchema.dsSchema.app.id === _oSourceApp.id && oScoreSchema.dsSchema.schema; });
                                if (_aSourceSchemas.length) {
                                    linkedSourceSchemaIds = scoreSchemas.map(function(oScoreSchema) { return oScoreSchema.dsSchema.schema.id; });
                                    _aSourceSchemas.forEach(function(oSourceSchema) {
                                        var oNewScoreSchema;
                                        if (linkedSourceSchemaIds.indexOf(oSourceSchema.id) === -1) {
                                            oNewScoreSchema = fnNewScoreSchema(oSourceSchema);
                                            scoreSchemas.push(oNewScoreSchema);
                                            if (!_aAddedScoreSchemas) _aAddedScoreSchemas = [];
                                            _aAddedScoreSchemas.push(oNewScoreSchema);
                                        }
                                    });
                                }
                                _oResult.schemas = scoreSchemas
                            });
                        } else {
                            _oResult.title = _oSourceApp.title + '（打分）';
                            _oResult.schemas = [];
                            _aSourceSchemas.forEach(function(oSourceSchema) {
                                _oResult.schemas.push(fnNewScoreSchema(oSourceSchema));
                            });
                        }
                    }],
                    backdrop: 'static',
                }).result.then(function() {
                    $scope.save(oConfig);
                });
            });
        };
        $scope.unlinkScoreApp = function(oConfig) {
            noticebox.confirm('解除和打分活动的关联，确定？').then(function() {
                var _oSourceApp;
                if (oConfig && oConfig.schemas && oConfig.schemas.length) {
                    _oSourceApp = $scope.app;
                    _oSourceApp.dataSchemas.forEach(function(oSourceSchema) {
                        if (oConfig.schemas.indexOf(oSourceSchema.id) !== -1) {
                            delete oSourceSchema.scoreApp;
                            srvEnlSch.update(oSourceSchema, null, 'scoreApp');
                        }
                    });
                }
                srvEnlSch.submitChange().then(function() {
                    delete oConfig.scoreApp;
                    $scope.save(oConfig);
                });
            });
        };
        $scope.save = function(oConfig) {
            fnPostScoreConfig('save', oConfig);
        };
        srvEnlApp.get().then(function(oApp) {
            $scope.scoreSchemas = [];
            oApp.dataSchemas.forEach(function(oSchema) {
                if (!/html|single|multiplue|score/.test(oSchema.type)) {
                    $scope.scoreSchemas.push(oSchema);
                }
            });
            if (oApp.scoreConfig && oApp.scoreConfig.length) {
                oApp.scoreConfig.forEach(function(oConfig, index) {
                    var oCopied;
                    oCopied = angular.copy(oConfig);
                    _aConfigs.push(oCopied);
                    fnWatchConfig($scope, oCopied, _oConfigsModified);
                });
            }
        });
    }]);
});