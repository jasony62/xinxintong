'use strict';
require('./view.css');

require('./_asset/ui.round.js');

window.moduleAngularModules = ['round.ui.enroll'];

var ngApp = require('./main.js');
ngApp.oUtilSchema = require('../_module/schema.util.js');
ngApp.factory('Record', ['http2', 'tmsLocation', function(http2, LS) {
    var Record, _ins, _deferredRecord;
    Record = function(oApp) {
        var data = {}; // 初始化空数据，优化加载体验
        oApp.dynaDataSchemas.forEach(function(schema) {
            data[schema.id] = '';
        });
        this.current = {
            enroll_at: 0,
            data: data
        };
    };
    Record.prototype.remove = function(record) {
        var url;
        url = LS.j('record/remove', 'site', 'app');
        url += '&ek=' + record.enroll_key;
        return http2.get(url);
    };
    return {
        ins: function(oApp) {
            if (_ins) {
                return _ins;
            }
            _ins = new Record(oApp);
            return _ins;
        }
    };
}]);
ngApp.controller('ctrlRecord', ['$scope', 'Record', 'tmsLocation', '$parse', '$sce', 'noticebox', function($scope, Record, LS, $parse, $sce, noticebox) {
    $scope.value2Label = function(schemaId) {
        var val, oRecord;
        if (schemaId && $scope.Record) {
            oRecord = $scope.Record.current;
            if (oRecord && oRecord.data && oRecord.data[schemaId]) {
                val = $parse(schemaId)(oRecord.data);
                return $sce.trustAsHtml(val);
            }
        }
        return '';
    };
    $scope.score2Html = function(schemaId) {
        var label = '',
            oSchema = $scope.app._schemasById[schemaId],
            val, oRecord;
        if (oSchema && $scope.Record) {
            oRecord = $scope.Record.current;
            if (oRecord && oRecord.data && oRecord.data[schemaId]) {
                val = oRecord.data[schemaId];
                if (oSchema.ops && oSchema.ops.length) {
                    oSchema.ops.forEach(function(op, index) {
                        label += '<div>' + op.l + ': ' + (val[op.v] ? val[op.v] : 0) + '</div>';
                    });
                }
            }
            return $sce.trustAsHtml(label);
        }
        return '';
    };
    $scope.editRecord = function(event, page) {
        if ($scope.app.scenarioConfig) {
            if ($scope.app.scenarioConfig.can_cowork !== 'Y') {
                if ($scope.user.uid !== $scope.Record.current.userid) {
                    noticebox.warn('不允许修改他人提交的数据');
                    return;
                }
            }
        }
        if (!page) {
            for (var i in $scope.app.pages) {
                var oPage = $scope.app.pages[i];
                if (oPage.type === 'I') {
                    page = oPage.name;
                    break;
                }
            }
        }
        $scope.gotoPage(event, page, $scope.Record.current.enroll_key);
    };
    $scope.removeRecord = function(event, page) {
        if ($scope.app.can_cowork && $scope.app.can_cowork !== 'Y') {
            if ($scope.user.uid !== $scope.Record.current.userid) {
                noticebox.warn('不允许删除他人提交的数据');
                return;
            }
        }
        noticebox.confirm('删除记录，确定？').then(function() {
            $scope.Record.remove($scope.Record.current).then(function(data) {
                page && $scope.gotoPage(event, page);
            });
        });
    };
}]);
ngApp.controller('ctrlView', ['$scope', '$sce', '$parse', 'tmsLocation', 'http2', 'noticebox', 'Record', 'picviewer', '$timeout', 'enlRound', function($scope, $sce, $parse, LS, http2, noticebox, Record, picviewer, $timeout, enlRound) {
    function fnGetRecord(oRound) {
        if (oRound) {
            return http2.get(LS.j('record/get', 'site', 'app') + '&rid=' + oRound.rid);
        } else {
            return http2.get(LS.j('record/get', 'site', 'app', 'ek', 'rid'));
        }
    }

    function fnProcessData(oRecord) {
        var oRecData, originalValue, afterValue;
        oRecData = oRecord.data;
        if (oRecData && Object.keys(oRecData).length) {
            _oApp.dynaDataSchemas.forEach(function(oSchema) {
                originalValue = $parse(oSchema.id)(oRecData);
                if (originalValue) {
                    switch (oSchema.type) {
                        case 'longtext':
                            afterValue = ngApp.oUtilSchema.txtSubstitute(originalValue);
                            break;
                        case 'single':
                            if (oSchema.ops && oSchema.ops.length) {
                                for (var i = oSchema.ops.length - 1; i >= 0; i--) {
                                    if (originalValue === oSchema.ops[i].v) {
                                        afterValue = oSchema.ops[i].l;
                                    }
                                }
                            }
                            break;
                        case 'multiple':
                            originalValue = originalValue.split(',');
                            if (oSchema.ops && oSchema.ops.length) {
                                afterValue = [];
                                oSchema.ops.forEach(function(op) {
                                    originalValue.indexOf(op.v) !== -1 && afterValue.push(op.l);
                                });
                                afterValue = afterValue.length ? afterValue.join(',') : '[空]';
                            }
                            break;
                        case 'url':
                            originalValue._text = ngApp.oUtilSchema.urlSubstitute(originalValue);
                            break;
                        default:
                            afterValue = originalValue;
                    }
                }

                $parse(oSchema.id).assign(oRecData, (afterValue || originalValue || (/image|file|voice|multitext/.test(oSchema.type) ? '' : '[空]')));

                afterValue = undefined;
            });
        }
    }

    function fnDisableActions() {
        var domActs, domAct;
        if (domActs = document.querySelectorAll('button[ng-click]')) {
            angular.forEach(domActs, function(domAct) {
                var ngClick = domAct.getAttribute('ng-click');
                if (ngClick.indexOf('editRecord') === 0 || ngClick.indexOf('removeRecord') === 0) {
                    domAct.style.display = 'none';
                }
            });
        }
    }

    /**
     * 控制关联题目的可见性
     */
    function fnToggleAssocSchemas(dataSchemas, oRecordData) {
        dataSchemas.forEach(function(oSchema) {
            var domSchema;
            domSchema = document.querySelector('[wrap=value][schema="' + oSchema.id + '"]');
            if (domSchema && oSchema.visibility && oSchema.visibility.rules && oSchema.visibility.rules.length) {
                var bVisible, oRule;
                bVisible = true;
                for (var i = 0, ii = oSchema.visibility.rules.length; i < ii; i++) {
                    oRule = oSchema.visibility.rules[i];
                    if (oRule.schema.indexOf('member.extattr') === 0) {
                        var memberSchemaId = oRule.schema.substr(15);
                        if (!oRecordData.member.extattr[memberSchemaId] || (oRecordData.member.extattr[memberSchemaId] !== oRule.op && !oRecordData.member.extattr[memberSchemaId][oRule.op])) {
                            bVisible = false;
                            break;
                        }
                    } else if (!oRecordData[oRule.schema] || (oRecordData[oRule.schema] !== oRule.op && !oRecordData[oRule.schema][oRule.op])) {
                        bVisible = false;
                        break;
                    }
                }
                domSchema.classList.toggle('hide', !bVisible);
                oSchema.visibility.visible = bVisible;
            }
        });
    }
    /* 根据获得的记录设置页面状态 */
    function fnSetPageByRecord(oRound) {
        fnGetRecord(oRound).then(function(rsp) {
            var oRecord, oOriginalData;
            oOriginalData = angular.copy(rsp.data.data);
            /* 设置题目的可见性 */
            fnToggleAssocSchemas(_oApp.dynaDataSchemas, oOriginalData);
            /* 将数据转换为可直接显示的形式 */
            fnProcessData(rsp.data);
            $scope.Record.current = oRecord = rsp.data;
            /* disable actions */
            if ((_oApp.can_cowork && _oApp.can_cowork !== 'Y')) {
                if ($scope.user.uid !== oRecord.userid) {
                    fnDisableActions();
                }
            }
            $timeout(function() {
                var imgs;
                if (imgs = document.querySelectorAll('.data img')) {
                    picviewer.init(imgs);
                }
            });
            /*设置页面分享信息*/
            $scope.setSnsShare(oRecord);
        });
    }

    var _oApp;

    $scope.shiftRound = function(oRound) {
        fnSetPageByRecord(oRound);
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var facRecord, _facRound;

        _oApp = params.app;
        $scope.Record = facRecord = Record.ins(_oApp);
        _facRound = new enlRound(_oApp);
        _facRound.list().then(function(oResult) {
            $scope.rounds = oResult.rounds;
        });
        fnSetPageByRecord();
        /*设置页面导航*/
        var oAppNavs = {
            length: 0
        };
        if (_oApp.scenario === 'voting') {
            oAppNavs.votes = {};
            oAppNavs.length++;
        }
        if (_oApp.scenarioConfig) {
            if (_oApp.scenarioConfig.can_repos === 'Y') {
                oAppNavs.repos = {};
                oAppNavs.length++;
            }
            if (_oApp.scenarioConfig.can_rank === 'Y') {
                oAppNavs.rank = {};
                oAppNavs.length++;
            }
            if (_oApp.scenarioConfig.can_action === 'Y') {
                oAppNavs.event = {};
                oAppNavs.length++;
            }
        }
        if (Object.keys(oAppNavs).length) {
            $scope.appNavs = oAppNavs;
        }
        /*页面阅读日志*/
        $scope.logAccess();
    });
}]);